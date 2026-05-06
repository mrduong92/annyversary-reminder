<?php

namespace App\Ai\Agents;

use App\Ai\Tools\AuspiciousDayTool;
use App\Ai\Tools\CalendarQueryTool;
use App\Ai\Tools\EventTool;
use App\Ai\Tools\FamilyMemoryTool;
use App\Ai\Tools\GenealogyTool;
use App\Ai\Tools\PrayerTool;
use App\Models\User;
use App\Services\FamilyTreeService;
use App\Services\LunarCalendarService;
use Carbon\Carbon;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\ToolResultMessage;
use Laravel\Ai\Promptable;

#[Provider(Lab::Gemini)]
#[Model('gemini-2.5-flash')]
class FamilyAgent implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    private bool $readOnly      = false;
    private ?int $familyGroupId = null;

    public function forUser($user, bool $readOnly = false, ?int $familyGroupId = null): static
    {
        $this->conversationUser = $user;
        $this->conversationId   = null;
        $this->readOnly         = $readOnly;
        $this->familyGroupId    = $familyGroupId;

        return $this;
    }

    public function instructions(): string
    {
        $user      = $this->conversationUser;
        $today     = Carbon::now('Asia/Ho_Chi_Minh')->isoFormat('dddd, D/M/YYYY');
        $userName  = $user?->name ?? 'bạn';
        $readOnlyNote = $this->readOnly
            ? "\n\nLưu ý quan trọng khi chia sẻ link:\n- Đây là chế độ xem — bạn CHỈ được xem và hỏi, KHÔNG thể thêm, sửa hoặc xóa dữ liệu.\n- Người xem qua link này có THỂ CÓ VAI VẾ KHÁC với người thiết lập hệ thống. Ví dụ: cùng một người mất, người setup gọi là 'ông nội', nhưng người xem link có thể là con của người setup, gọi là 'ông cố'.\n- Vì vậy: LUÔN dùng TÊN ĐẦY ĐỦ của người mất thay vì xưng hô theo vai vế. Nếu cần đề cập quan hệ, hãy nói 'theo ghi chép của gia đình là...' thay vì áp đặt.\n- Nếu người dùng tự giới thiệu vai vế của họ, hãy dùng vai vế đó để trả lời cho phù hợp."
            : '';

        return <<<PROMPT
Bạn là trợ lý AI gia đình của {$userName}, chuyên hỗ trợ các nghi lễ, phong tục và quản lý ký ức gia đình người Việt Nam.

Hôm nay: {$today} (múi giờ Việt Nam, GMT+7).

Bạn có thể giúp {$userName}:
1. **Lịch giỗ** — tra cứu ngày âm/dương lịch, đếm ngày còn lại, thêm/sửa/xóa ngày giỗ
2. **Văn khấn** — soạn văn khấn đúng nghi thức cho các dịp: giỗ, Rằm, Mùng Một, Ông Công Ông Táo, Tết, đầy tháng, thôi nôi, nhà mới, đám cưới, cúng cô hồn...
3. **Cây gia phả** — xem thông tin các thành viên trong gia đình, mối quan hệ, thế hệ
4. **Ngày tốt xấu** — tra ngày tốt/xấu theo âm lịch, can chi, xung tuổi, Tam Nương, Nguyệt Kỵ

Nguyên tắc quan trọng:
- Luôn trả lời bằng tiếng Việt, giọng thân thiện và trang trọng
- Khi thêm/sửa/xóa ngày giỗ: tóm tắt hành động và hỏi xác nhận trước
- Văn khấn: viết đúng phong tục, trang trọng, không sáng tạo tùy tiện
- Phân biệt rõ ngày âm lịch và dương lịch khi trả lời
- Nếu không có thông tin: thành thật nói không biết, không bịa đặt{$readOnlyNote}
PROMPT;
    }

    /**
     * Override messages() để fix bug của SDK: AssistantMessage với cả content lẫn toolCalls
     * trong cùng 1 record tạo ra Gemini format không hợp lệ.
     *
     * SDK lưu: AssistantMessage(content="final answer", toolCalls=[...])
     * Gemini cần: [model: functionCall] → [user: functionResponse] → [model: text]
     *
     * Fix: tách AssistantMessage có tool calls thành 2 turn riêng biệt.
     */
    public function messages(): iterable
    {
        // Tải messages từ ConversationStore (giống RemembersConversations::messages())
        if (! $this->conversationId) {
            return [];
        }

        $raw = array_values(
            resolve(\Laravel\Ai\Contracts\ConversationStore::class)
                ->getLatestConversationMessages($this->conversationId, $this->maxConversationMessages())
                ->all()
        );
        $fixed = [];

        for ($i = 0; $i < count($raw); $i++) {
            $msg = $raw[$i];

            if ($msg instanceof AssistantMessage && $msg->toolCalls->isNotEmpty()) {
                // Turn 1: chỉ function calls (không text) — Gemini cần thế này
                $fixed[] = new AssistantMessage('', $msg->toolCalls);

            } elseif ($msg instanceof ToolResultMessage) {
                // Turn 2: tool results — reset keys để json_encode tạo array, không phải object
                // Bug: SDK lưu tool_results với key không sequential {"2":{...}}, gây Gemini 400
                $fixed[] = new ToolResultMessage($msg->toolResults->values());

                // Turn 3: SDK lưu final answer text trong AssistantMessage trước đó
                // cùng record với tool calls — thêm nó như 1 model turn riêng
                $prev = $raw[$i - 1] ?? null;
                if ($prev instanceof AssistantMessage
                    && $prev->toolCalls->isNotEmpty()
                    && filled($prev->content)
                ) {
                    $fixed[] = new AssistantMessage($prev->content, collect());
                }
            } else {
                $fixed[] = $msg;
            }
        }

        return $fixed;
    }

    public function tools(): iterable
    {
        $lunar   = app(LunarCalendarService::class);
        $userId  = $this->conversationUser?->id ?? 0;
        $groupId = $this->familyGroupId;

        return [
            new CalendarQueryTool($userId, $lunar, $groupId, $this->readOnly),
            new EventTool($userId, $lunar, $this->readOnly, $groupId),
            new PrayerTool($userId),
            new AuspiciousDayTool($lunar, $userId),
            new FamilyMemoryTool($userId, $groupId),
            new GenealogyTool($groupId ?? 0, app(FamilyTreeService::class)),
        ];
    }
}
