<?php

namespace Modules\CrmCheckRemittances\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

class CheckImageOcrService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function detect(array $data): array
    {
        if (! (bool) config('crm.check_ocr.enabled', true)) {
            return $this->unavailable('OCR serveur desactive');
        }

        $script = (string) config('crm.check_ocr.script');
        if ($script === '' || ! File::exists($script)) {
            return $this->unavailable('Script OCR introuvable');
        }

        $image = trim((string) ($data['photoDataUrl'] ?? $data['photo_data_url'] ?? ''));
        if ($image === '') {
            return $this->unavailable('Photo requise');
        }

        $tempDir = storage_path('app/check-ocr');
        if (! File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true, true);
        }

        $imagePath = $tempDir.'/'.Str::uuid().'.'.$this->extensionFromDataUrl($image);
        $knownNamesPath = $tempDir.'/'.Str::uuid().'-names.json';

        try {
            File::put($imagePath, $this->decodeDataUrl($image));
            File::put($knownNamesPath, json_encode($this->knownNames($data), JSON_UNESCAPED_UNICODE));

            $process = new Process([
                (string) config('crm.check_ocr.python', 'python'),
                $script,
                '--image',
                $imagePath,
                '--known-names',
                $knownNamesPath,
            ]);
            $process->setTimeout(max(5, (int) config('crm.check_ocr.timeout', 45)));
            $process->run();

            $payload = json_decode($process->getOutput(), true);
            if (! is_array($payload)) {
                return $this->unavailable(trim($process->getErrorOutput()) ?: 'OCR serveur indisponible');
            }

            if (! ($payload['engine_available'] ?? false)) {
                return $this->unavailable((string) ($payload['error'] ?? 'Moteur OCR indisponible'));
            }

            return [
                'engineAvailable' => true,
                'engine' => (string) ($payload['engine'] ?? 'paddleocr'),
                'payerName' => $this->cleanText($payload['payer_name'] ?? ''),
                'amount' => $this->cleanAmount($payload['amount'] ?? ''),
                'ocrText' => $this->cleanText($payload['ocr_text'] ?? ''),
                'ocrConfidence' => $this->confidence($payload['confidence'] ?? null),
                'visualChecks' => [
                    'signature' => $this->nullableBool($payload['visual_checks']['signature'] ?? null),
                    'order' => $this->nullableBool($payload['visual_checks']['order'] ?? null),
                ],
                'micr' => $this->cleanText($payload['micr'] ?? ''),
            ];
        } catch (Throwable $error) {
            return $this->unavailable($error->getMessage());
        } finally {
            File::delete([$imagePath, $knownNamesPath]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function unavailable(string $message): array
    {
        return [
            'engineAvailable' => false,
            'error' => $message,
        ];
    }

    private function decodeDataUrl(string $dataUrl): string
    {
        if (! preg_match('/^data:image\/(?:png|jpe?g|webp);base64,/', $dataUrl)) {
            throw new \RuntimeException('Photo OCR invalide');
        }

        $binary = base64_decode(substr($dataUrl, (int) strpos($dataUrl, ',') + 1), true);
        if ($binary === false || strlen($binary) > 10 * 1024 * 1024) {
            throw new \RuntimeException('Photo OCR trop lourde');
        }

        return $binary;
    }

    private function extensionFromDataUrl(string $dataUrl): string
    {
        if (str_starts_with($dataUrl, 'data:image/png')) {
            return 'png';
        }

        if (str_starts_with($dataUrl, 'data:image/webp')) {
            return 'webp';
        }

        return 'jpg';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    private function knownNames(array $data): array
    {
        $names = $data['knownPayerNames'] ?? $data['known_payer_names'] ?? [];
        if (! is_array($names)) {
            return [];
        }

        return collect($names)
            ->map(fn (mixed $name): string => $this->cleanText($name))
            ->filter(fn (string $name): bool => mb_strlen($name) >= 4)
            ->unique()
            ->values()
            ->all();
    }

    private function cleanText(mixed $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $value));
    }

    private function cleanAmount(mixed $value): string
    {
        $amount = str_replace(["\xc2\xa0", ' '], '', trim((string) $value));
        $amount = str_replace(',', '.', $amount);
        if (! is_numeric($amount)) {
            return '';
        }

        return number_format((float) $amount, 2, ',', '');
    }

    private function confidence(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
    }

    private function nullableBool(mixed $value): ?bool
    {
        return is_bool($value) ? $value : null;
    }
}
