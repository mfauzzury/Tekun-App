<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RequestOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Traits\ApiResponse;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;

class OtpController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected OtpService $otpService,
    ) {}

    public function request(RequestOtpRequest $request): JsonResponse
    {
        $channel = $request->validated('channel');
        $identifier = $this->identifierFor($channel, $request);
        $code = $this->otpService->generate();

        $sent = $channel === 'sms'
            ? $this->otpService->sendSms($identifier, $code)
            : $this->otpService->sendEmail($identifier, $code);

        if (! $sent) {
            $message = $channel === 'sms'
                ? 'Gagal menghantar OTP melalui SMS. Sila cuba lagi.'
                : 'Gagal menghantar OTP melalui emel. Sila cuba lagi.';

            return $this->sendError(502, 'OTP_SEND_FAILED', $message);
        }

        $this->otpService->store("{$channel}:{$identifier}", $code);

        return $this->sendOk(['sent' => true]);
    }

    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $channel = $request->validated('channel');
        $identifier = $this->identifierFor($channel, $request);
        $code = $request->validated('code');

        if (! $this->otpService->verify("{$channel}:{$identifier}", $code)) {
            return $this->sendError(422, 'INVALID_OTP', 'Kod OTP tidak sah atau telah tamat tempoh.');
        }

        return $this->sendOk(['verified' => true]);
    }

    protected function identifierFor(string $channel, RequestOtpRequest|VerifyOtpRequest $request): string
    {
        return $channel === 'sms'
            ? $this->otpService->normalizePhone($request->validated('telefon'))
            : strtolower(trim($request->validated('email')));
    }
}
