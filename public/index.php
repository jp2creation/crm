<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$legacyApiPathCandidates = [
    $_SERVER['REQUEST_URI'] ?? '',
    $_SERVER['REDIRECT_URL'] ?? '',
    $_SERVER['UNENCODED_URL'] ?? '',
    $_SERVER['ORIG_PATH_INFO'] ?? '',
    $_SERVER['SCRIPT_URL'] ?? '',
    $_SERVER['SCRIPT_URI'] ?? '',
    $_SERVER['PHP_SELF'] ?? '',
    $_SERVER['SCRIPT_NAME'] ?? '',
    $_SERVER['PATH_INFO'] ?? '',
    $_SERVER['REQUEST_FILENAME'] ?? '',
    $_SERVER['HTTP_X_ORIGINAL_URL'] ?? '',
    $_SERVER['HTTP_X_REWRITE_URL'] ?? '',
    $_SERVER['THE_REQUEST'] ?? '',
];

$isLegacyPhpApiRequest = static function (string $value): bool {
    if ($value === '') {
        return false;
    }

    $path = parse_url($value, PHP_URL_PATH);

    if (is_string($path) && preg_match('#(?:^|/)api/[^?]*\.php$#i', trim(rawurldecode($path), '/')) === 1) {
        return true;
    }

    return preg_match('#(?:^|\s)/?api/[^?\s]+\.php(?:[?\s]|$)#i', $value) === 1;
};

foreach ($legacyApiPathCandidates as $legacyApiPathCandidate) {
    if ($isLegacyPhpApiRequest((string) $legacyApiPathCandidate)) {
        http_response_code(404);
        exit;
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
