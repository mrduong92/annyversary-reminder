<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class SubscriptionService
{
    private const LIMITS = [
        'free' => [
            'events' => 3,
            'recipients' => 2,
            'zns_per_month' => 5,
            'ai_prayers_per_month' => 3,
            'ai_messages_per_day' => 10,
            'shares' => 1,
            'documents' => 0,
        ],
        'basic' => [
            'events' => 10,
            'recipients' => 10,
            'zns_per_month' => 30,
            'ai_prayers_per_month' => PHP_INT_MAX,
            'ai_messages_per_day' => 50,
            'shares' => 3,
            'documents' => 3,
        ],
        'unlimited' => [
            'events' => PHP_INT_MAX,
            'recipients' => PHP_INT_MAX,
            'zns_per_month' => PHP_INT_MAX,
            'ai_prayers_per_month' => PHP_INT_MAX,
            'ai_messages_per_day' => PHP_INT_MAX,
            'shares' => PHP_INT_MAX,
            'documents' => PHP_INT_MAX,
        ],
    ];

    public function canAddEvent(User $user): bool
    {
        $limit = $this->getLimit($user, 'events');
        return $user->memorialEvents()->count() < $limit;
    }

    public function canAddRecipient(User $user): bool
    {
        $limit = $this->getLimit($user, 'recipients');
        return $user->recipients()->count() < $limit;
    }

    public function canSendZns(User $user): bool
    {
        $this->resetZnsCountIfNeeded($user);
        $limit = $this->getLimit($user, 'zns_per_month');
        return $user->zns_count_this_month < $limit;
    }

    public function canGeneratePrayer(User $user): bool
    {
        $this->resetZnsCountIfNeeded($user);
        $limit = $this->getLimit($user, 'ai_prayers_per_month');
        return $user->ai_prayer_count_this_month < $limit;
    }

    public function canSendAgentMessage(User $user): bool
    {
        $this->resetAiMessageCountIfNeeded($user);
        $limit = $this->getLimit($user, 'ai_messages_per_day');
        return $user->ai_message_count_today < $limit;
    }

    public function canCreateShare(User $user): bool
    {
        $limit = $this->getLimit($user, 'shares');
        return $user->familyShares()->where('is_active', true)->count() < $limit;
    }

    public function canUploadDocument(User $user): bool
    {
        $limit = $this->getLimit($user, 'documents');
        return $user->familyDocuments()->count() < $limit;
    }

    public function incrementZnsCount(User $user): void
    {
        $user->increment('zns_count_this_month');
    }

    public function incrementPrayerCount(User $user): void
    {
        $user->increment('ai_prayer_count_this_month');
    }

    public function incrementAgentMessageCount(User $user): void
    {
        $user->increment('ai_message_count_today');
    }

    private function getLimit(User $user, string $key): int
    {
        $plan = $user->subscription_plan ?? 'free';

        if ($plan !== 'free' && $user->subscription_expires_at?->isPast()) {
            $plan = 'free';
        }

        return self::LIMITS[$plan][$key] ?? 0;
    }

    private function resetZnsCountIfNeeded(User $user): void
    {
        $today = Carbon::now('Asia/Ho_Chi_Minh')->startOfMonth()->toDateString();

        if ($user->zns_count_reset_month?->toDateString() !== $today) {
            $user->update([
                'zns_count_this_month' => 0,
                'ai_prayer_count_this_month' => 0,
                'zns_count_reset_month' => $today,
            ]);
        }
    }

    private function resetAiMessageCountIfNeeded(User $user): void
    {
        $today = Carbon::now('Asia/Ho_Chi_Minh')->toDateString();

        if ($user->ai_message_reset_date?->toDateString() !== $today) {
            $user->update([
                'ai_message_count_today' => 0,
                'ai_message_reset_date' => $today,
            ]);
        }
    }
}
