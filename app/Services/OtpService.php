<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OtpService
{
    protected const TTL_MINUTES = 5;

    public function generate(): string
    {
        return (string) random_int(100000, 999999);
    }

    public function store(string $identifier, string $code): void
    {
        Cache::put($this->cacheKey($identifier), $code, now()->addMinutes(self::TTL_MINUTES));
    }

    public function verify(string $identifier, string $code): bool
    {
        $stored = Cache::get($this->cacheKey($identifier));

        if ($stored !== null && hash_equals($stored, $code)) {
            Cache::forget($this->cacheKey($identifier));

            return true;
        }

        return false;
    }

    /**
     * Normalize a Malaysian local number (e.g. "012-3456789") to E.164 (+60123456789)
     * as required by Twilio's "To" field. Numbers already in E.164 pass through unchanged.
     */
    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($digits, '+')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '+60'.substr($digits, 1);
        }

        return '+60'.$digits;
    }

    public function sendSms(string $phone, string $code): bool
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from');

        try {
            $response = Http::asForm()
                ->withBasicAuth($sid, $token)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => $from,
                    'To' => $this->normalizePhone($phone),
                    'Body' => "Kod pengesahan Portal Pemohon SPPT anda: {$code}. Sah selama ".self::TTL_MINUTES.' minit.',
                ]);

            if (! $response->successful()) {
                Log::warning('Twilio SMS send failed', ['status' => $response->status(), 'body' => $response->json()]);
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Twilio SMS send exception', ['message' => $e->getMessage()]);

            return false;
        }
    }

    public function sendEmail(string $email, string $code): bool
    {
        $apiKey = config('services.resend.key');
        $from = config('services.resend.from');

        try {
            $response = Http::withToken($apiKey)
                ->post('https://api.resend.com/emails', [
                    'from' => $from,
                    'to' => [$email],
                    'subject' => 'Kod Pengesahan Portal Pemohon SPPT',
                    'html' => "<p>Kod pengesahan anda: <strong>{$code}</strong></p><p>Kod ini sah selama ".self::TTL_MINUTES.' minit.</p>',
                ]);

            if (! $response->successful()) {
                Log::warning('Resend email send failed', ['status' => $response->status(), 'body' => $response->json()]);
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Resend email send exception', ['message' => $e->getMessage()]);

            return false;
        }
    }

    protected function cacheKey(string $identifier): string
    {
        return "otp:{$identifier}";
    }
}
