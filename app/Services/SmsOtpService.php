<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsOtpService
{
    private string $accessToken;
    private string $sender;

    public function __construct()
    {
        $this->accessToken = config('services.speedsms.access_token', '');
        $this->sender = config('services.speedsms.sender', 'VTDD');
    }

    public function send(string $phone, string $otp): bool
    {
        $message = "Ma OTP cua ban la: {$otp}. Het han sau 5 phut. Khong chia se ma nay cho bat ky ai.";

        try {
            $response = Http::withBasicAuth($this->accessToken, ':')
                ->post('https://api.speedsms.vn/index.php/sms/send', [
                    'to' => [$phone],
                    'content' => $message,
                    'sms_type' => 2,
                    'sender' => $this->sender,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return ($data['status'] ?? '') === 'success';
            }

            Log::warning('SpeedSMS send failed', ['phone' => $phone, 'response' => $response->body()]);
            return false;
        } catch (\Throwable $e) {
            Log::error('SpeedSMS exception', ['phone' => $phone, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
