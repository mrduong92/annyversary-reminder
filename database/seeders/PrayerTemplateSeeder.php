<?php

namespace Database\Seeders;

use App\Models\Prayer;
use Illuminate\Database\Seeder;

class PrayerTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Xóa bản hệ thống cũ trước khi seed lại
        Prayer::where('is_system', true)->delete();

        foreach (self::templates() as $tpl) {
            Prayer::create([
                'user_id'    => null,
                'title'      => $tpl['title'],
                'content'    => $tpl['content'],
                'is_system'  => true,
                'ai_generated' => false,
            ]);
        }
    }

    // ── Nội dung văn khấn ────────────────────────────────────────────────────

    private static function templates(): array
    {
        return [

            // ── 1. Cúng rằm / Mùng một ──────────────────────────────────────
            [
                'title'   => 'Văn khấn cúng Rằm / Mùng Một hàng tháng',
                'content' => <<<'EOT'
Nam mô A Di Đà Phật! (3 lần)

Con lạy chín phương Trời, mười phương Chư Phật, Chư Phật mười phương.
Con kính lạy Hoàng Thiên Hậu Thổ chư vị Tôn thần.
Con kính lạy ngài Bản cảnh Thành hoàng, ngài Bản xứ Thổ địa, ngài Bản gia Táo quân cùng chư vị Tôn thần.
Con kính lạy các cụ Tổ khảo, Tổ tỷ, nội ngoại tiên linh.

Tín chủ chúng con là: ......................................................
Ngụ tại: ...............................................................................

Hôm nay là ngày ...... tháng ...... năm ......, nhân ngày Sóc/Vọng (Mùng Một/Rằm), tín chủ con thành tâm sắm sửa hương hoa, lễ vật, trà quả bày lên trước án, dâng lên trước bệ đài các chư vị Tôn thần.

Cúi xin các ngài thương xót tín chủ, giáng lâm trước án, chứng giám lòng thành, thụ hưởng lễ vật, phù trì tín chủ chúng con toàn gia an lạc, công việc hanh thông, vạn sự như ý.

Chúng con lễ bạc tâm thành, trước án kính lễ, cúi xin được phù hộ độ trì.

Nam mô A Di Đà Phật! (3 lần)
EOT,
            ],

            // ── 2. Ông Công Ông Táo ─────────────────────────────────────────
            [
                'title'   => 'Văn khấn Ông Công Ông Táo (23 tháng Chạp)',
                'content' => <<<'EOT'
Nam mô A Di Đà Phật! (3 lần)

Con kính lạy ngài Đông Trù Tư Mệnh Táo Phủ Thần Quân.

Tín chủ con là: ......................................................
Ngụ tại: ...............................................................................

Hôm nay là ngày 23 tháng Chạp năm ......, tín chủ con thành tâm sắm sửa hương hoa, mũ áo, vàng mã, lễ vật bày lên trước án, một lòng kính lễ, trân trọng tâu trình:

Kính thưa ngài Táo Quân, trong năm qua ngài đã chứng giám mọi việc lớn nhỏ trong gia đình chúng con. Nay chúng con thành tâm sắm lễ, kính tiễn ngài lên chầu Thiên đình để tâu trình mọi việc của gia đình chúng con trong năm qua.

Cúi xin ngài rủ lòng thương xót, tâu những điều tốt đẹp, bỏ qua những điều lỡ lầm thiếu sót. Kính mong Thiên đình ban phúc lành, gia đình chúng con năm mới bình an, thịnh vượng, mọi sự như ý.

Chúng con lễ bạc tâm thành, kính lễ ngài Táo Quân, cúi xin phù hộ độ trì.

Nam mô A Di Đà Phật! (3 lần)
EOT,
            ],

            // ── 3. Tết Nguyên Đán ───────────────────────────────────────────
            [
                'title'   => 'Văn khấn Tết Nguyên Đán (Giao thừa)',
                'content' => <<<'EOT'
Nam mô A Di Đà Phật! (3 lần)

Con lạy chín phương Trời, mười phương Chư Phật, Chư Phật mười phương.
Con kính lạy Đức Đương Lai Hạ Sinh Di Lặc Tôn Phật.
Con kính lạy Hoàng Thiên Hậu Thổ chư vị Tôn thần.
Con kính lạy ngài Bản cảnh Thành hoàng Chư vị Đại vương.
Con kính lạy ngài Đương niên Hành khiển, ngài Bản xứ Thổ địa, ngài Bản gia Táo quân.
Con kính lạy các cụ Tổ tiên nội ngoại.

Nay là phút Giao thừa, năm ...... đã qua, năm ...... mới đến. Tín chủ chúng con là: ......................................................
Ngụ tại: ...............................................................................

Thành tâm sắm sửa hương hoa trà quả dâng lên trước án, cung thỉnh các chư vị giáng lâm, chứng giám lòng thành.

Năm cũ qua đi, đón năm mới sang. Cúi xin các chư vị Tôn thần, các bậc Tổ tiên phù hộ cho toàn gia chúng con năm mới vạn sự hanh thông, sức khoẻ dồi dào, công việc thuận lợi, gia đình hạnh phúc, bình an.

Chúng con lễ bạc tâm thành, cúi xin được phù hộ độ trì.

Nam mô A Di Đà Phật! (3 lần)
EOT,
            ],

            // ── 4. Giỗ Tổ Hùng Vương ────────────────────────────────────────
            [
                'title'   => 'Văn khấn Giỗ Tổ Hùng Vương (10/3 âm lịch)',
                'content' => <<<'EOT'
Nam mô A Di Đà Phật! (3 lần)

Con lạy chín phương Trời, mười phương Chư Phật, Chư Phật mười phương.
Con kính lạy Đức Quốc Tổ Hùng Vương cùng các vị Vua Hùng.
Con kính lạy các bậc tiền nhân đã có công dựng nước và giữ nước.

Tín chủ con là: ......................................................
Ngụ tại: ...............................................................................

Hôm nay là ngày mồng 10 tháng 3 âm lịch năm ......, ngày Giỗ Tổ Hùng Vương. Tín chủ con thành tâm sắm sửa hương hoa lễ vật dâng lên trước án.

Con cháu Lạc Hồng muôn phương tụ hội, hướng về đất Tổ Phong Châu, thành kính dâng lên Vua Tổ tấm lòng tri ân sâu sắc. Nhớ ơn các Vua Hùng đã có công dựng nên bờ cõi, để lại giang sơn gấm vóc cho con cháu muôn đời.

Cúi xin Đức Quốc Tổ chứng giám lòng thành, phù hộ cho đất nước thanh bình, dân tộc hưng thịnh, gia đình chúng con được bình an, khỏe mạnh, làm ăn phát đạt.

Chúng con lễ bạc tâm thành, kính lạy Vua Tổ Hùng Vương.

Nam mô A Di Đà Phật! (3 lần)
EOT,
            ],

            // ── 5. Cúng đầy tháng em bé ─────────────────────────────────────
            [
                'title'   => 'Văn khấn cúng đầy tháng em bé',
                'content' => <<<'EOT'
Nam mô A Di Đà Phật! (3 lần)

Con lạy chín phương Trời, mười phương Chư Phật, Chư Phật mười phương.
Con kính lạy Đức Ngọc Hoàng Thượng Đế.
Con kính lạy Đức Phật Bà Quan Âm.
Con kính lạy Bà Chúa Đầu Thai, Bà Mụ Vương Thị, cùng Mười Hai Bà Mụ đã nặn ra hài nhi.
Con kính lạy Đức Đương Cảnh Thành Hoàng, ngài Thổ địa, ngài Táo Quân.
Con kính lạy chư vị Tổ tiên nội ngoại.

Tín chủ con là: ......................................................
Ngụ tại: ...............................................................................

Hôm nay là ngày ...... tháng ...... năm ......, tròn một tháng kể từ khi hài nhi ...... (tên bé) chào đời. Vợ chồng con thành tâm sắm sửa hương hoa, bánh trái, mâm lễ dâng lên trước án.

Kính lạy Bà Chúa Đầu Thai và Mười Hai Bà Mụ đã nặn ra hài nhi, ban cho bé hình hài đầy đủ, lành lặn. Cúi xin các ngài tiếp tục phù hộ cho cháu ...... được mạnh khoẻ, hay ăn chóng lớn, thông minh sáng dạ, bình an trưởng thành.

Cúi xin tổ tiên chứng giám lòng thành, phù hộ cho gia đình chúng con thêm phước lành.

Nam mô A Di Đà Phật! (3 lần)
EOT,
            ],

            // ── 6. Thôi nôi ─────────────────────────────────────────────────
            [
                'title'   => 'Văn khấn cúng thôi nôi (đầy năm)',
                'content' => <<<'EOT'
Nam mô A Di Đà Phật! (3 lần)

Con lạy chín phương Trời, mười phương Chư Phật, Chư Phật mười phương.
Con kính lạy Bà Chúa Đầu Thai, Bà Mụ Vương Thị cùng Mười Hai Bà Mụ.
Con kính lạy Đức Đương Cảnh Thành Hoàng, ngài Thổ địa, ngài Táo Quân.
Con kính lạy chư vị Tổ tiên nội ngoại.

Tín chủ con là: ......................................................
Ngụ tại: ...............................................................................

Hôm nay là ngày ...... tháng ...... năm ......, là ngày Thôi Nôi — tròn một năm cháu ...... (tên bé) chào đời. Gia đình con thành tâm sắm sửa hương hoa, bánh trái, lễ vật bày lên trước án.

Kính lạy Mười Hai Bà Mụ đã nặn ra hài nhi trong suốt một năm qua phù hộ cho cháu được khoẻ mạnh, hay ăn chóng lớn. Nay cháu đã tròn một tuổi, gia đình chúng con thành tâm dâng lễ tạ ơn.

Cúi xin các ngài tiếp tục che chở, phù hộ cho cháu ...... lớn khôn, mạnh khoẻ, bình an, học hành giỏi giang, trở thành người có ích.

Gia đình chúng con lễ bạc tâm thành, kính lạy và cảm ơn.

Nam mô A Di Đà Phật! (3 lần)
EOT,
            ],

            // ── 7. Cúng nhà mới ─────────────────────────────────────────────
            [
                'title'   => 'Văn khấn cúng nhà mới / cúng đất',
                'content' => <<<'EOT'
Nam mô A Di Đà Phật! (3 lần)

Con lạy chín phương Trời, mười phương Chư Phật, Chư Phật mười phương.
Con kính lạy Hoàng Thiên Hậu Thổ chư vị Tôn thần.
Con kính lạy Đức Đương Cảnh Thành Hoàng chư vị Đại vương.
Con kính lạy ngài Bản xứ Thổ địa Phúc đức Chính thần.
Con kính lạy ngài Bản gia Táo quân và chư vị Thần linh cai quản trong khu vực này.
Con kính lạy các cụ Tổ tiên nội ngoại.

Tín chủ con là: ......................................................
Ngụ tại: ...............................................................................

Hôm nay là ngày ...... tháng ...... năm ......, gia đình chúng con vừa dọn đến nhà mới tại địa chỉ trên. Thành tâm sắm sửa hương hoa, lễ vật bày lên trước án để kính cáo chư vị Thần linh và Tổ tiên.

Kính cáo: Chúng con đã chọn được mảnh đất này, xây dựng ngôi nhà làm nơi an cư lạc nghiệp. Chúng con kính cẩn trình báo với chư vị Thần linh bản địa và xin phép được an cư tại đây.

Cúi xin chư vị Thần linh chứng giám, gia hộ cho gia đình chúng con được bình an, khoẻ mạnh, công việc thuận lợi, mọi sự hanh thông. Ngôi nhà này được ấm áp hạnh phúc, con cháu sum vầy, tài lộc dồi dào.

Chúng con lễ bạc tâm thành, kính lễ và cúi xin phù hộ độ trì.

Nam mô A Di Đà Phật! (3 lần)
EOT,
            ],

            // ── 8. Cúng đám cưới (xin dâu) ──────────────────────────────────
            [
                'title'   => 'Văn khấn xin dâu (lễ cưới)',
                'content' => <<<'EOT'
Nam mô A Di Đà Phật! (3 lần)

Con lạy chín phương Trời, mười phương Chư Phật, Chư Phật mười phương.
Con kính lạy Hoàng Thiên Hậu Thổ chư vị Tôn thần.
Con kính lạy ngài Bản cảnh Thành hoàng, ngài Thổ địa, ngài Táo quân.
Con kính lạy các cụ Tổ tiên nội ngoại hai họ.

Hôm nay là ngày ...... tháng ...... năm ......, là ngày lành tháng tốt, chúng con:
Họ nhà trai: ......................................................
Đến nhà gái: ......................................................

Để xin phép làm lễ thành hôn cho:
- Chú rể: ......................................................
- Cô dâu: ......................................................

Kính cáo tổ tiên hai họ biết rõ. Nay hai con đã ưng thuận kết duyên vợ chồng, gia đình hai bên đã đồng ý. Chúng con thành tâm sắm sửa lễ vật, hoa quả, trầu cau kính dâng lên tổ tiên.

Cúi xin tổ tiên hai họ chứng giám, phù hộ cho đôi uyên ương hạnh phúc trăm năm, duyên phận bền vững, sớm có con cháu nối dõi tông đường, gia đình hai bên thêm phần gắn bó thương yêu.

Chúng con lễ bạc tâm thành, kính lễ cúi xin phù hộ.

Nam mô A Di Đà Phật! (3 lần)
EOT,
            ],

            // ── 9. Cúng cô hồn tháng 7 ──────────────────────────────────────
            [
                'title'   => 'Văn khấn cúng cô hồn (Rằm tháng 7)',
                'content' => <<<'EOT'
Nam mô A Di Đà Phật! (3 lần)

Con lạy chín phương Trời, mười phương Chư Phật, Chư Phật mười phương.
Con kính lạy Đức Địa Tạng Vương Bồ Tát.
Con kính lạy các vị Thần linh cai quản âm phủ.

Hôm nay là ngày Rằm tháng Bảy năm ......, tín chủ con là: ......................................................
Ngụ tại: ...............................................................................

Thành tâm sắm sửa cháo lá đa, muối gạo, quần áo, vàng mã cùng các thứ thức ăn, thức uống bày lên trước án. Kính mời các vong linh không nơi nương tựa, các cô hồn lang thang, các vong nhân chưa được siêu thoát về đây thụ hưởng.

Nguyện cầu cho các vong linh được no đủ, được siêu thoát, đầu thai chuyển kiếp nơi lành.

Cúi xin các ngài Thần linh coi sóc âm phủ chứng giám lòng thành, phù hộ cho gia đình chúng con được bình an, không bị quấy nhiễu, mọi sự hanh thông.

Nam mô Siêu Lạc Độ Đạo Sư A Di Đà Phật! (3 lần)
EOT,
            ],

            // ── 10. Văn khấn ngày giỗ (mẫu chung) ──────────────────────────
            [
                'title'   => 'Văn khấn ngày giỗ (mẫu chung)',
                'content' => <<<'EOT'
Nam mô A Di Đà Phật! (3 lần)

Con lạy chín phương Trời, mười phương Chư Phật, Chư Phật mười phương.
Con kính lạy Hoàng Thiên Hậu Thổ chư vị Tôn thần.
Con kính lạy các cụ Tổ tiên nội ngoại.

Hôm nay là ngày ...... tháng ...... âm lịch, nhằm ngày ...... dương lịch.

Tín chủ con là: ......................................................
Ngụ tại: ...............................................................................

Thành kính nhớ đến: ...................................................... (Danh xưng, họ tên người mất)

Năm nay đúng ngày giỗ, tín chủ chúng con cùng toàn gia thành tâm sắm sửa hương hoa, lễ vật, mâm cơm dâng lên trước linh cữu để tỏ lòng tưởng nhớ.

Kính xin ...... linh thiêng phù hộ cho con cháu mạnh khoẻ, bình an, công việc thuận lợi, gia đình hạnh phúc. Cháu con luôn nhớ ơn và làm theo những điều tốt đẹp mà ...... đã để lại.

Chúng con lễ bạc tâm thành, kính cẩn dâng lên trước linh cữu. Cúi mong được chứng giám và phù hộ.

Nam mô A Di Đà Phật! (3 lần)
EOT,
            ],

            // ── 11. Văn khấn Tổ tiên ngày Tết (mùng 1) ─────────────────────
            [
                'title'   => 'Văn khấn Tổ tiên ngày Tết Nguyên Đán (mùng 1)',
                'content' => <<<'EOT'
Nam mô A Di Đà Phật! (3 lần)

Con lạy chín phương Trời, mười phương Chư Phật, Chư Phật mười phương.
Con kính lạy Hoàng Thiên Hậu Thổ chư vị Tôn thần.
Con kính lạy các cụ Cao tằng tổ khảo, cao tằng tổ tỷ, bá thúc đệ huynh, cô dì tỷ muội và các hương linh nội ngoại.

Tín chủ con là: ......................................................
Ngụ tại: ...............................................................................

Ngày hôm nay là mùng 1 Tết Nguyên Đán năm ......, xuân mới đã về. Gia đình chúng con thành tâm sắm sửa hương hoa, bánh chưng, mâm ngũ quả, rượu trà dâng lên trước án mời các cụ Tổ tiên về ăn Tết cùng con cháu.

Kính mời các cụ Tổ tiên về hưởng hương hoa, chứng giám lòng thành. Cúi xin các cụ phù hộ cho gia đình chúng con năm mới bình an, sức khoẻ, phát tài phát lộc, mọi sự như ý nguyện.

Con cháu nguyện gìn giữ nền nếp gia phong, hiếu thảo với cha mẹ, đoàn kết yêu thương nhau.

Chúng con lễ bạc tâm thành, kính mời Tổ tiên về vui Tết cùng con cháu.

Nam mô A Di Đà Phật! (3 lần)
EOT,
            ],

        ];
    }
}
