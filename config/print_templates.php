<?php

return [
    /*
     * Template mặc định — Gia_Pha_3.svg (SVG injection approach)
     * Canvas: 3508 × 2480 (A0 landscape)
     * Capacity: 69 members (Gen 0–6)
     */
    'default' => [
        'id'          => 'default',
        'name'        => 'Mẫu Truyền Thống',
        'description' => 'Hoa văn truyền thống Việt Nam, khổ A0 ngang. Tối đa 69 thành viên.',
        'svg_path'    => 'vectors/Gia_Pha_3.svg',  // relative to base_path()
        'couple_root' => true,
        'canvas_w'    => 3508,
        'canvas_h'    => 2480,
        'title_cx'    => 1754,   // center x — nằm giữa cuộn băng
        'title_cy'    => 315,    // center y — nằm trong vùng màu đỏ của cuộn
        'title_size'  => 44,     // max font size, tự thu nhỏ nếu tên dài
        'preview_image' => null,
        'is_active'   => true,
    ],
];
