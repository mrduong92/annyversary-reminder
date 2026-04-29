<?php

namespace App\Console\Commands;

use App\Jobs\SendZnsReminderJob;
use App\Models\MemorialEvent;
use App\Models\Recipient;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ScheduleZnsReminders extends Command
{
    protected $signature = 'zns:schedule-reminders';
    protected $description = 'Dispatch ZNS reminder jobs for upcoming memorial events';

    public function __construct(private readonly SubscriptionService $subscriptionService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $reminderDays = array_map('intval', explode(',', config('app.reminder_days_before', '1,3')));
        $today        = Carbon::now('Asia/Ho_Chi_Minh')->toDateString();
        $dispatched   = 0;

        foreach ($reminderDays as $daysAhead) {
            $targetDate = Carbon::now('Asia/Ho_Chi_Minh')->addDays($daysAhead)->toDateString();

            $events = MemorialEvent::with('user')
                ->active()
                ->where('solar_date_next', $targetDate)
                ->get();

            foreach ($events as $event) {
                if (! $this->subscriptionService->canSendZns($event->user)) {
                    $this->warn("User {$event->user_id} đã hết ZNS quota, bỏ qua event {$event->id}");
                    continue;
                }

                // Lấy TẤT CẢ recipients của family_group — áp dụng toàn bộ ngày giỗ
                $recipients = Recipient::where('family_group_id', $event->family_group_id)
                    ->where('is_active', true)
                    ->get();

                foreach ($recipients as $recipient) {
                    $effectiveDays = $recipient->notify_days_before ?? [1];

                    if (in_array($daysAhead, (array) $effectiveDays)) {
                        SendZnsReminderJob::dispatch($event, $recipient);
                        $dispatched++;
                    }
                }
            }
        }

        $this->info("Dispatched {$dispatched} ZNS reminder jobs for {$today}");
    }
}
