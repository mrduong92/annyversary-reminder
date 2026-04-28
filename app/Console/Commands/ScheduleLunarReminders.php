<?php

namespace App\Console\Commands;

use App\Jobs\SendZnsReminderJob;
use App\Models\FamilyGroup;
use App\Models\MemorialEvent;
use App\Models\Recipient;
use App\Services\LunarCalendarService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ScheduleLunarReminders extends Command
{
    protected $signature = 'zns:lunar-reminders';
    protected $description = 'Gửi ZNS nhắc Rằm (15) và Mùng 1 âm lịch hàng tháng';

    public function __construct(
        private readonly LunarCalendarService $lunar,
        private readonly SubscriptionService $subscriptionService,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $today        = Carbon::now('Asia/Ho_Chi_Minh');
        $dispatched   = 0;

        // Xác định ngày âm lịch hôm nay
        [$lunarDay, $lunarMonth] = $this->getLunarDayMonth($today);

        $isRam     = $lunarDay === 15;
        $isMungMot = $lunarDay === 1;

        if (! $isRam && ! $isMungMot) {
            $this->info("Hôm nay ({$today->toDateString()}) là ngày âm lịch {$lunarDay}/{$lunarMonth} — không phải Rằm hay Mùng 1.");
            return;
        }

        $label = $isRam ? "Rằm tháng {$lunarMonth} âm lịch" : "Mùng 1 tháng {$lunarMonth} âm lịch";
        $this->info("Hôm nay: {$label} ({$today->toDateString()})");

        // Lấy tất cả family groups đã bật tính năng tương ứng
        $groups = FamilyGroup::query()
            ->when($isRam,     fn ($q) => $q->where('remind_ram', true))
            ->when($isMungMot, fn ($q) => $q->where('remind_mung_mot', true))
            ->with('user')
            ->get();

        foreach ($groups as $group) {
            $user = $group->user;

            if (! $this->subscriptionService->canSendZns($user)) {
                $this->warn("User {$user->id} ({$user->name}) đã hết ZNS tháng này.");
                continue;
            }

            // Lấy tất cả recipients của group
            $recipients = DB::table('recipients')
                ->join('event_recipient', 'recipients.id', '=', 'event_recipient.recipient_id')
                ->join('memorial_events', 'event_recipient.memorial_event_id', '=', 'memorial_events.id')
                ->where('memorial_events.family_group_id', $group->id)
                ->where('recipients.is_active', true)
                ->distinct()
                ->pluck('recipients.id');

            if ($recipients->isEmpty()) {
                continue;
            }

            // Tạo một "virtual event" cho Rằm/Mùng 1 — dùng làm context ZNS
            // Trong thực tế cần 1 ZNS template riêng cho Rằm/Mùng 1
            // Hiện tại: dispatch job với first event của group làm placeholder
            $firstEvent = MemorialEvent::where('family_group_id', $group->id)->first();
            if (! $firstEvent) continue;

            foreach ($recipients as $recipientId) {
                $recipient = Recipient::find($recipientId);
                if (! $recipient) continue;

                // TODO: ZNS template riêng cho Rằm/Mùng 1 khi có credentials
                // SendZnsLunarReminderJob::dispatch($group, $recipient, $label);
                $dispatched++;
                $this->line("  → {$recipient->name} ({$recipient->phone}) — {$label}");
            }

            $this->subscriptionService->incrementZnsCount($user);
        }

        $this->info("Hoàn tất. Sẽ gửi {$dispatched} ZNS nhắc {$label}.");
    }

    /**
     * Lấy ngày và tháng âm lịch của ngày dương lịch cho trước.
     * Dùng cách thử: tìm ngày âm có solar_date trùng với today.
     */
    private function getLunarDayMonth(Carbon $solar): array
    {
        // Convert solar → lunar bằng cách thử từng ngày âm trong tháng
        // Cách đơn giản: dùng thư viện để convert ngược
        // LunarCalendarService hiện chỉ có lunar→solar
        // Nên dùng approach: check nếu today là lunar 1 hoặc 15
        // bằng cách convert lunar 1 và 15 của tháng hiện tại sang solar

        $solarYear  = $solar->year;
        $solarMonth = $solar->month;

        // Thử tháng âm tương ứng (approximate: tháng âm = tháng dương ± 1)
        foreach (range(-1, 1) as $offset) {
            $lunarMonth = $solarMonth + $offset;
            if ($lunarMonth < 1) { $lunarMonth = 12; }
            if ($lunarMonth > 12) { $lunarMonth = 1; }

            $lunarYear = $solarYear;
            if ($offset === -1 && $solarMonth === 1) $lunarYear--;
            if ($offset ===  1 && $solarMonth === 12) $lunarYear++;

            foreach ([1, 15] as $lunarDay) {
                $candidate = $this->lunar->lunarToSolar($lunarDay, $lunarMonth, $lunarYear);
                if ($candidate->isSameDay($solar)) {
                    return [$lunarDay, $lunarMonth];
                }
            }
        }

        // Fallback: không phải Rằm hay Mùng 1
        return [0, 0];
    }
}
