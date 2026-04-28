<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ActivateSubscription extends Command
{
    protected $signature = 'subscription:activate
                            {user : Email hoặc ID của user}
                            {plan : mini | premium}
                            {--months=12 : Số tháng (default 12)}
                            {--amount=0 : Số tiền đã thu (VND)}
                            {--note= : Ghi chú}';

    protected $description = 'Kích hoạt subscription cho user (thủ công sau khi thu tiền)';

    public function handle(): int
    {
        $identifier = $this->argument('user');
        $plan       = $this->argument('plan');

        if (! in_array($plan, ['mini', 'premium', 'free'])) {
            $this->error("Plan phải là: free | mini | premium");
            return 1;
        }

        // Tìm user
        $user = is_numeric($identifier)
            ? User::find($identifier)
            : User::where('email', $identifier)->first();

        if (! $user) {
            $this->error("Không tìm thấy user: {$identifier}");
            return 1;
        }

        $months  = (int) $this->option('months');
        $amount  = (int) $this->option('amount');
        $note    = $this->option('note');
        $startAt = now();
        $expiresAt = $plan === 'free' ? null : $startAt->copy()->addMonths($months);

        // Hiển thị confirm
        $this->table(['Field', 'Value'], [
            ['User',       $user->name . ' (' . ($user->email ?? $user->phone) . ')'],
            ['Plan',       strtoupper($plan)],
            ['Thời hạn',   $plan === 'free' ? 'Vĩnh viễn' : $expiresAt->format('d/m/Y')],
            ['Số tiền',    number_format($amount) . ' VND'],
            ['Ghi chú',   $note ?: '—'],
        ]);

        if (! $this->confirm('Xác nhận kích hoạt?')) {
            $this->info('Đã hủy.');
            return 0;
        }

        // Cập nhật user
        $user->update([
            'subscription_plan'       => $plan,
            'subscription_expires_at' => $expiresAt,
        ]);

        // Lưu lịch sử
        Subscription::create([
            'user_id'      => $user->id,
            'plan'         => $plan,
            'amount'       => $amount,
            'note'         => $note,
            'activated_by' => get_current_user() ?: 'admin',
            'starts_at'    => $startAt,
            'expires_at'   => $expiresAt,
        ]);

        $this->info("✓ Đã kích hoạt {$plan} cho {$user->name}." .
            ($expiresAt ? " Hết hạn: " . $expiresAt->format('d/m/Y') : ''));

        return 0;
    }
}
