<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CollectionSmsService
{
    public function __construct(
        protected OtpService $otpService,
    ) {}

    public function send(string $phone, string $message): bool
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from');

        if (! $sid || ! $token || ! $from) {
            Log::info('Collection SMS simulated (Twilio not configured)', [
                'to' => $phone,
                'body' => $message,
            ]);

            return true;
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($sid, $token)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => $from,
                    'To' => $this->otpService->normalizePhone($phone),
                    'Body' => $message,
                ]);

            if (! $response->successful()) {
                Log::warning('Collection SMS send failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Collection SMS exception', ['message' => $e->getMessage()]);

            return false;
        }
    }
}
