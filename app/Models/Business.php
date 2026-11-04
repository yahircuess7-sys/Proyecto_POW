<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'tax_id',
        'email',
        'phone',
        'address',
        'status',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    /**
     * Usuarios asociados a la empresa con sus respectivos roles y membresías.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'business_user')
            ->withPivot('role', 'is_default')
            ->withTimestamps();
    }

    /**
     * Catálogo de productos de la empresa.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Registro de ventas y transacciones de la empresa.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Detalle de líneas de venta de la empresa.
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Invitaciones enviadas para unirse a la empresa.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(BusinessInvitation::class);
    }

    /**
     * Registros de auditoría asociados a la empresa.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Clientes registrados en la empresa.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Proveedores registrados en la empresa.
     */
    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    /**
     * Compras registradas en la empresa.
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Cajas registradas en la empresa.
     */
    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegister::class);
    }

    /**
     * Turnos y arqueos de caja.
     */
    public function cashShifts(): HasMany
    {
        return $this->hasMany(CashShift::class);
    }

    /**
     * Devoluciones de ventas.
     */
    public function saleReturns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    /**
     * Historial de suscripciones de la empresa.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Suscripción más reciente de la empresa.
     */
    public function currentSubscription(): ?Subscription
    {
        return $this->subscriptions()->latest('id')->first();
    }

    /**
     * Plan actualmente contratado o asignado.
     */
    public function currentPlan(): ?Plan
    {
        return $this->currentSubscription()?->plan;
    }

    /**
     * Valida en el servidor si la empresa tiene su suscripción activa o en prueba vigente.
     */
    public function hasActiveSubscription(): bool
    {
        $sub = $this->currentSubscription();
        return $sub ? $sub->isActive() : false;
    }

    /**
     * Control estricto de límite de usuarios permitidos según el plan.
     */
    public function canAddUser(): bool
    {
        $plan = $this->currentPlan();
        if (! $plan || $plan->isUnlimitedUsers()) {
            return true;
        }

        return $this->users()->count() < $plan->max_users;
    }

    /**
     * Control estricto de límite de productos permitidos según el plan.
     */
    public function canAddProduct(): bool
    {
        $plan = $this->currentPlan();
        if (! $plan || $plan->isUnlimitedProducts()) {
            return true;
        }

        return $this->products()->withoutBusinessScope()->count() < $plan->max_products;
    }

    /**
     * Control estricto de límite de cajas registradoras permitidas según el plan.
     */
    public function canAddCashRegister(): bool
    {
        $plan = $this->currentPlan();
        if (! $plan || $plan->isUnlimitedCashRegisters()) {
            return true;
        }

        return $this->cashRegisters()->withoutBusinessScope()->count() < $plan->max_cash_registers;
    }

    /**
     * Verifica si una funcionalidad avanzada está autorizada en el plan actual de la empresa.
     */
    public function hasFeature(string $feature): bool
    {
        $plan = $this->currentPlan();
        if (! $plan) {
            return true;
        }

        return $plan->hasFeature($feature);
    }

    /**
     * Comprueba si la empresa se encuentra activa en la plataforma.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
