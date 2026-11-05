<?php

namespace App\Models;

use App\Services\TenantManager;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_CASHIER = 'cajero';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'google_id',
        'avatar',
        'password',
        'role',
        'is_super_admin',
        'status',
        'current_business_id',
        'email_verified_at',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    /**
     * Negocios / empresas a las que pertenece el usuario (membresías).
     */
    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class, 'business_user')
            ->withPivot('role', 'is_default')
            ->withTimestamps();
    }

    /**
     * Negocio activo en el que se encuentra trabajando el usuario.
     */
    public function currentBusiness(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'current_business_id');
    }

    /**
     * Retorna el ID del negocio activo del usuario o el contexto en TenantManager.
     */
    public function currentBusinessId(): ?int
    {
        return $this->current_business_id
            ? (int) $this->current_business_id
            : app(TenantManager::class)->getTenantId();
    }

    /**
     * Verifica si el usuario pertenece a un negocio específico.
     */
    public function belongsToBusiness(?int $businessId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $businessId) {
            return false;
        }

        if ($this->relationLoaded('businesses')) {
            return $this->businesses->contains('id', $businessId);
        }

        return $this->businesses()->where('businesses.id', $businessId)->exists();
    }

    /**
     * Verifica si el usuario tiene acceso al negocio activo o indicado.
     */
    public function hasBusinessAccess(?int $businessId = null): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $target = $businessId ?: ($this->current_business_id ?: app(TenantManager::class)->getTenantId());
        return $this->belongsToBusiness($target);
    }

    /**
     * Obtiene el rol efectivo del usuario en una empresa específica o en la empresa activa.
     * Posibles valores: 'super_admin', 'owner', 'admin', 'cajero'.
     */
    public function roleInBusiness(?int $businessId = null): string
    {
        if ($this->isSuperAdmin()) {
            return self::ROLE_SUPER_ADMIN;
        }

        $targetBusinessId = $businessId ?: ($this->current_business_id ?: app(TenantManager::class)->getTenantId());

        if ($targetBusinessId) {
            if ($this->relationLoaded('businesses')) {
                $currentPivot = $this->businesses->firstWhere('id', $targetBusinessId);
                if ($currentPivot && $currentPivot->pivot && ! empty($currentPivot->pivot->role)) {
                    return (string) $currentPivot->pivot->role;
                }
            }

            $pivot = $this->businesses()->where('businesses.id', $targetBusinessId)->first();
            if ($pivot && $pivot->pivot && ! empty($pivot->pivot->role)) {
                return (string) $pivot->pivot->role;
            }
        }

        return $this->role ?: self::ROLE_CASHIER;
    }

    /**
     * Obtiene el rol del usuario en su empresa activa ('super_admin', 'owner', 'admin', 'cajero').
     */
    public function roleInCurrentBusiness(): string
    {
        return $this->roleInBusiness($this->current_business_id);
    }

    /**
     * Etiqueta legible del rol actual.
     */
    public function roleLabel(?int $businessId = null): string
    {
        return match ($this->roleInBusiness($businessId)) {
            self::ROLE_SUPER_ADMIN => 'Super Admin',
            self::ROLE_OWNER => 'Propietario',
            self::ROLE_ADMIN => 'Administrador',
            default => 'Cajero',
        };
    }

    /**
     * Transacciones de venta registradas por este usuario.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Verifica si el usuario es Super Administrador global de la plataforma.
     */
    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin || $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Verifica si el usuario es Propietario del negocio activo (o Super Admin).
     */
    public function isOwner(?int $businessId = null): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->roleInBusiness($businessId) === self::ROLE_OWNER;
    }

    /**
     * Verifica si el usuario posee permisos administrativos (Super Admin, Propietario o Administrador).
     */
    public function isAdmin(?int $businessId = null): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $effectiveRole = $this->roleInBusiness($businessId);

        return in_array($effectiveRole, [self::ROLE_SUPER_ADMIN, self::ROLE_OWNER, self::ROLE_ADMIN], true);
    }

    /**
     * Verifica si el usuario tiene rol exclusivo de Cajero en el negocio.
     */
    public function isCashier(?int $businessId = null): bool
    {
        return ! $this->isAdmin($businessId);
    }
}
