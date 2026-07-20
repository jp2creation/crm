<?php

namespace Modules\CrmCore\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class UploadedCrmFileCleaner
{
    public function deletePublicUpload(?string $path, bool $afterCommit = false): void
    {
        $targets = $this->publicUploadTargets($path);

        if ($targets === []) {
            return;
        }

        $delete = function () use ($targets): void {
            foreach ($targets as $target) {
                if ($target['disk'] === 'public') {
                    Storage::disk('public')->delete($target['path']);

                    continue;
                }

                $uploadsRoot = realpath(public_path('assets/uploads'));
                $targetPath = realpath(public_path($target['path']));

                if ($uploadsRoot === false || $targetPath === false || ! str_starts_with($targetPath, $uploadsRoot)) {
                    continue;
                }

                if (is_file($targetPath)) {
                    File::delete($targetPath);
                }
            }
        };

        if ($afterCommit && DB::connection()->transactionLevel() > 0) {
            DB::afterCommit($delete);

            return;
        }

        $delete();
    }

    /**
     * @return array<int, array{disk: string, path: string}>
     */
    private function publicUploadTargets(?string $path): array
    {
        $path = trim((string) $path);

        if ($path === '') {
            return [];
        }

        $urlPath = parse_url($path, PHP_URL_PATH);
        $relativePath = ltrim(str_replace('\\', '/', (string) ($urlPath ?: $path)), '/');

        if (str_starts_with($relativePath, 'storage/assets/uploads/')) {
            return array_map(
                fn (string $uploadPath): array => ['disk' => 'public', 'path' => $uploadPath],
                $this->relatedPaths(substr($relativePath, strlen('storage/'))),
            );
        }

        if (str_starts_with($relativePath, 'assets/uploads/')) {
            return array_map(
                fn (string $uploadPath): array => ['disk' => 'legacy-public', 'path' => $uploadPath],
                $this->relatedPaths($relativePath),
            );
        }

        return [];
    }

    /**
     * @return array<int, string>
     */
    private function relatedPaths(string $path): array
    {
        $paths = [$path];

        if (str_ends_with($path, '-thumb.webp')) {
            $paths[] = substr($path, 0, -strlen('-thumb.webp')).'.webp';

            return array_values(array_unique($paths));
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if ($extension !== '') {
            $paths[] = substr($path, 0, -(strlen($extension) + 1)).'-thumb.webp';
        }

        return array_values(array_unique($paths));
    }
}
