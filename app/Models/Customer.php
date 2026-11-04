<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory, BelongsToBusiness;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'business_id',
        'document_type',
        'document_number',
        'name',
        'email',
        'phone',
        'address',
        'status',
        'notes',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class)->orderByDesc('created_at');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function totalSpent(): float
    {
        return (float) $this->sales()->sum('total');
    }

    public function totalPurchasesCount(): int
    {
        return $this->sales()->count();
    }
}
