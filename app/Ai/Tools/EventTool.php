<?php

namespace App\Ai\Tools;

use App\Models\MemorialEvent;
use App\Services\LunarCalendarService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class EventTool implements Tool
{
    public function __construct(
        private readonly int $userId,
        private readonly LunarCalendarService $lunar,
        private readonly bool $readOnly = false,
    ) {}

    public function description(): string
    {
        return $this->readOnly
            ? 'Xem danh sách ngày giỗ. Chế độ chỉ đọc — không thể thêm, sửa hoặc xóa.'
            : 'Thêm, sửa hoặc xóa ngày giỗ bằng ngôn ngữ tự nhiên. Luôn xác nhận với người dùng trước khi thực hiện thao tác ghi.';
    }

    public function handle(Request $request): string
    {
        $action = $request->string('action');

        if ($this->readOnly && $action !== 'list') {
            return 'Bạn chỉ có quyền xem lịch giỗ, không thể thay đổi dữ liệu qua link chia sẻ này.';
        }

        return match ($action) {
            'list'   => $this->list($request),
            'add'    => $this->add($request),
            'update' => $this->update($request),
            'delete' => $this->delete($request),
            default  => 'Hành động không hợp lệ.',
        };
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->enum(['list', 'add', 'update', 'delete'])
                ->description('list=xem danh sách, add=thêm mới, update=sửa, delete=xóa')->required(),
            'name'         => $schema->string()->description('Tên người (VD: "Ông nội Nguyễn Văn A")'),
            'relationship' => $schema->string()->description('Quan hệ với người dùng'),
            'lunar_day'    => $schema->integer()->description('Ngày (1-30)'),
            'lunar_month'  => $schema->integer()->description('Tháng (1-12)'),
            'date_type'    => $schema->string()->enum(['lunar', 'solar'])->description('lunar=âm, solar=dương'),
            'event_id'     => $schema->integer()->description('ID ngày giỗ (cần cho update/delete)'),
            'notes'        => $schema->string()->description('Ghi chú'),
        ];
    }

    private function list(Request $request): string
    {
        $events = MemorialEvent::where('user_id', $this->userId)->active()->orderBy('solar_date_next')->get();

        if ($events->isEmpty()) return 'Chưa có ngày giỗ nào.';

        return $events->map(fn ($e) => "ID:{$e->id} — {$e->name} ({$e->dateLabel()})")->implode("\n");
    }

    private function add(Request $request): string
    {
        $name       = $request->string('name');
        $lunarDay   = $request->integer('lunar_day');
        $lunarMonth = $request->integer('lunar_month');
        $dateType   = $request->string('date_type') ?: 'lunar';

        if (! $name || ! $lunarDay || ! $lunarMonth) return 'Thiếu thông tin: cần tên, ngày và tháng.';

        $solarNext = $dateType === 'solar'
            ? $this->lunar->nextSolarOccurrence($lunarDay, $lunarMonth)
            : $this->lunar->nextOccurrence($lunarDay, $lunarMonth);

        $event = MemorialEvent::create([
            'user_id' => $this->userId, 'name' => $name,
            'relationship' => $request->string('relationship'),
            'lunar_day' => $lunarDay, 'lunar_month' => $lunarMonth,
            'date_type' => $dateType, 'solar_date_next' => $solarNext,
            'notes' => $request->string('notes'), 'is_active' => true,
        ]);

        return "✓ Đã thêm \"{$event->name}\" ({$event->dateLabel()}), gần nhất: {$solarNext->format('d/m/Y')}.";
    }

    private function update(Request $request): string
    {
        $event = MemorialEvent::where('user_id', $this->userId)->find($request->integer('event_id'));

        if (! $event) return 'Không tìm thấy ngày giỗ với ID đã cung cấp.';

        $lunarDay   = $request->integer('lunar_day') ?: $event->lunar_day;
        $lunarMonth = $request->integer('lunar_month') ?: $event->lunar_month;
        $dateType   = $request->string('date_type') ?: $event->date_type;

        $solarNext = $dateType === 'solar'
            ? $this->lunar->nextSolarOccurrence($lunarDay, $lunarMonth)
            : $this->lunar->nextOccurrence($lunarDay, $lunarMonth);

        $event->update([
            'name' => $request->string('name') ?: $event->name,
            'relationship' => $request->string('relationship') ?: $event->relationship,
            'lunar_day' => $lunarDay, 'lunar_month' => $lunarMonth,
            'date_type' => $dateType, 'solar_date_next' => $solarNext,
            'notes' => $request->string('notes') ?: $event->notes,
        ]);

        return "✓ Đã cập nhật \"{$event->name}\".";
    }

    private function delete(Request $request): string
    {
        $event = MemorialEvent::where('user_id', $this->userId)->find($request->integer('event_id'));

        if (! $event) return 'Không tìm thấy ngày giỗ với ID đã cung cấp.';

        $name = $event->name;
        $event->delete();

        return "✓ Đã xóa ngày giỗ \"{$name}\".";
    }
}
