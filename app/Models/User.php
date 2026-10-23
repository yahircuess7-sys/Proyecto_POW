<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'current_business_id',
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
            'password' => 'hashed',
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
     * Obtiene el rol del usuario en su empresa activa ('owner', 'admin', 'cajero').
     */
    public function roleInCurrentBusiness(): string
    {
        if ($this->relationLoaded('businesses')) {
            $currentPivot = $this->businesses->firstWhere('id', $this->current_business_id);
            if ($currentPivot && $currentPivot->pivot) {
                return (string) $currentPivot->pivot->role;
            }
        }

        $pivot = $this->businesses()->where('business_id', $this->current_business_id)->first();
        if ($pivot && $pivot->pivot) {
            return (string) $pivot->pivot->role;
        }

        return $this->role ?: 'cajero';
    }

    /**
     * Transacciones de venta registradas por este usuario.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Verifica si el usuario posee rol administrativo en la empresa o sistema.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin' || in_array($this->roleInCurrentBusiness(), ['owner', 'admin'], true);
    }
}
