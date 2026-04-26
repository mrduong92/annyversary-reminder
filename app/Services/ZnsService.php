<?php

namespace App\Services;

use App\Models\MemorialEvent;
use App\Models\NotificationLog;
use App\Models\Recipient;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Log;

class ZnsService
{
    public function __construct(private readonly HttpClient $http) {}

    public function send(MemorialEvent $event, Recipient $recipient): bool
    {
        $log = NotificationLog::create([
            'user_id' => $event->user_id,
            'memorial_event_id' => $event->id,
            'recipient_id' => $recipient->id,
            'zns_template_id' => config('services.zalo.zns_template_id'),
            'status' => 'pending',
        ]);

        try {
            $response = $this->http->withToken(config('services.zalo.oa_access_token'))
                ->post(config('services.zalo.zns_endpoint'), [
                    'phone' => $recipient->phone,
                    'template_id' => config('services.zalo.zns_template_id'),
                    'template_data' => [
                        'ten_nguoi_mat' => $event->name,
                        'ngay_gio' => "{$event->lunar_day}/{$event->lunar_month} âm lịch",
                        'ngay_duong_lich' => $event->solar_date_next?->format('d/m/Y'),
                        'ten_nguoi_dung' => $event->user->name,
                    ],
                    'tracking_id' => "log_{$log->id}",
                ]);

            $data = $response->json();

            if ($response->successful() && isset($data['data']['msg_id'])) {
                $log->update([
                    'status' => 'sent',
                    'zns_message_id' => $data['data']['msg_id'],
                    'sent_at' => now(),
                ]);

                return true;
            }

            $log->update([
                'status' => 'failed',
                'error_message' => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('ZNS send failed', ['event_id' => $event->id, 'error' => $e->getMessage()]);
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);

            return false;
        }
    }
}
