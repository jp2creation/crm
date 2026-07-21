<?php

declare(strict_types=1);

$basePath = realpath(__DIR__.'/..');

if ($basePath === false) {
    fwrite(STDERR, "Impossible de trouver la racine du projet.\n");
    exit(1);
}

$skipDirectories = [
    '.git',
    '.playwright-mcp',
    'bootstrap/cache',
    'node_modules',
    'public/build',
    'storage',
    'vendor',
];

$allowedPrefixes = [
    'app/',
    'config/',
    'database/',
    'Modules/',
    'resources/lang/',
    'resources/views/',
    'routes/',
    'scripts/',
    'tests/',
];

$allowedFiles = [
    'bootstrap/app.php',
    'bootstrap/providers.php',
    'public/index.php',
];

$suspicious = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($basePath, FilesystemIterator::SKIP_DOTS),
        function (SplFileInfo $file) use ($basePath, $skipDirectories): bool {
            $relativePath = str_replace($basePath.DIRECTORY_SEPARATOR, '', $file->getPathname());
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

            if (! $file->isDir()) {
                return true;
            }

            foreach ($skipDirectories as $directory) {
                if ($relativePath === $directory || str_starts_with($relativePath, $directory.'/')) {
                    return false;
                }
            }

            return true;
        },
    ),
);

foreach ($iterator as $file) {
    if (! $file instanceof SplFileInfo || ! $file->isFile()) {
        continue;
    }

    if (! str_ends_with($file->getFilename(), '.php')) {
        continue;
    }

    $relativePath = str_replace($basePath.DIRECTORY_SEPARATOR, '', $file->getPathname());
    $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

    if (in_array($relativePath, $allowedFiles, true)) {
        continue;
    }

    foreach ($allowedPrefixes as $prefix) {
        if (str_starts_with($relativePath, $prefix)) {
            continue 2;
        }
    }

    $suspicious[] = $relativePath;
}

sort($suspicious);

if ($suspicious !== []) {
    fwrite(STDERR, "Scripts PHP autonomes suspects detectes :\n");

    foreach ($suspicious as $path) {
        fwrite(STDERR, " - {$path}\n");
    }

    exit(1);
}

fwrite(STDOUT, "Aucun script PHP autonome suspect detecte.\n");
