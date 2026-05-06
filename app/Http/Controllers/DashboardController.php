<?php

namespace App\Http\Controllers;

use App\Services\LunarCalendarService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    // Ngày Tam Nương và Nguyệt Kỵ (âm lịch)
    private const TAM_NUONG = [3, 7, 13, 18, 22, 27];
    private const NGUYET_KY = [5, 14, 23];

    public function __construct(
        private readonly LunarCalendarService $lunar,
        private readonly SubscriptionService  $subscription,
    ) {}

    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user  = Auth::user()->fresh();
        $group = active_group();

        $eventCount     = $group->memorialEvents()->count();
        $recipientCount = $this->subscription->recipientCount($user);
        $recipientLimit = $this->subscription->recipientLimit($user);
        $prayerCount    = $group->prayers()->count();
        $znsUsed        = $this->subscription->znsUsed($user);
        $znsLimit       = $this->subscription->znsLimit($user);
        $plan           = $this->subscription->plan($user);

        $upcoming = $group->memorialEvents()
            ->with('familyMember')
            ->active()
            ->whereNotNull('solar_date_next')
            ->orderBy('solar_date_next')
            ->limit(5)
            ->get()
            ->map(function ($e) {
                $e->days_until = $this->lunar->daysUntil($e->solar_date_next);
                return $e;
            })
            ->filter(fn ($e) => $e->days_until >= 0 && $e->days_until <= 365);

        // ── Calendar âm lịch tháng này ─────────────────────────────────────
        $calendar = $this->buildCalendar($group);

        return view('dashboard', compact(
            'user', 'plan', 'group',
            'eventCount', 'recipientCount', 'recipientLimit', 'prayerCount',
            'znsUsed', 'znsLimit', 'upcoming',
            'calendar',
        ));
    }

    /**
     * Build dữ liệu calendar tháng hiện tại.
     * Mỗi ngày có: solar date, lunar info, day quality, giỗ events.
     */
    private function buildCalendar($group): array
    {
        $tz    = 'Asia/Ho_Chi_Minh';
        $today = Carbon::now($tz)->startOfDay();
        $start = $today->copy()->startOfMonth();
        $end   = $today->copy()->endOfMonth();

        // Load all events trong tháng này (solar_date_next)
        $monthEvents = $group->memorialEvents()
            ->with('familyMember')
            ->active()
            ->whereNotNull('solar_date_next')
            ->whereBetween('solar_date_next', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn ($e) => $e->solar_date_next->format('Y-m-d'));

        $days = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $info    = $this->lunar->solarToLunarInfo($cursor);
            $quality = $this->dayQuality($info['day']);
            $events  = $monthEvents->get($cursor->format('Y-m-d'), collect());

            $days[] = [
                'date'        => $cursor->copy(),
                'is_today'    => $cursor->isToday(),
                'lunar_day'   => $info['day'],
                'lunar_month' => $info['month'],
                'stem_day'    => $info['stem_day'],
                'branch_day'  => $info['branch_day'],
                'quality'     => $quality,
                'events'      => $events,
            ];

            $cursor->addDay();
        }

        return [
            'days'       => $days,
            'month'      => $today->month,
            'year'       => $today->year,
            'start_dow'  => $start->dayOfWeekIso % 7, // 0=Mon offset for CSS grid
            'today_lunar'=> $this->lunar->solarToLunarInfo($today),
        ];
    }

    private function dayQuality(int $lunarDay): string
    {
        if ($lunarDay === 1 || $lunarDay === 15) {
            return 'good';
        }
        if (in_array($lunarDay, self::TAM_NUONG, true)) {
            return 'bad';
        }
        if (in_array($lunarDay, self::NGUYET_KY, true)) {
            return 'caution';
        }
        return 'normal';
    }
}
