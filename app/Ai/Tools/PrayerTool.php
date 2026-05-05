<?php

namespace App\Ai\Tools;

use App\Ai\Skills\PrayerSkill;
use App\Models\MemorialEvent;
use App\Models\Prayer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class PrayerTool implements Tool
{
    public function __construct(private readonly int $userId) {}

    public function description(): string
    {
        return 'Soạn văn khấn giỗ theo đúng nghi thức truyền thống Việt Nam. Trả về bài văn khấn hoàn chỉnh. Người dùng sẽ tự quyết định có lưu lại hay không.';
    }

    public function handle(Request $request): string
    {
        $eventId = $request->integer('event_id');
        $event   = $eventId
            ? MemorialEvent::where('user_id', $this->userId)->find($eventId)
            : null;

        $name           = (string) ($request->string('deceased_name') ?: $event?->name ?: 'người thân');
        $pronoun        = $event?->pronoun ?: '';
        $relationship   = (string) ($request->string('relationship') ?: $event?->relationship ?: '');
        $worshipperName = (string) ($request->string('worshipper_name') ?: '');
        $address        = (string) ($request->string('address') ?: '');
        $occasion       = (string) ($request->string('occasion') ?: 'ngày giỗ');

        // Ưu tiên pronoun (danh xưng) nếu người tạo đã đặt
        $displayName = $pronoun ? "{$pronoun} {$name}" : $name;
        $prayerData  = $this->generatePrayer($displayName, $pronoun ?: $relationship, $worshipperName, $address, $occasion);

        // Tiêu đề: AI tự extract từ yêu cầu của user (qua param title),
        // fallback sang compose từ occasion + displayName nếu AI không cung cấp
        $userTitle = trim((string) ($request->string('title') ?: ''));
        $title     = $userTitle ?: trim("Văn khấn {$occasion}" . ($displayName !== 'người thân' ? " — {$displayName}" : ''));

        // Auto-save vào DB — user không cần bấm nút lưu
        $prayer = Prayer::create([
            'user_id'            => $this->userId,
            'memorial_event_id'  => $event?->id,
            'title'              => $title,
            'content'            => $prayerData,
            'ai_generated'       => true,
        ]);

        // Trả về prayer text + marker để frontend hiển thị link "Đã lưu"
        return "DỮ LIỆU VĂN KHẤN:\n{$prayerData}\n\n[PRAYER_SAVED:{$prayer->id}]\n\n" . PrayerSkill::rules();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title'           => $schema->string()->description('Tiêu đề ngắn gọn dựa đúng vào yêu cầu của người dùng. VD: "Văn khấn giỗ ông nội Dương Huy Điệm", "Văn khấn Ông Công Ông Táo", "Văn khấn cúng Rằm tháng 7". KHÔNG được dùng nội dung văn khấn làm tiêu đề.'),
            'event_id'        => $schema->integer()->description('ID ngày giỗ (nếu có, tự động lấy tên, pronoun và quan hệ)'),
            'deceased_name'   => $schema->string()->description('Tên người được cúng (VD: "Nguyễn Văn A")'),
            'relationship'    => $schema->string()->description('Quan hệ (VD: "ông nội", "bà ngoại") — bỏ qua nếu đã có pronoun từ event'),
            'worshipper_name' => $schema->string()->description('Tên người đứng khấn'),
            'address'         => $schema->string()->description('Địa chỉ nơi thờ cúng'),
            'occasion'        => $schema->string()->description('Dịp cúng (VD: "giỗ đầu", "giỗ thường niên", "ngày giỗ")'),
        ];
    }

    private function generatePrayer(
        string $name,
        string $relationship,
        string $worshipperName,
        string $address,
        string $occasion,
    ): string {
        $rel            = $relationship ? "{$relationship} " : '';
        $worshipperText = $worshipperName ? "Con/cháu: {$worshipperName}" : 'Con cháu trong gia đình';
        $addressText    = $address ?: 'tại gia';

        return <<<PRAYER
Nam mô A Di Đà Phật! (3 lần)

Con lạy chín phương Trời, mười phương Chư Phật, Chư Phật mười phương.

Con kính lạy {$rel}hương linh: {$name}

Hôm nay là ngày {$occasion}, {$worshipperText}, thành tâm sắm lễ, hương hoa trà quả, thắp nén tâm hương {$addressText}.

Kính mời {$rel}hương linh {$name} về đây thụ hưởng lễ vật, chứng giám lòng thành của con cháu.

Kính xin {$rel}hương linh phù hộ độ trì cho toàn thể gia đình được mạnh khỏe, bình an, vạn sự hanh thông, công danh thuận lợi.

Con cháu nhớ ơn sinh thành dưỡng dục, nguyện sống xứng đáng với công đức của {$rel}hương linh để lại.

Nam mô A Di Đà Phật! (3 lần)
PRAYER;
    }
}
