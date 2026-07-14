<?php

namespace Modules\CrmCore\Http\Controllers;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PwaAssetController extends Controller
{
    public function manifest(): BinaryFileResponse
    {
        return response()
            ->file(public_path('manifest.json'), [
                'Cache-Control' => 'public, max-age=3600',
                'Content-Type' => 'application/manifest+json; charset=utf-8',
            ]);
    }

    public function serviceWorker(): BinaryFileResponse
    {
        return response()
            ->file(public_path('sw.js'), [
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Content-Type' => 'application/javascript; charset=utf-8',
                'Service-Worker-Allowed' => '/',
            ]);
    }

    public function offline(): BinaryFileResponse
    {
        return response()
            ->file(public_path('offline.html'), [
                'Cache-Control' => 'public, max-age=3600',
                'Content-Type' => 'text/html; charset=utf-8',
            ]);
    }
}
