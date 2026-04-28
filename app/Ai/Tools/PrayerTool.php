<?php

namespace App\Ai\Tools;

use App\Ai\Skills\PrayerSkill;
use App\Models\MemorialEvent;
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

        // Dùng PrayerSkill thay vì inline rules
        return "DỮ LIỆU VĂN KHẤN:\n{$prayerData}\n\n" . PrayerSkill::rules();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
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
