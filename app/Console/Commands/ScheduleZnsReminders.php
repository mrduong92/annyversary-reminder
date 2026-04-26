<?php

namespace App\Console\Commands;

use App\Jobs\SendZnsReminderJob;
use App\Models\MemorialEvent;
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
        $reminderDays = array_map(
            'intval',
            explode(',', config('app.reminder_days_before', '1,3'))
        );

        $today      = Carbon::now('Asia/Ho_Chi_Minh')->toDateString();
        $dispatched = 0;

        foreach ($reminderDays as $daysAhead) {
            $targetDate = Carbon::now('Asia/Ho_Chi_Minh')->addDays($daysAhead)->toDateString();

            $events = MemorialEvent::with([
                    'user',
                    // Chỉ load recipient đang active, kèm pivot notify_days_before
                    'recipients' => fn ($q) => $q->active()->withPivot('notify_days_before'),
                ])
                ->active()
                ->where('solar_date_next', $targetDate)
                ->get();

            foreach ($events as $event) {
                if (! $this->subscriptionService->canSendZns($event->user)) {
                    $this->warn("User {$event->user_id} reached ZNS limit, skipping event {$event->id}");
                    continue;
                }

                foreach ($event->recipients as $recipient) {
                    // Dùng notify_days_before của pivot nếu có, fallback về default của recipient
                    $pivotDays = $recipient->pivot->notify_days_before
                        ? json_decode($recipient->pivot->notify_days_before, true)
                        : null;

                    $effectiveDays = $recipient->effectiveDays($pivotDays);

                    // Chỉ dispatch nếu daysAhead nằm trong danh sách nhắc của recipient này
                    if (in_array($daysAhead, $effectiveDays)) {
                        SendZnsReminderJob::dispatch($event, $recipient);
                        $dispatched++;
                    }
                }
            }
        }

        $this->info("Dispatched {$dispatched} ZNS reminder jobs for {$today}");
    }
}
