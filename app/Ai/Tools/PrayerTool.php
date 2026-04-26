<?php

namespace App\Ai\Tools;

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
        return 'Soạn văn khấn giỗ theo đúng nghi thức truyền thống Việt Nam. Trả về bài văn khấn hoàn chỉnh và lưu vào hệ thống.';
    }

    public function handle(Request $request): string
    {
        $eventId = $request->integer('event_id');
        $event   = $eventId
            ? MemorialEvent::where('user_id', $this->userId)->find($eventId)
            : null;

        $name          = $request->string('deceased_name') ?: $event?->name ?: 'người thân';
        $relationship  = $request->string('relationship') ?: $event?->relationship ?: '';
        $worshipperName = $request->string('worshipper_name') ?: '';
        $address       = $request->string('address') ?: '';
        $occasion      = $request->string('occasion') ?: 'ngày giỗ';

        $prayerContent = $this->generatePrayer($name, $relationship, $worshipperName, $address, $occasion);

        $title = "Văn khấn {$occasion}" . ($relationship ? " {$relationship}" : '') . " {$name}";

        Prayer::create([
            'user_id'          => $this->userId,
            'memorial_event_id' => $event?->id,
            'title'            => $title,
            'content'          => $prayerContent,
            'ai_generated'     => true,
        ]);

        return $prayerContent;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'event_id'       => $schema->integer()->description('ID ngày giỗ (nếu có, tự động lấy tên và quan hệ)'),
            'deceased_name'  => $schema->string()->description('Tên người được cúng (VD: "Nguyễn Văn A")'),
            'relationship'   => $schema->string()->description('Quan hệ (VD: "ông nội", "bà ngoại")'),
            'worshipper_name'=> $schema->string()->description('Tên người đứng khấn'),
            'address'        => $schema->string()->description('Địa chỉ nơi thờ cúng'),
            'occasion'       => $schema->string()->description('Dịp cúng (VD: "giỗ đầu", "giỗ thường niên", "ngày giỗ")'),
        ];
    }

    private function generatePrayer(
        string $name,
        string $relationship,
        string $worshipperName,
        string $address,
        string $occasion,
    ): string {
        $relationshipText = $relationship ? "{$relationship} " : '';
        $worshipperText   = $worshipperName ? "Con/cháu: {$worshipperName}" : 'Con cháu trong gia đình';
        $addressText      = $address ?: 'tại gia';

        return <<<PRAYER
Nam mô A Di Đà Phật! (3 lần)

Con lạy chín phương Trời, mười phương Chư Phật, Chư Phật mười phương.

Con kính lạy {$relationshipText}hương linh: {$name}

Hôm nay là ngày {$occasion}, {$worshipperText}, thành tâm sắm lễ, hương hoa trà quả, thắp nén tâm hương {$addressText}.

Kính mời {$relationshipText}hương linh {$name} về đây thụ hưởng lễ vật, chứng giám lòng thành của con cháu.

Kính xin {$relationshipText}hương linh phù hộ độ trì cho toàn thể gia đình được mạnh khỏe, bình an, vạn sự hanh thông, công danh thuận lợi.

Con cháu nhớ ơn sinh thành dưỡng dục, nguyện sống xứng đáng với công đức của {$relationshipText}hương linh để lại.

Nam mô A Di Đà Phật! (3 lần)
PRAYER;
    }
}
