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
        return 'Truy vấn lịch sự kiện gia đình (ngày giỗ, sinh nhật, kỷ niệm...). '
            . 'Dùng khi người dùng hỏi về ngày giỗ, sinh nhật, còn bao nhiêu ngày, ngày dương lịch tương ứng.';
    }

    public function handle(Request $request): string
    {
        $keyword   = (string) $request->string('keyword');
        $eventType = (string) $request->string('event_type'); // lọc theo loại nếu cần

        $query = MemorialEvent::where('user_id', $this->userId)->active();

        if ($this->familyGroupId) {
            $query->where('family_group_id', $this->familyGroupId);
        }

        // Lọc theo event_type nếu được chỉ định
        if ($eventType && array_key_exists($eventType, MemorialEvent::TYPE_LABELS)) {
            $query->where('event_type', $eventType);
        }

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                // Tìm theo title (sự kiện tự do)
                $q->where('title', 'like', "%{$keyword}%")
                  // Hoặc theo thành viên gia phả
                  ->orWhereHas('familyMember', function ($q2) use ($keyword) {
                      $q2->where('name', 'like', "%{$keyword}%")
                         ->orWhere('relationship', 'like', "%{$keyword}%")
                         ->orWhere('pronoun', 'like', "%{$keyword}%");
                  });
            });
        }

        $events = $query->orderBy('solar_date_next')->get();

        if ($events->isEmpty()) {
            $typeLabel = $eventType ? (MemorialEvent::TYPE_LABELS[$eventType] ?? 'sự kiện') : 'sự kiện';
            return $keyword
                ? "Không tìm thấy {$typeLabel} nào liên quan đến \"{$keyword}\"."
                : "Chưa có {$typeLabel} nào được lưu.";
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
                $daysUntil === 0                         => ' — HÔM NAY',
                $daysUntil === 1                         => ' — ngày mai',
                $daysUntil !== null && $daysUntil <= 365 => " — còn {$daysUntil} ngày",
                default                                  => '',
            };

            $label = '';
            if ($e->pronoun) {
                $label = $this->readOnly ? " [{$e->pronoun}]" : " ({$e->pronoun})";
            } elseif ($e->relationship) {
                $label = $this->readOnly
                    ? " (theo chủ gia đình: {$e->relationship})"
                    : " ({$e->relationship})";
            }

            $typeTag = ($e->event_type ?? MemorialEvent::DEFAULT_TYPE) !== MemorialEvent::DEFAULT_TYPE
                ? ' [' . $e->typeLabel() . ']' : '';

            return "• {$e->displayName()}{$label}{$typeTag}: {$dateStr}{$solarStr}{$countdown}";
        });

        $data      = $lines->implode("\n");
        $typeLabel = $eventType
            ? (MemorialEvent::TYPE_LABELS[$eventType] ?? 'Sự kiện')
            : 'Lịch sự kiện gia đình';

        return "{$typeLabel}:\n{$data}\n\n" . CalendarSkill::rules();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'keyword' => $schema->string()
                ->description('Từ khoá tìm kiếm: tên người, danh xưng, quan hệ, hoặc tiêu đề sự kiện. Để trống để lấy tất cả.'),
            'event_type' => $schema->string()
                ->description('Lọc theo loại: anniversary_of_death (ngày giỗ), birthday (sinh nhật), ancestor_anniversary (giỗ tổ), anniversary (kỷ niệm), event (sự kiện khác). Để trống = lấy tất cả.'),
        ];
    }
}
