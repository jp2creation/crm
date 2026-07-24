<?php

namespace Modules\CrmCore\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicUploadController extends Controller
{
    public function __invoke(string $publicUploadPath): StreamedResponse
    {
        $path = ltrim(str_replace('\\', '/', rawurldecode($publicUploadPath)), '/');

        abort_unless(str_starts_with($path, 'assets/uploads/'), 404);
        abort_if(str_contains($path, '..'), 404);

        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
