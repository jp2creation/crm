<?php

namespace Modules\CrmReservations\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\CrmCore\Http\Requests\CrmApiRequest;
use Modules\CrmReservations\Services\ReservationService;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ReservationApiController extends Controller
{
    public function __invoke(CrmApiRequest $request, ReservationService $reservations): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->json(['ok' => true]);
        }

        try {
            $action = $request->action();

            if ($action === 'health') {
                return $this->json(['ok' => true, 'mode' => 'mysql']);
            }

            $user = $this->authenticatedApiUser($request);

            if (! $user) {
                return $this->json(['ok' => false, 'error' => 'Utilisateur CRM requis'], 401);
            }

            $actor = $reservations->actorForUser($user);
            $body = $request->body();

            return match ($action) {
                'bootstrap' => $this->json($reservations->bootstrap($actor)),
                'create_reservation' => $this->json($reservations->createReservation($actor, $body)),
                'update_reservation' => $this->json($reservations->updateReservation($actor, $body)),
                'save_vehicle' => $this->json($reservations->saveVehicle($actor, $body)),
                'delete_vehicle' => $this->json($reservations->deleteVehicle($actor, $body)),
                'delete_reservation' => $this->json($reservations->deleteReservation($actor, $body)),
                default => $this->json(['ok' => false, 'error' => 'Action inconnue'], 404),
            };
        } catch (HttpExceptionInterface $error) {
            return $this->json(['ok' => false, 'error' => $error->getMessage()], $error->getStatusCode());
        } catch (ValidationException $error) {
            return $this->json(['ok' => false, 'error' => $error->validator->errors()->first()], 400);
        } catch (Throwable $error) {
            Log::error('[crm-reservations] '.$error->getMessage(), ['exception' => $error]);

            return $this->json([
                'ok' => false,
                'error' => config('app.debug') ? $error->getMessage() : 'Erreur API reservations',
            ], 500);
        }
    }

    private function json(array $data, int $status = 200): JsonResponse
    {
        return response()
            ->json($data, $status, [], JSON_UNESCAPED_UNICODE)
            ->withHeaders($this->crmApiHeaders());
    }
}
