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

        return view('dashboard', compact(
            'user', 'plan', 'group',
            'eventCount', 'recipientCount', 'recipientLimit', 'prayerCount',
            'znsUsed', 'znsLimit', 'upcoming',
        ));
    }
}
