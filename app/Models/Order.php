<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_DP = 'dp';
    public const PAYMENT_PAID = 'paid';

    public const STATUS_ANTREAN = 'antrean';
    public const STATUS_DIPRODUKSI = 'diproduksi';
    public const STATUS_SIAP_DIAMBIL = 'siap_diambil';
    public const STATUS_CLOSED = 'closed';

    public const ORDER_STATUSES = [
        self::STATUS_ANTREAN,
        self::STATUS_DIPRODUKSI,
        self::STATUS_SIAP_DIAMBIL,
        self::STATUS_CLOSED,
    ];

    protected $fillable = [
        'order_number',
        'customer_id',
        'user_id',
        'total_amount',
        'payment_status',
        'order_status',
        'file_design_link',
        'design_file_path',
        'notes',
        'stock_deducted_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'float',
            'stock_deducted_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getTotalPaidAttribute(): float
    {
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->sum('amount_paid');
        }

        return (float) $this->payments()->sum('amount_paid');
    }

    public function getRemainingBalanceAttribute(): float
    {
        return max(0.0, round((float) $this->total_amount - $this->total_paid, 2));
    }

    public function canBeClosed(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID
            && $this->remaining_balance <= 0.0;
    }

    public function isProductionEligible(): bool
    {
        return in_array($this->payment_status, [self::PAYMENT_DP, self::PAYMENT_PAID], true);
    }

    public function syncPaymentStatus(): void
    {
        $totalPaid = (float) $this->payments()->sum('amount_paid');
        $totalAmount = (float) $this->total_amount;

        if ($totalPaid <= 0) {
            $status = self::PAYMENT_UNPAID;
        } elseif ($totalPaid + 0.001 >= $totalAmount && $totalAmount > 0) {
            $status = self::PAYMENT_PAID;
        } else {
            $status = self::PAYMENT_DP;
        }

        if ($this->payment_status !== $status) {
            $this->payment_status = $status;
            $this->saveQuietly();
        }
    }

    public function paymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_PAID => 'Lunas',
            self::PAYMENT_DP => 'DP (Uang Muka)',
            default => 'Belum Bayar',
        };
    }

    public function orderStatusLabel(): string
    {
        return match ($this->order_status) {
            self::STATUS_ANTREAN => 'Antrean Produksi',
            self::STATUS_DIPRODUKSI => 'Sedang Diproduksi',
            self::STATUS_SIAP_DIAMBIL => 'Selesai / Siap Diambil',
            self::STATUS_CLOSED => 'Sudah Diambil (Closed)',
            default => ucfirst($this->order_status),
        };
    }

    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD-'.now()->format('Ymd').'-';
        $latest = static::query()
            ->where('order_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('order_number');

        $nextSeq = 1;
        if ($latest) {
            $parts = explode('-', $latest);
            $nextSeq = ((int) end($parts)) + 1;
        }

        return $prefix.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }
}

