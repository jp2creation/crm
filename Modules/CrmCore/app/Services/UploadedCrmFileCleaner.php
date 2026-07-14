<?php

namespace Modules\CrmCore\Services;

use Illuminate\Support\Facades\File;

class UploadedCrmFileCleaner
{
    public function deletePublicUpload(?string $path): void
    {
        $relativePath = $this->publicUploadPath($path);

        if ($relativePath === null) {
            return;
        }

        $uploadsRoot = realpath(public_path('assets/uploads'));
        $targetPath = realpath(public_path($relativePath));

        if ($uploadsRoot === false || $targetPath === false || ! str_starts_with($targetPath, $uploadsRoot)) {
            return;
        }

        if (is_file($targetPath)) {
            File::delete($targetPath);
        }
    }

    private function publicUploadPath(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        $urlPath = parse_url($path, PHP_URL_PATH);
        $relativePath = ltrim(str_replace('\\', '/', (string) ($urlPath ?: $path)), '/');

        return str_starts_with($relativePath, 'assets/uploads/')
            ? $relativePath
            : null;
    }
}
