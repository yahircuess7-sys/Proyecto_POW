<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory, BelongsToBusiness;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'business_id',
        'tax_id',
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
        'status',
        'notes',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class)->orderByDesc('purchase_date');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function totalPurchasesCount(): int
    {
        return $this->purchases()->count();
    }

    public function totalPurchased(): float
    {
        return (float) $this->purchases()->sum('total');
    }
}
