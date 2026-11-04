<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashShift extends Model
{
    use HasFactory, BelongsToBusiness;

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'business_id',
        'cash_register_id',
        'user_id',
        'closed_by_user_id',
        'opened_at',
        'closed_at',
        'initial_cash',
        'expected_cash',
        'closing_cash',
        'difference',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'initial_cash' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'closing_cash' => 'decimal:2',
            'difference' => 'decimal:2',
        ];
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function cashSalesTotal(): float
    {
        return (float) $this->sales()
            ->where('payment_method', 'cash')
            ->where('status', '!=', 'cancelled')
            ->sum('total');
    }

    public function cardSalesTotal(): float
    {
        return (float) $this->sales()
            ->where('payment_method', 'card')
            ->where('status', '!=', 'cancelled')
            ->sum('total');
    }

    public function transferSalesTotal(): float
    {
        return (float) $this->sales()
            ->where('payment_method', 'transfer')
            ->where('status', '!=', 'cancelled')
            ->sum('total');
    }

    public function cashInTotal(): float
    {
        return (float) $this->movements()->where('type', 'in')->sum('amount');
    }

    public function cashOutTotal(): float
    {
        return (float) $this->movements()->where('type', 'out')->sum('amount');
    }

    public function cashReturnsTotal(): float
    {
        return (float) $this->returns()->where('refund_method', 'cash')->sum('total_refunded');
    }

    public function calculateExpectedCash(): float
    {
        return (float) ($this->initial_cash + $this->cashSalesTotal() + $this->cashInTotal() - $this->cashOutTotal() - $this->cashReturnsTotal());
    }
}
