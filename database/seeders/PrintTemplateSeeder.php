<?php

namespace Database\Seeders;

use App\Models\PrintTemplate;
use Illuminate\Database\Seeder;

class PrintTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PrintTemplate::create([
            'name' => 'Mẫu truyền thống',
            'description' => 'Cây gia phả nền sẫm màu với viền vàng truyền thống.',
            'price' => 49000,
            'is_free' => false,
            'is_active' => true,
            'width' => 900,
            'height' => 820,
            'padding' => 20,
            'show_title' => true,
            'title_text' => 'PHẢ ĐỒ GIA TỘC',
            'show_footer' => true,
            'footer_text' => 'Lập năm 2026',
            'view_name' => 'exports.family-tree-traditional',
        ]);
        
        PrintTemplate::create([
            'name' => 'Mẫu hiện đại',
            'description' => 'Cây gia phả nền sáng, thiết kế tinh gọn và thanh lịch.',
            'price' => 0,
            'is_free' => true,
            'is_active' => true,
            'width' => 1200,
            'height' => 800,
            'padding' => 50,
            'show_title' => true,
            'title_text' => 'GIA PHẢ ĐƠN GIẢN',
            'show_footer' => false,
            'view_name' => null, // Sẽ dùng code cũ render nếu cần
        ]);
    }
}
