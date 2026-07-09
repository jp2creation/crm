<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Services\Crm\AdministrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class AdministrationApiController extends Controller
{
    public function __invoke(Request $request, AdministrationService $administration): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->json(['ok' => true]);
        }

        try {
            $action = (string) $request->query('action', 'bootstrap');

            if ($action === 'health') {
                return $this->json(['ok' => true, 'mode' => 'laravel']);
            }

            $administration->ensureDefaults();

            $user = $request->user();
            if (! $user) {
                return $this->json(['ok' => false, 'error' => 'Utilisateur CRM requis'], 401);
            }

            $actor = $administration->actorForUser($user);
            $body = $this->body($request);

            return match ($action) {
                'profile' => $this->json($administration->profile($actor)),
                'save_profile' => $this->json($administration->saveProfile($actor, $body)),
                'bootstrap' => $this->json($administration->bootstrap($actor)),
                'save_menu_settings' => $this->json($administration->saveMenuSettings($actor, $body)),
                'pages_bootstrap' => $this->json($administration->pagesBootstrap($actor)),
                'save_page' => $this->json($administration->savePage($actor, $body)),
                'delete_page' => $this->json($administration->deletePage($actor, $body)),
                'save_site' => $this->json($administration->saveSite($actor, $body)),
                'delete_site' => $this->json($administration->deleteSite($actor, $body)),
                'save_module' => $this->json($administration->saveModule($actor, $body)),
                'save_user' => $this->json($administration->saveUser($actor, $body)),
                default => $this->json(['ok' => false, 'error' => 'Action inconnue'], 404),
            };
        } catch (HttpExceptionInterface $error) {
            return $this->json(['ok' => false, 'error' => $error->getMessage()], $error->getStatusCode());
        } catch (Throwable $error) {
            Log::error('[crm-administration] '.$error->getMessage(), ['exception' => $error]);

            return $this->json([
                'ok' => false,
                'error' => config('app.debug') ? $error->getMessage() : 'Erreur API administration',
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
            ->withHeaders([
                'Access-Control-Allow-Headers' => 'Content-Type, X-CSRF-TOKEN, X-XSRF-TOKEN',
                'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            ]);
    }
}
