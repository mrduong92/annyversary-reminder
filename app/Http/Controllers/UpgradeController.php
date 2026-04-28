<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UpgradeController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscription) {}

    public function index(): View
    {
        $user = Auth::user();
        return view('upgrade.index', [
            'user'        => $user,
            'currentPlan' => $this->subscription->plan($user),
            'znsUsed'     => $user->zns_count_this_year ?? 0,
            'znsLimit'    => $this->subscription->znsLimit($user),
        ]);
    }
}
