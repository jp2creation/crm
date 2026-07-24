<?php

namespace Modules\CrmCore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\CrmCore\Support\LoginCaptcha;

class LoginRequest extends FormRequest
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
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'captcha_answer' => ['required', 'integer'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('captcha_answer')) {
                    LoginCaptcha::regenerate($this);

                    return;
                }

                if (! LoginCaptcha::passes($this, $this->input('captcha_answer'))) {
                    LoginCaptcha::regenerate($this);

                    $validator->errors()->add(
                        'captcha_answer',
                        'Réponse anti-robot incorrecte.',
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'captcha_answer.required' => 'Confirmez le contrôle anti-robot.',
            'captcha_answer.integer' => 'La réponse anti-robot doit être un nombre.',
        ];
    }
}
