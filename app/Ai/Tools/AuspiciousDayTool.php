<?php

namespace App\Ai\Tools;

use App\Services\LunarCalendarService;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

/**
 * Tra cứu ngày tốt xấu theo lịch âm Việt Nam.
 *
 * Cung cấp thông tin thực tế (âm lịch, can chi, ngày kỵ dân gian)
 * để AI tổng hợp lời khuyên phù hợp với mục đích của người dùng.
 */
class AuspiciousDayTool implements Tool
{
    // Ngày Tam Nương hàng tháng — kiêng khởi sự, cúng kiếng quan trọng
    private const TAM_NUONG = [3, 7, 13, 18, 22, 27];

    // Ngày Nguyệt Kỵ — tháng nào cũng có, tránh khởi đầu
    private const NGUYET_KY = [5, 14, 23];

    // Bảng xung theo chi: index 0=Tý, 1=Sửu, ... 11=Hợi
    // Xung = khoảng cách 6 trong vòng 12 chi
    private const CHI_NAMES = ['Tý','Sửu','Dần','Mão','Thìn','Tỵ','Ngọ','Mùi','Thân','Dậu','Tuất','Hợi'];

    public function __construct(
        private readonly LunarCalendarService $lunar,
        private readonly int $userId,
    ) {}

    public function description(): string
    {
        return 'Tra cứu ngày tốt xấu theo âm lịch Việt Nam cho một khoảng thời gian. '
            . 'Trả về thông tin can chi, ngày kỵ dân gian và đánh giá cơ bản từng ngày. '
            . 'Dùng khi người dùng hỏi về ngày tốt để làm giỗ, cúng bái, xuất hành hoặc công việc quan trọng.';
    }

    public function handle(Request $request): string
    {
        $startStr   = (string) ($request->string('start_date') ?: now('Asia/Ho_Chi_Minh')->toDateString());
        $days       = max(1, min(14, (int) ($request->integer('days') ?: 7)));
        $purpose    = (string) ($request->string('purpose') ?: '');
        $birthYears = array_filter(array_map('intval', (array) ($request->get('birth_years') ?: [])));

        $start   = Carbon::parse($startStr, 'Asia/Ho_Chi_Minh')->startOfDay();
        $results = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $info = $this->lunar->solarToLunarInfo($date);

            $results[] = $this->analyzeDay($date, $info, $birthYears);
        }

        $header = "THÔNG TIN NGÀY TỐT XẤU" . ($purpose ? " (mục đích: {$purpose})" : '') . "\n"
            . "Từ: " . $start->format('d/m/Y') . " | " . $days . " ngày\n"
            . ($birthYears ? "Tuổi gia chủ: " . implode(', ', $birthYears) . "\n" : '')
            . "─────────────────────────────\n\n";

        return $header . implode("\n\n", $results) . "\n\n"
            . "Lưu ý: Thông tin trên là các chỉ số âm lịch cơ bản. Hãy kết hợp với "
            . "mục đích cụ thể, hoàn cảnh gia đình và phong tục địa phương khi đưa ra lời khuyên.";
    }

    private function analyzeDay(Carbon $date, array $info, array $birthYears): string
    {
        $solarStr = $date->isoFormat('dddd, DD/MM/YYYY');
        $lunarStr = "{$info['day']}/{$info['month']}" . ($info['is_leap'] ? ' (nhuận)' : '') . " âm lịch";
        $canChi   = "{$info['stem_day']} {$info['branch_day']}";

        // ── Đánh giá ngày ────────────────────────────────────────
        $notes    = [];
        $quality  = 'bình thường';

        // Ngày Sóc / Vọng
        if ($info['day'] === 1)  { $notes[] = 'Ngày Sóc (Mùng Một) — tốt để cúng gia thần'; $quality = 'tốt'; }
        if ($info['day'] === 15) { $notes[] = 'Ngày Vọng (Rằm) — tốt để cúng gia tiên'; $quality = 'tốt'; }

        // Tam Nương
        if (in_array($info['day'], self::TAM_NUONG, true)) {
            $notes[]  = 'Ngày Tam Nương — kiêng khởi sự, cúng giỗ quan trọng';
            $quality  = 'xấu';
        }

        // Nguyệt Kỵ
        if (in_array($info['day'], self::NGUYET_KY, true)) {
            $notes[]  = 'Ngày Nguyệt Kỵ — nên tránh khởi đầu, xuất hành';
            $quality  = $quality === 'xấu' ? 'xấu' : 'cần lưu ý';
        }

        // ── Kiểm tra tuổi xung ────────────────────────────────────
        $dayBranchPos = $info['branch_pos']; // 0=Tý .. 11=Hợi

        foreach ($birthYears as $year) {
            if ($year < 1900 || $year > 2100) continue;

            $birthChiPos  = ($year - 4) % 12;
            $birthChi     = self::CHI_NAMES[$birthChiPos] ?? '?';
            $xungPos      = ($birthChiPos + 6) % 12;

            if ($dayBranchPos === $birthChiPos) {
                $notes[]  = "Tuổi {$birthChi} ({$year}): Ngày bản mệnh — tránh khởi sự lớn";
                $quality  = 'cần lưu ý';
            } elseif ($dayBranchPos === $xungPos) {
                $notes[]  = "Tuổi {$birthChi} ({$year}): Ngày xung tuổi — nên tránh";
                $quality  = $quality !== 'xấu' ? 'cần lưu ý' : 'xấu';
            }
        }

        // ── Format output ─────────────────────────────────────────
        $icon = match ($quality) {
            'tốt'        => '✅',
            'xấu'        => '❌',
            'cần lưu ý'  => '⚠️',
            default      => '📅',
        };

        $line = "{$icon} {$solarStr}\n"
            . "   Âm lịch: {$lunarStr} | Can Chi: {$canChi} | Đánh giá: {$quality}";

        if ($notes) {
            $line .= "\n   " . implode("\n   ", $notes);
        }

        return $line;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'start_date'  => $schema->string()->description('Ngày bắt đầu tra (YYYY-MM-DD). Mặc định: hôm nay.'),
            'days'        => $schema->integer()->description('Số ngày cần tra (1-14). Mặc định: 7.'),
            'purpose'     => $schema->string()->description('Mục đích cần ngày tốt (VD: "làm giỗ", "xuất hành", "cúng nhà mới", "kết hôn").'),
            'birth_years' => $schema->array(items: $schema->integer())
                ->description('Năm sinh dương lịch của gia chủ/người liên quan để kiểm tra xung tuổi. VD: [1964, 1993]'),
        ];
    }
}
