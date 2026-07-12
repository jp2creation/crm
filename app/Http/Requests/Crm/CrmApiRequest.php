<?php

namespace App\Http\Requests\Crm;

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
                ], 400, [], JSON_UNESCAPED_UNICODE)
                ->withHeaders([
                    'Access-Control-Allow-Origin' => $this->headers->get('Origin', '*'),
                    'Access-Control-Allow-Headers' => 'Accept, Authorization, Content-Type, X-CSRF-TOKEN, X-Requested-With, X-XSRF-TOKEN',
                    'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
                    'Vary' => 'Origin',
                ])
        );
    }
}
