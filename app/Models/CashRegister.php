<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    use HasFactory, BelongsToBusiness;

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ACTIVE = 'closed';

    protected $fillable = [
        'business_id',
        'name',
        'status',
        'notes',
    ];

    public function shifts(): HasMany
    {
        return $this->hasMany(CashShift::class);
    }

    public function currentShift(): ?CashShift
    {
        return $this->shifts()->where('status', CashShift::STATUS_OPEN)->latest('opened_at')->first();
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function hasOpenShift(): bool
    {
        return $this->shifts()->where('status', CashShift::STATUS_OPEN)->exists();
    }
}
