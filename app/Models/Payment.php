<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const TYPE_DP = 'dp';
    public const TYPE_LUNAS = 'lunas';

    public const METHOD_TUNAI = 'tunai';
    public const METHOD_TRANSFER = 'transfer';

    protected $fillable = [
        'order_id',
        'user_id',
        'amount_paid',
        'payment_type',
        'payment_method',
        'date_paid',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount_paid' => 'float',
            'date_paid' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

