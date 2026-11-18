<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    protected $fillable = [
        'name',
        'type',
        'current_stock',
        'unit',
        'minimal_stock_alert',
    ];

    protected function casts(): array
    {
        return [
            'current_stock' => 'float',
            'minimal_stock_alert' => 'float',
        ];
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('current_stock', '<=', 'minimal_stock_alert');
    }

    public function isLowStock(): bool
    {
        return (float) $this->current_stock <= (float) $this->minimal_stock_alert;
    }

    public function formattedStock(): string
    {
        $value = (float) $this->current_stock;

        return fmod($value, 1.0) === 0.0
            ? number_format($value, 0, ',', '.')
            : number_format($value, 2, ',', '.');
    }
}

