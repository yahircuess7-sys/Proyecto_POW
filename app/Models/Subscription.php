<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_TRIALING = 'trialing';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAST_DUE = 'past_due';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'business_id',
        'plan_id',
        'status',
        'billing_cycle',
        'price',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'cancelled_at',
        'payment_provider',
        'provider_subscription_id',
        'provider_customer_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isActive(): bool
    {
        if (in_array($this->status, [self::STATUS_SUSPENDED, self::STATUS_PENDING, self::STATUS_PAST_DUE], true)) {
            return false;
        }

        if ($this->status === self::STATUS_CANCELLED) {
            return $this->ends_at !== null && $this->ends_at->isFuture();
        }

        if ($this->status === self::STATUS_TRIALING) {
            return $this->trial_ends_at === null || $this->trial_ends_at->isFuture();
        }

        if ($this->status === self::STATUS_ACTIVE) {
            return $this->ends_at === null || $this->ends_at->isFuture();
        }

        return false;
    }

    public function isTrialing(): bool
    {
        return $this->status === self::STATUS_TRIALING;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPastDue(): bool
    {
        return $this->status === self::STATUS_PAST_DUE || ($this->ends_at && $this->ends_at->isPast() && $this->status === self::STATUS_ACTIVE);
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function statusLabel(): string
    {
        if ($this->isPastDue()) {
            return 'Vencida';
        }

        return match ($this->status) {
            self::STATUS_ACTIVE => 'Activa',
            self::STATUS_TRIALING => 'En Período de Prueba',
            self::STATUS_PENDING => 'Pendiente de Pago',
            self::STATUS_CANCELLED => 'Cancelada',
            self::STATUS_SUSPENDED => 'Suspendida',
            default => ucfirst($this->status),
        };
    }

    public function statusBadgeClasses(): string
    {
        if ($this->isPastDue()) {
            return 'bg-amber-100 text-amber-800 border-amber-200';
        }

        return match ($this->status) {
            self::STATUS_ACTIVE => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            self::STATUS_TRIALING => 'bg-blue-100 text-blue-800 border-blue-200',
            self::STATUS_PENDING => 'bg-purple-100 text-purple-800 border-purple-200',
            self::STATUS_CANCELLED => 'bg-slate-100 text-slate-700 border-slate-200',
            self::STATUS_SUSPENDED => 'bg-rose-100 text-rose-800 border-rose-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }
}
