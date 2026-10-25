<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryMovement extends Model
{
    use HasFactory, BelongsToBusiness;

    public const TYPE_INITIAL = 'initial';
    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_SALE = 'sale';

    protected $fillable = [
        'business_id',
        'product_id',
        'user_id',
        'type',
        'quantity',
        'previous_stock',
        'new_stock',
        'unit_cost',
        'reason',
        'reference_type',
        'reference_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'previous_stock' => 'integer',
            'new_stock' => 'integer',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_INITIAL => 'Inventario Inicial',
            self::TYPE_IN => 'Entrada / Compra',
            self::TYPE_OUT => 'Salida / Merma',
            self::TYPE_ADJUSTMENT => 'Ajuste Físico',
            self::TYPE_SALE => 'Venta en Caja',
            default => ucfirst($this->type),
        };
    }

    public function typeBadgeClass(): string
    {
        return match ($this->type) {
            self::TYPE_INITIAL, self::TYPE_IN => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::TYPE_OUT, self::TYPE_SALE => 'bg-rose-50 text-rose-700 border-rose-200',
            self::TYPE_ADJUSTMENT => 'bg-amber-50 text-amber-700 border-amber-200',
            default => 'bg-slate-50 text-slate-700 border-slate-200',
        };
    }
}
