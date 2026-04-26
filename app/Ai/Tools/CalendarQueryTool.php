<?php

namespace App\Ai\Tools;

use App\Models\MemorialEvent;
use App\Services\LunarCalendarService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class CalendarQueryTool implements Tool
{
    public function __construct(
        private readonly int $userId,
        private readonly LunarCalendarService $lunar,
    ) {}

    public function description(): string
    {
        return 'Truy vấn lịch giỗ của gia đình. Dùng khi người dùng hỏi về ngày giỗ, còn bao nhiêu ngày, ngày dương lịch tương ứng.';
    }

    public function handle(Request $request): string
    {
        $keyword = $request->string('keyword');

        $query = MemorialEvent::where('user_id', $this->userId)->active();

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('relationship', 'like', "%{$keyword}%");
            });
        }

        $events = $query->orderBy('solar_date_next')->get();

        if ($events->isEmpty()) {
            return $keyword
                ? "Không tìm thấy ngày giỗ nào liên quan đến \"{$keyword}\"."
                : 'Chưa có ngày giỗ nào được lưu.';
        }

        $lines = $events->map(function (MemorialEvent $e) {
            $daysUntil = $e->solar_date_next
                ? $this->lunar->daysUntil($e->solar_date_next)
                : null;

            $dateStr = $e->dateLabel();
            $solarStr = $e->solar_date_next
                ? ' (dương: ' . $e->solar_date_next->format('d/m/Y') . ', ' . $e->solar_date_next->isoFormat('dddd') . ')'
                : '';

            $countdown = match (true) {
                $daysUntil === 0  => ' — HÔM NAY',
                $daysUntil === 1  => ' — ngày mai',
                $daysUntil !== null && $daysUntil <= 365 => " — còn {$daysUntil} ngày",
                default => '',
            };

            $relationship = $e->relationship ? " ({$e->relationship})" : '';

            return "• {$e->name}{$relationship}: {$dateStr}{$solarStr}{$countdown}";
        });

        return $lines->implode("\n");
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'keyword' => $schema->string()
                ->description('Từ khoá tìm kiếm: tên người hoặc quan hệ (VD: "ông nội", "bà ngoại"). Để trống để lấy tất cả.'),
        ];
    }
}
