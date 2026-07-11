<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Services\Crm\PageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class PageApiController extends Controller
{
    public function __invoke(Request $request, PageService $pages): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->json(['ok' => true]);
        }

        try {
            $user = $this->authenticatedApiUser($request);

            if (! $user) {
                return $this->json(['ok' => false, 'error' => 'Utilisateur CRM requis'], 401);
            }

            $actor = $pages->actorForUser($user);
            $action = (string) $request->query('action', 'bootstrap');
            $body = $this->body($request);

            return match ($action) {
                'health' => $this->json(['ok' => true, 'mode' => 'laravel']),
                'bootstrap' => $this->json($pages->bootstrap($actor)),
                'page' => $this->json($pages->page($actor, (string) $request->query('slug', ''))),
                'pages_bootstrap' => $this->json($pages->manageBootstrap($actor)),
                'save_page' => $this->json($pages->savePage($actor, $body)),
                'delete_page' => $this->json($pages->deletePage($actor, $body)),
                default => $this->json(['ok' => false, 'error' => 'Action inconnue'], 404),
            };
        } catch (HttpExceptionInterface $error) {
            return $this->json(['ok' => false, 'error' => $error->getMessage()], $error->getStatusCode());
        } catch (Throwable $error) {
            Log::error('[crm-pages] '.$error->getMessage(), ['exception' => $error]);

            return $this->json([
                'ok' => false,
                'error' => config('app.debug') ? $error->getMessage() : 'Erreur API pages',
            ], 500);
        }
    }

    private function body(Request $request): array
    {
        $json = $request->json()->all();

        if ($json !== []) {
            return $json;
        }

        return $request->request->all();
    }

    private function json(array $data, int $status = 200): JsonResponse
    {
        return response()
            ->json($data, $status, [], JSON_UNESCAPED_UNICODE)
            ->withHeaders($this->crmApiHeaders());
    }
}
