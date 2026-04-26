<?php

namespace App\Http\Controllers;

use App\Services\LunarCalendarService;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly LunarCalendarService $lunar,
        private readonly SubscriptionService $subscription,
    ) {}

    public function index(): View
    {
        $user   = Auth::user()->fresh();
        $plan   = $user->subscription_plan ?? 'free';
        $limits = ['free' => ['events'=>3,'recipients'=>2,'zns'=>5], 'basic' => ['events'=>10,'recipients'=>10,'zns'=>30], 'unlimited' => ['events'=>PHP_INT_MAX,'recipients'=>PHP_INT_MAX,'zns'=>PHP_INT_MAX]];

        $eventCount     = $user->memorialEvents()->count();
        $recipientCount = $user->recipients()->count();
        $prayerCount    = $user->prayers()->count();
        $znsUsed        = $user->zns_count_this_month;
        $znsLimit       = $limits[$plan]['zns'];

        // Các ngày giỗ sắp tới (30 ngày tới), sort theo solar_date_next
        $upcoming = $user->memorialEvents()
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

        return view('dashboard', compact(
            'user', 'plan', 'limits',
            'eventCount', 'recipientCount', 'prayerCount',
            'znsUsed', 'znsLimit', 'upcoming',
        ));
    }
}
