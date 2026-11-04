<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory, BelongsToBusiness;

    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_CANCELLED = 'cancelled';

    protected static function booted(): void
    {
        static::deleting(function () {
            throw new \LogicException('Los comprobantes de venta confirmados son documentos históricos inmutables y no pueden eliminarse físicamente (Art. 103 Código Tributario de Nicaragua - Ley No. 562). Utilice devoluciones o anulaciones controladas.');
        });
    }

    protected $fillable = [
        'business_id',
        'cash_shift_id',
        'invoice_number',
        'user_id',
        'customer_id',
        'customer_name',
        'customer_document',
        'payment_method',
        'subtotal',
        'discount',
        'tax',
        'total',
        'paid_amount',
        'change_amount',
        'status',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cashShift(): BelongsTo
    {
        return $this->belongsTo(CashShift::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function isPartiallyRefunded(): bool
    {
        return $this->status === self::STATUS_PARTIALLY_REFUNDED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canBeRefunded(): bool
    {
        return ! in_array($this->status, [self::STATUS_REFUNDED, self::STATUS_CANCELLED], true);
    }

    public function totalRefundedAmount(): float
    {
        return (float) $this->returns()->sum('total_refunded');
    }

    public function refundableAmount(): float
    {
        return max(0, (float) $this->total - $this->totalRefundedAmount());
    }
}
