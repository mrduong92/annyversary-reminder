<?php

namespace App\Services;

use App\Models\User;

class SubscriptionService
{
    /**
     * Giới hạn theo plan — 2 gói: basic (free) và advanced (paid).
     *
     * Cả 2 gói đều không giới hạn ngày giỗ và gia phả.
     * Chỉ khác nhau ở ZNS/năm và AI tin nhắn/ngày.
     *
     * ZNS cost:
     *   basic:    60 tin/năm  × 300đ = 18.000đ/năm  (chi phí)
     *   advanced: 360 tin/năm × 300đ = 108.000đ/năm (chi phí, bù vào revenue 199k)
     */
    private const LIMITS = [
        'basic' => [
            'zns_events'          => PHP_INT_MAX, // không giới hạn
            'recipients'          => 2,
            'zns_per_year'        => 60,           // ~5 tin/tháng
            'ai_messages_per_day' => 10,
            'ai_prayers_per_month'=> PHP_INT_MAX,  // văn khấn AI miễn phí
            'shares'              => 1,
            'documents'           => 0,
            'lunar_special_days'        => false,
            'import_image'        => false,
            'can_crud_events_ai'  => false,
        ],
        'advanced' => [
            'zns_events'          => PHP_INT_MAX,
            'recipients'          => PHP_INT_MAX,
            'zns_per_year'        => 360,          // ~30 tin/tháng
            'ai_messages_per_day' => PHP_INT_MAX,
            'ai_prayers_per_month'=> PHP_INT_MAX,
            'shares'              => PHP_INT_MAX,
            'documents'           => 20,
            'lunar_special_days'        => true,
            'import_image'        => true,
            'can_crud_events_ai'  => true,
        ],
    ];

    // ── Plan helpers ──────────────────────────────────────────────────────────

    public function plan(User $user): string
    {
        $plan = $user->subscription_plan ?? 'basic';

        if ($plan === 'advanced' && $user->subscription_expires_at?->isPast()) {
            return 'basic';
        }

        return in_array($plan, ['basic', 'advanced']) ? $plan : 'basic';
    }

    public function isBasic(User $user): bool    { return $this->plan($user) === 'basic'; }
    public function isAdvanced(User $user): bool { return $this->plan($user) === 'advanced'; }
    public function isPaid(User $user): bool     { return $this->isAdvanced($user); }

    // Legacy aliases — giữ để không break code cũ gọi isFree/isPremium
    public function isFree(User $user): bool    { return $this->isBasic($user); }
    public function isPremium(User $user): bool { return $this->isAdvanced($user); }

    private function limit(User $user, string $key): mixed
    {
        return self::LIMITS[$this->plan($user)][$key] ?? 0;
    }

    // ── Feature gates ─────────────────────────────────────────────────────────

    public function canAddEvent(User $user): bool     { return true; } // không giới hạn cả 2 gói
    public function canAddGiapha(User $user): bool    { return true; } // không giới hạn gia phả

    public function canAddRecipient(User $user): bool
    {
        $max = $this->limit($user, 'recipients');
        return $max === PHP_INT_MAX || $user->recipients()->count() < $max;
    }

    public function canSendZns(User $user): bool
    {
        $this->resetYearlyZnsIfNeeded($user);
        return $user->zns_count_this_year < $this->limit($user, 'zns_per_year');
    }

    public function canGeneratePrayer(User $user): bool
    {
        return $this->limit($user, 'ai_prayers_per_month') > 0;
    }

    public function canSendAgentMessage(User $user): bool
    {
        $this->resetDailyAiIfNeeded($user);
        $max = $this->limit($user, 'ai_messages_per_day');
        return $max === PHP_INT_MAX || $user->ai_message_count_today < $max;
    }

    public function canCreateShare(User $user): bool
    {
        $max = $this->limit($user, 'shares');
        if ($max === 0) return false;
        if ($max === PHP_INT_MAX) return true;
        return $user->familyShares()->where('is_active', true)->count() < $max;
    }

    public function canUploadDocument(User $user): bool
    {
        $max = $this->limit($user, 'documents');
        if ($max === 0) return false;
        if ($max === PHP_INT_MAX) return true;
        return $user->familyDocuments()->count() < $max;
    }

    public function canUseLunarSpecialDays(User $user): bool  { return (bool) $this->limit($user, 'lunar_special_days'); }
    public function canImportImage(User $user): bool     { return (bool) $this->limit($user, 'import_image'); }
    public function canCrudEventsViaAi(User $user): bool { return (bool) $this->limit($user, 'can_crud_events_ai'); }

    // ── Increment counters ────────────────────────────────────────────────────

    public function incrementZnsCount(User $user): void            { $user->increment('zns_count_this_year'); }
    public function incrementPrayerCount(User $user): void         { $user->increment('ai_prayer_count_this_month'); }
    public function incrementAgentMessageCount(User $user): void   { $user->increment('ai_message_count_today'); }

    // ── Limit getters for UI ──────────────────────────────────────────────────

    public function znsLimit(User $user): int
    {
        $v = $this->limit($user, 'zns_per_year');
        return $v === PHP_INT_MAX ? 9999 : (int) $v;
    }

    public function znsUsed(User $user): int
    {
        $this->resetYearlyZnsIfNeeded($user);
        return $user->zns_count_this_year;
    }

    public function recipientLimit(User $user): int
    {
        $v = $this->limit($user, 'recipients');
        return $v === PHP_INT_MAX ? 9999 : (int) $v;
    }

    public function recipientCount(User $user): int  { return $user->recipients()->count(); }
    public function eventCount(User $user): int       { return $user->memorialEvents()->count(); }

    public function agentMessageLimit(User $user): int
    {
        $v = $this->limit($user, 'ai_messages_per_day');
        return $v === PHP_INT_MAX ? 9999 : (int) $v;
    }

    public function agentMessageCount(User $user): int
    {
        $this->resetDailyAiIfNeeded($user);
        return $user->ai_message_count_today;
    }

    public function shareCount(User $user): int    { return $user->familyShares()->where('is_active', true)->count(); }
    public function documentCount(User $user): int { return $user->familyDocuments()->count(); }

    public function documentLimit(User $user): int
    {
        $v = $this->limit($user, 'documents');
        return $v === PHP_INT_MAX ? 999 : (int) $v;
    }

    public function prayerCount(User $user): int { return $user->ai_prayer_count_this_month; }

    // ── Reset helpers ─────────────────────────────────────────────────────────

    private function resetYearlyZnsIfNeeded(User $user): void
    {
        $thisYear = now('Asia/Ho_Chi_Minh')->year;
        if (($user->zns_count_reset_year ?? 0) != $thisYear) {
            $user->update([
                'zns_count_this_year'  => 0,
                'zns_count_reset_year' => $thisYear,
            ]);
        }
    }

    private function resetDailyAiIfNeeded(User $user): void
    {
        $today = now('Asia/Ho_Chi_Minh')->toDateString();
        if ($user->ai_message_reset_date?->toDateString() !== $today) {
            $user->update([
                'ai_message_count_today' => 0,
                'ai_message_reset_date'  => $today,
            ]);
        }
    }
}
