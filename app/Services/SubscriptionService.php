<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class SubscriptionService
{
    /**
     * Giới hạn theo plan.
     * ZNS tính theo NĂM (không phải tháng).
     * Events/Recipients là giới hạn cho ZNS reminders, không phải tổng số lưu.
     */
    private const LIMITS = [
        'free' => [
            'zns_events'          => 3,       // max events có nhắc ZNS
            'recipients'          => 1,
            'zns_per_year'        => 50,
            'ai_prayers_per_month'=> 0,        // không có văn khấn AI
            'ai_messages_per_day' => 20,       // chat cơ bản
            'shares'              => 0,        // không share
            'documents'           => 0,        // không RAG
            'ram_mung_mot'        => false,
            'import_image'        => false,
            'can_crud_events_ai'  => false,    // không tạo/sửa ngày giỗ qua AI
        ],
        'mini' => [
            'zns_events'          => 10,
            'recipients'          => 3,
            'zns_per_year'        => 100,
            'ai_prayers_per_month'=> PHP_INT_MAX,
            'ai_messages_per_day' => 100,
            'shares'              => 0,
            'documents'           => 0,
            'ram_mung_mot'        => false,
            'import_image'        => false,
            'can_crud_events_ai'  => true,
        ],
        'premium' => [
            'zns_events'          => 20,
            'recipients'          => 10,
            'zns_per_year'        => 300,
            'ai_prayers_per_month'=> PHP_INT_MAX,
            'ai_messages_per_day' => PHP_INT_MAX,
            'shares'              => PHP_INT_MAX,
            'documents'           => 20,
            'ram_mung_mot'        => true,
            'import_image'        => true,
            'can_crud_events_ai'  => true,
        ],
    ];

    // ── Plan helpers ──────────────────────────────────────────────

    public function plan(User $user): string
    {
        $plan = $user->subscription_plan ?? 'free';

        // Downgrade nếu hết hạn
        if ($plan !== 'free' && $user->subscription_expires_at?->isPast()) {
            return 'free';
        }

        return in_array($plan, ['free', 'mini', 'premium']) ? $plan : 'free';
    }

    public function isFree(User $user): bool    { return $this->plan($user) === 'free'; }
    public function isMini(User $user): bool    { return $this->plan($user) === 'mini'; }
    public function isPremium(User $user): bool { return $this->plan($user) === 'premium'; }
    public function isPaid(User $user): bool    { return ! $this->isFree($user); }

    private function limit(User $user, string $key): mixed
    {
        return self::LIMITS[$this->plan($user)][$key] ?? 0;
    }

    // ── Feature gates ─────────────────────────────────────────────

    public function canAddRecipient(User $user): bool
    {
        return $user->recipients()->count() < $this->limit($user, 'recipients');
    }

    public function canSendZns(User $user): bool
    {
        $this->resetYearlyZnsIfNeeded($user);
        return $user->zns_count_this_year < $this->limit($user, 'zns_per_year');
    }

    public function canGeneratePrayer(User $user): bool
    {
        if ($this->limit($user, 'ai_prayers_per_month') === 0) return false;
        $this->resetMonthlyPrayerIfNeeded($user);
        return $user->ai_prayer_count_this_month < $this->limit($user, 'ai_prayers_per_month');
    }

    public function canSendAgentMessage(User $user): bool
    {
        $this->resetDailyAiIfNeeded($user);
        return $user->ai_message_count_today < $this->limit($user, 'ai_messages_per_day');
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

    public function canUseRamMungMot(User $user): bool
    {
        return (bool) $this->limit($user, 'ram_mung_mot');
    }

    public function canImportImage(User $user): bool
    {
        return (bool) $this->limit($user, 'import_image');
    }

    public function canCrudEventsViaAi(User $user): bool
    {
        return (bool) $this->limit($user, 'can_crud_events_ai');
    }

    // ── Increment counters ────────────────────────────────────────

    public function incrementZnsCount(User $user): void
    {
        $user->increment('zns_count_this_year');
    }

    public function incrementPrayerCount(User $user): void
    {
        $user->increment('ai_prayer_count_this_month');
    }

    public function incrementAgentMessageCount(User $user): void
    {
        $user->increment('ai_message_count_today');
    }

    // ── Limit getters for UI ──────────────────────────────────────

    public function znsLimit(User $user): int        { return (int) $this->limit($user, 'zns_per_year'); }
    public function recipientLimit(User $user): int  { return (int) $this->limit($user, 'recipients'); }
    public function znsEventLimit(User $user): int   { return (int) $this->limit($user, 'zns_events'); }
    public function documentLimit(User $user): int
    {
        $v = $this->limit($user, 'documents');
        return $v === PHP_INT_MAX ? 999 : (int) $v;
    }

    // ── Reset helpers ─────────────────────────────────────────────

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

    private function resetMonthlyPrayerIfNeeded(User $user): void
    {
        $today = now('Asia/Ho_Chi_Minh')->startOfMonth()->toDateString();
        // ai_prayer_count_this_month vẫn dùng monthly reset (dùng chung field cũ)
        // Dùng ai_message_reset_date làm mốc reset prayer cùng lúc
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
