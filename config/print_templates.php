<?php

return [
    /*
     * Template mặc định — Gia_Pha_3.svg
     * Canvas: 3508 × 2480 (A4 landscape @ 300dpi)
     */
    'default' => [
        'id'          => 'default',
        'name'        => 'Mẫu Truyền Thống',
        'description' => 'Hoa văn truyền thống Việt Nam, khổ A0 ngang. Tối đa 69 thành viên.',
        'svg_path'    => 'vectors/Gia_Pha_3.svg',  // relative to base_path()
        'couple_root' => true,
        'canvas_w'    => 3508,
        'canvas_h'    => 2480,
        'title_cx'    => 1754,   // center x — nằm giữa cuộn băng (canvas center)
        'title_cy'    => 295,    // center y — nằm trong vùng màu đỏ (Object 468 bắt đầu y=239)
        'title_size'  => 48,     // max font size, tự thu nhỏ nếu tên dài
        'preview_image' => null,
        'is_active'   => true,
    ],

    /*
     * Template 2 — resources/views/print-templates/template_2/background.svg
     * Canvas: 7022 × 4967 (= 2× template default, A2 landscape @ 300dpi)
     * Layout tự động scale ×2 vì canvas_w = 7022 = 2 × 3508.
     */
    'template_2' => [
        'id'            => 'template_2',
        'name'          => 'Mẫu Cao Cấp',
        'description'   => 'Thiết kế cao cấp, khổ lớn hơn.',
        'svg_path'      => 'resources/views/print-templates/template_2/background.svg',
        'couple_root'   => true,
        'canvas_w'      => 7022,
        'canvas_h'      => 4967,
        'title_size'    => 96,   // 2× default (48 × 2)
        'preview_image' => null,
        'is_active'     => true,
    ],
];
