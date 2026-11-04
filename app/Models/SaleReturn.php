<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturn extends Model
{
    use HasFactory, BelongsToBusiness;

    public const METHOD_CASH = 'cash';
    public const METHOD_CREDIT_NOTE = 'credit_note';
    public const METHOD_CARD_REVERSAL = 'card_reversal';

    protected $fillable = [
        'business_id',
        'sale_id',
        'user_id',
        'cash_shift_id',
        'return_number',
        'total_refunded',
        'refund_method',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'total_refunded' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashShift(): BelongsTo
    {
        return $this->belongsTo(CashShift::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }
}
