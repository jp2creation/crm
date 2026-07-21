<?php

namespace Modules\CrmCore\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class LegacyPhpApiController extends Controller
{
    public function __invoke(Request $request): SymfonyResponse
    {
        $targetPath = $this->targetPath($request);
        $this->audit($request, $targetPath);

        if ($request->isMethod('OPTIONS')) {
            return response('', Response::HTTP_NO_CONTENT)
                ->header('Allow', 'GET, HEAD, OPTIONS');
        }

        if ($this->isReadRequest($request) && (bool) config('crm.legacy_php_api.redirect_safe_methods', true)) {
            return redirect()->to($this->targetUrl($request, $targetPath), Response::HTTP_PERMANENTLY_REDIRECT);
        }

        return $this->gone($targetPath);
    }

    private function isReadRequest(Request $request): bool
    {
        return $request->isMethod('GET') || $request->isMethod('HEAD');
    }

    private function targetPath(Request $request): string
    {
        $target = $request->route()?->parameter('crm_legacy_target');

        return is_string($target) && $target !== '' ? $target : '/api';
    }

    private function targetUrl(Request $request, string $targetPath): string
    {
        $query = $request->getQueryString();

        return $query ? "{$targetPath}?{$query}" : $targetPath;
    }

    private function gone(string $targetPath): JsonResponse
    {
        return response()
            ->json([
                'ok' => false,
                'error' => 'Endpoint legacy .php desactive. Utilisez la route API sans extension .php.',
                'target' => $targetPath,
            ], Response::HTTP_GONE, [], JSON_UNESCAPED_UNICODE);
    }

    private function audit(Request $request, string $targetPath): void
    {
        if (! (bool) config('crm.legacy_php_api.log_calls', true)) {
            return;
        }

        $context = [
            'method' => $request->method(),
            'path' => $request->path(),
            'target' => $targetPath,
            'route' => $request->route()?->getName(),
            'ip' => $request->ip(),
            'user_id' => $request->user()?->getAuthIdentifier(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->headers->get('referer'),
        ];

        if ($this->isReadRequest($request)) {
            Log::channel('crm')->notice('Legacy CRM .php API redirected.', $context);

            return;
        }

        Log::channel('crm')->warning('Legacy CRM .php API mutation blocked.', $context);
    }
}
