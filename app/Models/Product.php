<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, BelongsToBusiness;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'business_id',
        'sku',
        'barcode',
        'name',
        'description',
        'cost',
        'price',
        'stock',
        'min_stock',
        'status',
        'category',
        'unit',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'price' => 'decimal:2',
            'stock' => 'integer',
            'min_stock' => 'integer',
        ];
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class)->orderByDesc('created_at');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isOutOfStock(): bool
    {
        return $this->stock <= 0;
    }

    public function isCriticalStock(): bool
    {
        return $this->stock > 0 && $this->stock <= $this->min_stock;
    }

    public function stockStatus(): string
    {
        if ($this->isOutOfStock()) {
            return 'sin_stock';
        }
        if ($this->isCriticalStock()) {
            return 'critico';
        }
        return 'disponible';
    }

    /**
     * Margen bruto porcentual: ((precio - costo) / precio) * 100
     */
    public function grossMarginPercentage(): ?float
    {
        if (! $this->price || (float) $this->price <= 0) {
            return null;
        }

        $cost = (float) ($this->cost ?? 0);
        $margin = (($this->price - $cost) / $this->price) * 100;

        return round($margin, 1);
    }
}
