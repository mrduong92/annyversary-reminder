<?php

return [
    'default' => [
        'id'           => 'default',
        'name'         => 'Mẫu Truyền Thống',
        'description'  => 'Hoa văn truyền thống Việt Nam, khổ A0 ngang.',
        'preview_image'=> null,
        'is_active'    => true,
        'width'        => 3508,
        'height'       => 2480,
        'padding'      => 350,
        'show_title'   => true,
        'view_name'    => 'print-templates.default.index',
    ],

    'gia_pha_2' => [
        'id' => 'gia_pha_2',
        'name' => 'Mẫu Cao Cấp 1',
        'description' => 'Khung viền hoạ tiết hoàng gia, kích thước lớn.',
        'preview_image' => null,
        'is_active' => true,
        'width' => 7022,
        'height' => 4967,
        'padding' => 100,
        'show_title' => false,
        'tree_x' => 1000,
        'tree_y' => 1000,
        'tree_scale' => 4,
        'view_name' => 'print-templates.gia_pha_2',
    ],
    
    'gia_pha_3' => [
        'id' => 'gia_pha_3',
        'name' => 'Mẫu Cao Cấp 2',
        'description' => 'Khung viền hiện đại, màu sắc trang nhã.',
        'preview_image' => null,
        'is_active' => true,
        'width' => 3508,
        'height' => 2480,
        'padding' => 50,
        'show_title' => false,
        'tree_x' => 500,
        'tree_y' => 500,
        'tree_scale' => 2,
        'view_name' => 'print-templates.gia_pha_3',
    ],
];
