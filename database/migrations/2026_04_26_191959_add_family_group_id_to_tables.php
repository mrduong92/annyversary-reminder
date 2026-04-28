<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Các bảng cần thêm family_group_id
    private array $tables = [
        'memorial_events',
        'recipients',
        'prayers',
        'family_shares',
    ];

    public function up(): void
    {
        // Thêm cột family_group_id (nullable) vào các bảng
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('family_group_id')->nullable()->constrained()->nullOnDelete()->after('user_id');
            });
        }

        // Tạo default group cho mỗi user hiện có và gán data cũ vào đó
        $users = DB::table('users')->get(['id']);
        foreach ($users as $user) {
            $groupId = DB::table('family_groups')->insertGetId([
                'user_id'    => $user->id,
                'name'       => 'Gia đình',
                'color'      => '#c026d3',
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Gán tất cả data cũ (chưa có group) vào default group
            foreach ($this->tables as $tableName) {
                DB::table($tableName)
                    ->where('user_id', $user->id)
                    ->whereNull('family_group_id')
                    ->update(['family_group_id' => $groupId]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['family_group_id']);
                $table->dropColumn('family_group_id');
            });
        }
        DB::table('family_groups')->delete();
    }
};
