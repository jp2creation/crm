<?php

namespace Modules\CrmCore\Services;

use GdImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @phpstan-type StoredImage array{url: string, path: string, thumbnailUrl: string, thumbnailPath: string, mime: string, size: int, width: int, height: int}
 */
class CrmImageStorage
{
    private const DEFAULT_MAX_BYTES = 5_242_880;

    private const DEFAULT_MAX_WIDTH = 5_000;

    private const DEFAULT_MAX_HEIGHT = 5_000;

    private const DEFAULT_MAX_PIXELS = 20_000_000;

    private const DEFAULT_IMAGE_MAX_WIDTH = 2_048;

    private const DEFAULT_IMAGE_MAX_HEIGHT = 2_048;

    private const DEFAULT_THUMBNAIL_SIZE = 420;

    private const DEFAULT_QUALITY = 82;

    public function __construct(
        private readonly UploadedCrmFileCleaner $cleaner,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return StoredImage
     */
    public function storeDataUrl(string $dataUrl, string $folder, ?string $replaceUrl = null, array $options = []): array
    {
        $label = $this->label($options['label'] ?? 'Photo');
        $binary = $this->decodeDataUrl($dataUrl, $label, $this->intOption($options, 'maxBytes', self::DEFAULT_MAX_BYTES));
        $info = $this->imageInfo($binary, $label, $options);
        $source = $this->imageResource($binary, $label);

        $imageMaxWidth = $this->intOption($options, 'imageMaxWidth', self::DEFAULT_IMAGE_MAX_WIDTH);
        $imageMaxHeight = $this->intOption($options, 'imageMaxHeight', self::DEFAULT_IMAGE_MAX_HEIGHT);
        $thumbnailSize = $this->intOption($options, 'thumbnailSize', self::DEFAULT_THUMBNAIL_SIZE);
        $quality = max(50, min(95, $this->intOption($options, 'quality', self::DEFAULT_QUALITY)));

        $image = $this->scaledImage($source, $info['width'], $info['height'], $imageMaxWidth, $imageMaxHeight);
        $thumbnail = $this->scaledImage($source, $info['width'], $info['height'], $thumbnailSize, $thumbnailSize);

        try {
            $contents = $this->encodeWebp($image, $quality, $label);
            $thumbnailContents = $this->encodeWebp($thumbnail, $quality, $label);
            $storedWidth = imagesx($image);
            $storedHeight = imagesy($image);
        } finally {
            imagedestroy($thumbnail);
            imagedestroy($image);
            imagedestroy($source);
        }

        $directory = $this->directory($folder);
        $name = now()->format('YmdHis').'-'.Str::random(12);
        $path = $directory.'/'.$name.'.webp';
        $thumbnailPath = $directory.'/'.$name.'-thumb.webp';

        $storage = Storage::disk('public');

        if (! $storage->put($path, $contents, ['visibility' => 'public'])) {
            $this->fail("Impossible de stocker l'image", 500);
        }

        if (! $storage->put($thumbnailPath, $thumbnailContents, ['visibility' => 'public'])) {
            $storage->delete($path);
            $this->fail("Impossible de stocker la miniature de l'image", 500);
        }

        if ($replaceUrl !== null && trim($replaceUrl) !== '') {
            $this->cleaner->deletePublicUpload($replaceUrl);
        }

        return [
            'url' => $this->url($path),
            'path' => $path,
            'thumbnailUrl' => $this->url($thumbnailPath),
            'thumbnailPath' => $thumbnailPath,
            'mime' => 'image/webp',
            'size' => strlen($contents),
            'width' => $storedWidth,
            'height' => $storedHeight,
        ];
    }

    public function deletePublicUpload(?string $path): void
    {
        $this->cleaner->deletePublicUpload($path);
    }

    private function decodeDataUrl(string $dataUrl, string $label, int $maxBytes): string
    {
        $dataUrl = trim($dataUrl);

        if (! preg_match('/^data:image\/[a-z0-9.+-]+;base64,(.+)$/is', $dataUrl, $matches)) {
            $this->fail($label.' invalide', 400);
        }

        $payload = preg_replace('/\s+/', '', $matches[1]);
        $binary = is_string($payload) ? base64_decode($payload, true) : false;

        if ($binary === false) {
            $this->fail($label.' invalide', 400);
        }

        if (strlen($binary) > $maxBytes) {
            $this->fail($label.' trop lourde', 400);
        }

        return $binary;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{width: int, height: int, type: int}
     */
    private function imageInfo(string $binary, string $label, array $options): array
    {
        $info = @getimagesizefromstring($binary);

        if ($info === false) {
            $this->fail($label.' invalide', 400);
        }

        $width = (int) $info[0];
        $height = (int) $info[1];
        $type = (int) $info[2];

        if (! in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            $this->fail($label.' invalide', 400);
        }

        $maxWidth = $this->intOption($options, 'maxWidth', self::DEFAULT_MAX_WIDTH);
        $maxHeight = $this->intOption($options, 'maxHeight', self::DEFAULT_MAX_HEIGHT);
        $maxPixels = $this->intOption($options, 'maxPixels', self::DEFAULT_MAX_PIXELS);

        if ($width <= 0 || $height <= 0 || $width > $maxWidth || $height > $maxHeight || ($width * $height) > $maxPixels) {
            $this->fail('Dimensions image trop grandes', 400);
        }

        return ['width' => $width, 'height' => $height, 'type' => $type];
    }

    private function imageResource(string $binary, string $label): GdImage
    {
        $image = @imagecreatefromstring($binary);

        if (! $image instanceof GdImage) {
            $this->fail($label.' invalide', 400);
        }

        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        return $image;
    }

    private function scaledImage(GdImage $source, int $width, int $height, int $maxWidth, int $maxHeight): GdImage
    {
        $ratio = min(1.0, $maxWidth / max(1, $width), $maxHeight / max(1, $height));
        $targetWidth = max(1, (int) floor($width * $ratio));
        $targetHeight = max(1, (int) floor($height * $ratio));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        if (! $target instanceof GdImage) {
            $this->fail("Impossible de preparer l'image", 500);
        }

        imagealphablending($target, false);
        imagesavealpha($target, true);

        $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
        imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);

        if (! imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height)) {
            imagedestroy($target);
            $this->fail("Impossible de redimensionner l'image", 500);
        }

        return $target;
    }

    private function encodeWebp(GdImage $image, int $quality, string $label): string
    {
        ob_start();
        $encoded = @imagewebp($image, null, $quality);
        $contents = ob_get_clean();

        if (! $encoded || $contents === '') {
            $this->fail('Encodage '.$label.' impossible', 500);
        }

        return $contents;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function intOption(array $options, string $key, int $default): int
    {
        $value = (int) ($options[$key] ?? $default);

        return $value > 0 ? $value : $default;
    }

    private function directory(string $folder): string
    {
        $segments = array_filter(array_map(
            fn (string $segment): string => trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '', $segment)),
            explode('/', str_replace('\\', '/', $folder)),
        ));

        if ($segments === []) {
            $segments = ['general'];
        }

        return 'assets/uploads/'.implode('/', $segments);
    }

    private function url(string $path): string
    {
        return '/storage/'.str_replace('\\', '/', ltrim($path, '/'));
    }

    private function label(mixed $value): string
    {
        $label = trim((string) $value);

        return $label !== '' ? $label : 'Photo';
    }

    private function fail(string $message, int $status): never
    {
        throw new HttpException($status, $message);
    }
}
