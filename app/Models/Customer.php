<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone_whatsapp',
        'address',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function whatsappUrl(?string $message = null): string
    {
        $digits = preg_replace('/\D+/', '', $this->phone_whatsapp ?? '');
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        $base = 'https://wa.me/'.$digits;

        return $message ? $base.'?text='.rawurlencode($message) : $base;
    }
}

