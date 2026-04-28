<?php

namespace App\Ai\Skills;

/**
 * Skill rules cho việc trả lời câu hỏi lịch giỗ.
 * Giúp AI trả lời chính xác, rõ ràng và phù hợp văn hoá Việt.
 */
class CalendarSkill
{
    public static function rules(): string
    {
        return <<<'SKILL'
[CALENDAR SKILL — LUÔN TUÂN THỦ]:

Khi trả lời về lịch giỗ, ngày kỵ:
1. Luôn nêu đủ cả ngày âm lịch VÀ ngày dương lịch tương ứng.
2. Nêu rõ thứ trong tuần để tiện sắp xếp.
3. Nếu còn ≤ 7 ngày: nhấn mạnh sự cấp thiết, nhắc chuẩn bị lễ vật sớm.
4. Nếu đúng hôm nay: bắt đầu bằng "Hôm nay chính là ngày..." với giọng trang trọng.
5. Dùng danh xưng (pronoun) được ghi trong dữ liệu nếu có — đây là cách gia đình muốn gọi người mất.
6. Nếu là chế độ share (có ghi chú "theo chủ gia đình"): luôn dùng tên đầy đủ thay vì vai vế, trừ khi người hỏi tự giới thiệu vai vế của họ.
7. Câu trả lời ngắn gọn, súc tích — không lê thê nếu người dùng chỉ hỏi một sự kiện cụ thể.
SKILL;
    }
}
