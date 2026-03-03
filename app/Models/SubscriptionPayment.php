<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'amount',
        'currency',
        'status',
        'reference',
        'merchant_reference',
        'order_tracking_id',
        'pesapal_tracking_id',
        'redirect_url',
        'callback_url',
        'ipn_id',
        'response_payload',
        'status_payload',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'response_payload' => 'array',
            'status_payload' => 'array',
            'confirmed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
