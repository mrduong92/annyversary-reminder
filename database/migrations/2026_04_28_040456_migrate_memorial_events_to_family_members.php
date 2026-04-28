<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Với mỗi memorial_event chưa có family_member_id:
        // - Tạo family_member từ name/pronoun/relationship của event
        // - Link lại event.family_member_id = member.id
        // - Link member.memorial_event_id = event.id (ngược lại)

        $events = DB::table('memorial_events')
            ->whereNull('family_member_id')
            ->get();

        foreach ($events as $event) {
            // Xác định family_group_id — dùng user's default group nếu không có
            $groupId = $event->family_group_id;

            if (! $groupId) {
                $defaultGroup = DB::table('family_groups')
                    ->where('user_id', $event->user_id)
                    ->where('is_default', true)
                    ->first();

                $groupId = $defaultGroup?->id;

                if (! $groupId) {
                    // Tạo default group nếu user chưa có
                    $groupId = DB::table('family_groups')->insertGetId([
                        'user_id'    => $event->user_id,
                        'name'       => 'Gia đình',
                        'color'      => '#c026d3',
                        'is_default' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Cập nhật event.family_group_id
                DB::table('memorial_events')
                    ->where('id', $event->id)
                    ->update(['family_group_id' => $groupId]);
            }

            // Tạo family_member từ thông tin event
            $memberId = DB::table('family_members')->insertGetId([
                'family_group_id'  => $groupId,
                'user_id'          => $event->user_id,
                'name'             => $event->name,
                'pronoun'          => $event->pronoun,
                'relationship'     => $event->relationship,
                'gender'           => 'unknown',
                'death_year'       => null, // không biết năm mất chính xác
                'memorial_event_id' => $event->id, // link ngược
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            // Link event → member
            DB::table('memorial_events')
                ->where('id', $event->id)
                ->update(['family_member_id' => $memberId]);
        }
    }

    public function down(): void
    {
        // Không rollback data migration vì có thể mất dữ liệu
        // Chỉ unlink các events đã được migrate
    }
};
