<?php

namespace App\Ai\Tools;

use App\Ai\Skills\CalendarSkill;
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
        private readonly ?int $familyGroupId = null,
        private readonly bool $readOnly = false,
    ) {}

    public function description(): string
    {
        return 'Truy vấn lịch giỗ của gia đình. Dùng khi người dùng hỏi về ngày giỗ, còn bao nhiêu ngày, ngày dương lịch tương ứng.';
    }

    public function handle(Request $request): string
    {
        $keyword = (string) $request->string('keyword');

        $query = MemorialEvent::where('user_id', $this->userId)->active();

        if ($this->familyGroupId) {
            $query->where('family_group_id', $this->familyGroupId);
        }

        if ($keyword) {
            $query->whereHas('familyMember', function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('relationship', 'like', "%{$keyword}%")
                  ->orWhere('pronoun', 'like', "%{$keyword}%");
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

            $dateStr  = $e->dateLabel();
            $solarStr = $e->solar_date_next
                ? ' (dương: ' . $e->solar_date_next->format('d/m/Y') . ', ' . $e->solar_date_next->isoFormat('dddd') . ')'
                : '';

            $countdown = match (true) {
                $daysUntil === 0                              => ' — HÔM NAY',
                $daysUntil === 1                              => ' — ngày mai',
                $daysUntil !== null && $daysUntil <= 365      => " — còn {$daysUntil} ngày",
                default                                       => '',
            };

            // Hiển thị: pronoun > relationship > tên thuần
            if ($e->pronoun) {
                $label = $this->readOnly
                    ? " [{$e->pronoun}]"                               // share: bracket để rõ là danh xưng chung
                    : " ({$e->pronoun})";
            } elseif ($e->relationship) {
                $label = $this->readOnly
                    ? " (theo chủ gia đình: {$e->relationship})"
                    : " ({$e->relationship})";
            } else {
                $label = '';
            }

            return "• {$e->name}{$label}: {$dateStr}{$solarStr}{$countdown}";
        });

        $data = $lines->implode("\n");

        return "DỮ LIỆU LỊCH GIỖ:\n{$data}\n\n" . CalendarSkill::rules();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'keyword' => $schema->string()
                ->description('Từ khoá tìm kiếm: tên người, danh xưng hoặc quan hệ. Để trống để lấy tất cả.'),
        ];
    }
}
