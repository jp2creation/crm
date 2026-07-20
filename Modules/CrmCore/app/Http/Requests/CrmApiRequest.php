<?php

namespace Modules\CrmCore\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CrmApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'action' => ['sometimes', 'string', 'max:80'],
            'siteId' => ['sometimes', 'integer', 'min:1'],
            'site_id' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action.string' => 'Action invalide.',
            'action.max' => 'Action trop longue.',
            'siteId.integer' => 'Site invalide.',
            'siteId.min' => 'Site invalide.',
            'site_id.integer' => 'Site invalide.',
            'site_id.min' => 'Site invalide.',
        ];
    }

    public function action(string $default = 'bootstrap'): string
    {
        return (string) $this->query('action', $default);
    }

    /**
     * @return array<string, mixed>
     */
    public function body(): array
    {
        $json = $this->json()->all();

        if ($json !== []) {
            return $json;
        }

        return $this->request->all();
    }

    public function siteId(array $body = []): ?int
    {
        $value = $this->query('siteId')
            ?? $this->query('site_id')
            ?? $body['siteId']
            ?? $body['site_id']
            ?? null;

        $siteId = (int) $value;

        return $siteId > 0 ? $siteId : null;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()
                ->json([
                    'ok' => false,
                    'error' => $validator->errors()->first(),
                ], 422, [], JSON_UNESCAPED_UNICODE)
        );
    }
}
