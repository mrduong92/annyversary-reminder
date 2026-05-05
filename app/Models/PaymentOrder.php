<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PaymentOrder extends Model
{
    protected $fillable = [
        'user_id', 'plan', 'amount', 'reference_code',
        'status', 'sepay_transaction_id', 'sepay_payload',
        'expires_at', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'paid_at'    => 'datetime',
            'amount'     => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool   { return $this->status === 'pending'; }
    public function isCompleted(): bool { return $this->status === 'completed'; }
    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->expires_at->isPast();
    }

    public function planLabel(): string
    {
        return match ($this->plan) {
            'advanced' => 'Đại Gia Đình',
            'mini'     => 'Mini',      // legacy
            'premium'  => 'Premium',   // legacy
            default    => $this->plan,
        };
    }

    public function formattedAmount(): string
    {
        return number_format($this->amount, 0, ',', '.') . 'đ';
    }

    /** Tạo reference code unique dạng GP-XXXXXX */
    public static function generateReference(): string
    {
        do {
            $code = 'GP' . strtoupper(Str::random(6));
        } while (static::where('reference_code', $code)->exists());

        return $code;
    }

    /** URL QR VietQR (không cần SePay API, dùng open standard) */
    public function qrUrl(): string
    {
        $bank    = config('services.sepay.bank_code', 'MB');
        $account = config('services.sepay.bank_account');
        $amount  = $this->amount;
        $info    = urlencode($this->reference_code);

        return "https://img.vietqr.io/image/{$bank}-{$account}-compact2.jpg"
            . "?amount={$amount}&addInfo={$info}&accountName=" . urlencode('GIA PHONG APP');
    }
}
