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

    protected static function booted(): void
    {
        static::deleting(function () {
            throw new \LogicException('Las notas de devolución y crédito registradas no pueden eliminarse físicamente para preservar la trazabilidad tributaria y de inventario.');
        });
    }

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

    /**
     * Calcula el monto proporcional de IVA restituido/cancelado en la devolución
     * conforme al Art. 123 de la Ley No. 822 (Ley de Concertación Tributaria de Nicaragua).
     */
    public function restitutedTaxAmount(): float
    {
        $sale = $this->sale;
        if (! $sale || (float) $sale->tax <= 0 || (float) $sale->subtotal <= 0) {
            return 0.00;
        }

        $effectiveBase = max(0.01, (float) $sale->subtotal - (float) $sale->discount);
        $taxRatio = (float) $sale->tax / $effectiveBase;

        return round((float) $this->total_refunded * $taxRatio, 2);
    }

    /**
     * Total general acreditado incluyendo el IVA restituido.
     */
    public function totalWithTax(): float
    {
        return round((float) $this->total_refunded + $this->restitutedTaxAmount(), 2);
    }
}
