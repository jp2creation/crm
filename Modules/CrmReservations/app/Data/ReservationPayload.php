<?php

namespace Modules\CrmReservations\Data;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class ReservationPayload
{
    public function __construct(
        public ?int $id,
        public int $vehicleId,
        public string $startAt,
        public string $endAt,
        public string $title,
        public string $contactPhone,
        public string $notes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public static function fromArray(array $data, bool $requireId = false): self
    {
        $normalized = [
            'id' => self::value($data, 'id'),
            'vehicleId' => self::value($data, 'vehicleId', 'vehicle_id'),
            'startAt' => self::value($data, 'startAt', 'start_at'),
            'endAt' => self::value($data, 'endAt', 'end_at'),
            'title' => self::value($data, 'title') ?? '',
            'contactPhone' => self::value($data, 'contactPhone', 'contact_phone') ?? '',
            'notes' => self::value($data, 'notes') ?? '',
        ];

        $rules = [
            'vehicleId' => ['required', 'integer', 'min:1'],
            'startAt' => ['required', 'string'],
            'endAt' => ['required', 'string'],
            'title' => ['nullable', 'string', 'max:190'],
            'contactPhone' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        if ($requireId) {
            $rules['id'] = ['required', 'integer', 'min:1'];
        } else {
            $rules['id'] = ['nullable', 'integer', 'min:1'];
        }

        $validator = Validator::make($normalized, $rules, [
            'id.required' => 'Reservation invalide',
            'id.integer' => 'Reservation invalide',
            'id.min' => 'Reservation invalide',
            'vehicleId.required' => 'Creneau invalide',
            'vehicleId.integer' => 'Creneau invalide',
            'vehicleId.min' => 'Creneau invalide',
            'startAt.required' => 'Date invalide',
            'endAt.required' => 'Date invalide',
            'title.max' => 'Titre trop long',
            'contactPhone.max' => 'Telephone trop long',
            'notes.max' => 'Notes trop longues',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                'payload' => $validator->errors()->first(),
            ]);
        }

        $startAt = self::normalizeDateTime((string) $normalized['startAt']);
        $endAt = self::normalizeDateTime((string) $normalized['endAt']);

        if (strtotime($endAt) <= strtotime($startAt)) {
            throw ValidationException::withMessages(['startAt' => 'Creneau invalide']);
        }

        return new self(
            id: $normalized['id'] !== null ? (int) $normalized['id'] : null,
            vehicleId: (int) $normalized['vehicleId'],
            startAt: $startAt,
            endAt: $endAt,
            title: trim((string) $normalized['title']),
            contactPhone: trim((string) $normalized['contactPhone']),
            notes: trim((string) $normalized['notes']),
        );
    }

    private static function value(array $data, string $primary, ?string $secondary = null): mixed
    {
        if (array_key_exists($primary, $data)) {
            return $data[$primary];
        }

        if ($secondary !== null && array_key_exists($secondary, $data)) {
            return $data[$secondary];
        }

        return null;
    }

    /**
     * @throws ValidationException
     */
    private static function normalizeDateTime(string $value): string
    {
        $value = str_replace('T', ' ', trim($value));

        if ($value === '') {
            throw ValidationException::withMessages(['startAt' => 'Date invalide']);
        }

        try {
            return CarbonImmutable::parse($value)->format('Y-m-d H:i:s');
        } catch (Throwable) {
            throw ValidationException::withMessages(['startAt' => 'Date invalide']);
        }
    }
}
