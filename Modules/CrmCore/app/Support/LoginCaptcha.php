<?php

namespace Modules\CrmCore\Support;

use Illuminate\Http\Request;

final class LoginCaptcha
{
    public const SESSION_KEY = 'crm_login_captcha';

    /**
     * @return array{question: string, answer_hash: string}
     */
    public static function ensure(Request $request): array
    {
        $challenge = $request->session()->get(self::SESSION_KEY);

        if (self::isChallenge($challenge)) {
            return $challenge;
        }

        return self::regenerate($request);
    }

    /**
     * @return array{question: string, answer_hash: string}
     */
    public static function regenerate(Request $request): array
    {
        $challenge = self::makeChallenge(random_int(2, 9), random_int(2, 9));

        $request->session()->put(self::SESSION_KEY, $challenge);

        return $challenge;
    }

    public static function forget(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    public static function passes(Request $request, mixed $answer): bool
    {
        $challenge = $request->session()->get(self::SESSION_KEY);

        if (! self::isChallenge($challenge)) {
            return false;
        }

        return hash_equals($challenge['answer_hash'], self::hashAnswer($answer));
    }

    /**
     * @return array{question: string, answer_hash: string}
     */
    public static function makeChallenge(int $left, int $right): array
    {
        return [
            'question' => "{$left} + {$right}",
            'answer_hash' => self::hashAnswer($left + $right),
        ];
    }

    private static function hashAnswer(mixed $answer): string
    {
        return hash_hmac(
            'sha256',
            trim((string) $answer),
            (string) config('app.key'),
        );
    }

    /**
     * @phpstan-assert-if-true array{question: string, answer_hash: string} $challenge
     */
    private static function isChallenge(mixed $challenge): bool
    {
        return is_array($challenge)
            && isset($challenge['question'], $challenge['answer_hash'])
            && is_string($challenge['question'])
            && is_string($challenge['answer_hash']);
    }
}
