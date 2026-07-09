<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Services\Crm\EquipmentRentalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class EquipmentRentalApiController extends Controller
{
    public function __invoke(Request $request, EquipmentRentalService $rentals): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->json(['ok' => true]);
        }

        try {
            $action = (string) $request->query('action', 'bootstrap');

            if ($action === 'health') {
                return $this->json(['ok' => true, 'mode' => 'mysql']);
            }

            $user = $request->user();

            if (! $user) {
                return $this->json(['ok' => false, 'error' => 'Utilisateur CRM requis'], 401);
            }

            $actor = $rentals->actorForUser($user);
            $body = $this->body($request);

            return match ($action) {
                'bootstrap' => $this->json($rentals->bootstrap($actor)),
                'create_rental' => $this->json($rentals->createRental($actor, $body)),
                'update_rental' => $this->json($rentals->updateRental($actor, $body)),
                'delete_rental' => $this->json($rentals->deleteRental($actor, $body)),
                'save_equipment_item' => $this->json($rentals->saveEquipmentItem($actor, $body)),
                'delete_equipment_item' => $this->json($rentals->deleteEquipmentItem($actor, $body)),
                default => $this->json(['ok' => false, 'error' => 'Action inconnue'], 404),
            };
        } catch (HttpExceptionInterface $error) {
            return $this->json(['ok' => false, 'error' => $error->getMessage()], $error->getStatusCode());
        } catch (ValidationException $error) {
            return $this->json(['ok' => false, 'error' => $error->validator->errors()->first()], 400);
        } catch (Throwable $error) {
            Log::error('[crm-equipment-rentals] '.$error->getMessage(), ['exception' => $error]);

            return $this->json([
                'ok' => false,
                'error' => config('app.debug') ? $error->getMessage() : 'Erreur API location materiel',
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
