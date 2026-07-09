<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Services\Crm\LeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class LeaveApiController extends Controller
{
    public function __invoke(Request $request, LeaveService $leaves): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->json(['ok' => true]);
        }

        try {
            $user = $request->user();

            if (! $user) {
                return $this->json(['ok' => false, 'error' => 'Utilisateur CRM requis'], 401);
            }

            $actor = $leaves->actorForUser($user);
            $action = (string) $request->query('action', 'bootstrap');
            $body = $this->body($request);

            return match ($action) {
                'bootstrap' => $this->json($leaves->bootstrap($actor)),
                'save_employee' => $this->json($leaves->saveEmployee($actor, $body)),
                'delete_employee' => $this->json($leaves->deleteEmployee($actor, $body)),
                'save_leave' => $this->json($leaves->saveLeave($actor, $body)),
                'delete_leave' => $this->json($leaves->deleteLeave($actor, $body)),
                default => $this->json(['ok' => false, 'error' => 'Action inconnue'], 404),
            };
        } catch (HttpExceptionInterface $error) {
            return $this->json(['ok' => false, 'error' => $error->getMessage()], $error->getStatusCode());
        } catch (Throwable $error) {
            Log::error('[crm-leaves] '.$error->getMessage(), ['exception' => $error]);

            return $this->json([
                'ok' => false,
                'error' => config('app.debug') ? $error->getMessage() : 'Erreur API conges',
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
