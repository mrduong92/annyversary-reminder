<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetMonthlyZnsCount extends Command
{
    protected $signature   = 'zns:reset-monthly-count';
    protected $description = 'Reset zns_count_this_month và ai_prayer_count_this_month về 0 vào ngày 1 hàng tháng';

    public function handle(): void
    {
        $today = Carbon::now('Asia/Ho_Chi_Minh');

        if ($today->day !== 1) {
            $this->info("Hôm nay là ngày {$today->day}, chưa cần reset (chỉ chạy ngày 1).");
            return;
        }

        $affected = DB::table('users')->update([
            'zns_count_this_month'       => 0,
            'ai_prayer_count_this_month' => 0,
            'zns_count_reset_month'      => $today->startOfMonth()->toDateString(),
        ]);

        $this->info("Reset ZNS count cho {$affected} users — tháng {$today->format('m/Y')}.");
    }
}
