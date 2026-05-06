<?php

namespace App\Services;

use Carbon\Carbon;
use LucNham\LunarCalendar\LunarDateTime;
use LucNham\LunarCalendar\Sexagenary;
use LucNham\LunarCalendar\Terms\VnBranchIdentifier;
use LucNham\LunarCalendar\Terms\VnStemIdentifier;

class LunarCalendarService
{
    private const TZ = 'Asia/Ho_Chi_Minh';

    /**
     * Convert lunar date → solar Carbon.
     * Nếu ngày 30 không tồn tại trong tháng (tháng thiếu), tự động lùi về ngày 29.
     */
    public function lunarToSolar(int $day, int $month, int $year): Carbon
    {
        if ($day === 30 && ! $this->monthHas30Days($month, $year)) {
            $day = 29;
        }

        return $this->convert($day, $month, $year);
    }

    /**
     * Ngày dương lịch kế tiếp của một ngày dương lịch cố định hằng năm.
     * VD: 10/3 dương → 10/3/năm_nay nếu chưa qua, ngược lại 10/3/năm_sau.
     */
    public function nextSolarOccurrence(int $day, int $month): Carbon
    {
        $now  = Carbon::now(self::TZ)->startOfDay();
        $date = Carbon::create($now->year, $month, $day, 0, 0, 0, self::TZ);

        return ($date->isFuture() || $date->isToday()) ? $date : $date->addYear();
    }

    /**
     * Ngày dương lịch kế tiếp của một ngày âm lịch hằng năm.
     * Trả về năm nay nếu chưa qua hoặc là hôm nay, ngược lại năm sau.
     */
    public function nextOccurrence(int $lunarDay, int $lunarMonth): Carbon
    {
        $lunarYear = (int) Carbon::now(self::TZ)->format('Y');

        $thisYear = $this->lunarToSolar($lunarDay, $lunarMonth, $lunarYear);

        return ($thisYear->isFuture() || $thisYear->isToday())
            ? $thisYear
            : $this->lunarToSolar($lunarDay, $lunarMonth, $lunarYear + 1);
    }

    /**
     * Số ngày còn lại tính từ hôm nay (âm nếu đã qua).
     */
    public function daysUntil(Carbon $date): int
    {
        return (int) Carbon::now(self::TZ)
            ->startOfDay()
            ->diffInDays($date->copy()->startOfDay(), false);
    }

    /**
     * Chuyển ngày dương lịch → thông tin âm lịch đầy đủ.
     * Trả về: ['day', 'month', 'year', 'is_leap', 'stem_day', 'branch_day', 'stem_year', 'branch_year']
     */
    public function solarToLunarInfo(Carbon $solar): array
    {
        $str = $solar->format('Y-m-d') . ' 00:00 ' . self::TZ;
        $lunar = new LunarDateTime('G:' . $str, new \DateTimeZone(self::TZ));

        $sex = new Sexagenary($lunar, VnStemIdentifier::class, VnBranchIdentifier::class);

        return [
            'day'        => (int) $lunar->day,
            'month'      => (int) $lunar->month,
            'year'       => (int) $lunar->year,
            'is_leap'    => (bool) $lunar->isLeapMonth,
            'stem_day'   => $sex->D->name,    // Can ngày (Giáp, Ất, ...)
            'branch_day' => $sex->d->name,    // Chi ngày (Tý, Sửu, ...)
            'branch_pos' => $sex->d->position, // 0=Tý .. 11=Hợi
            'stem_year'  => $sex->Y->name,    // Can năm
            'branch_year'=> $sex->y->name,    // Chi năm
        ];
    }

    // ── Private ───────────────────────────────────────────────

    private function convert(int $day, int $month, int $year): Carbon
    {
        $str = sprintf('%04d-%02d-%02d 00:00 %s', $year, $month, $day, self::TZ);

        return Carbon::parse((new LunarDateTime($str))->toDateTimeString())
            ->setTimezone(self::TZ)
            ->startOfDay();
    }

    /**
     * Kiểm tra tháng âm lịch có 30 ngày không.
     * Tạo LunarDateTime với ngày 30 rồi đọc lại lunar month:
     * - Nếu vẫn là $month → tháng đủ (30 ngày)
     * - Nếu đã thành $month+1 → overflow, tháng thiếu (29 ngày)
     */
    private function monthHas30Days(int $month, int $year): bool
    {
        $str   = sprintf('%04d-%02d-30 00:00 %s', $year, $month, self::TZ);
        $lunar = new LunarDateTime($str);

        // format('j') trả về ngày âm lịch sau khi package tự điều chỉnh.
        // Nếu tháng có 30 ngày → format('j') = 30.
        // Nếu tháng chỉ có 29 ngày → ngày 30 bị overflow, format('j') trả về ngày khác (thường là 1).
        return (int) $lunar->format('j') === 30;
    }
}
