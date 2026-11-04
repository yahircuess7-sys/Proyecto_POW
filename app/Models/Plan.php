<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_cycle',
        'trial_days',
        'max_users',
        'max_products',
        'max_cash_registers',
        'features',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'trial_days' => 'integer',
            'max_users' => 'integer',
            'max_products' => 'integer',
            'max_cash_registers' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isUnlimitedUsers(): bool
    {
        return $this->max_users === null;
    }

    public function isUnlimitedProducts(): bool
    {
        return $this->max_products === null;
    }

    public function isUnlimitedCashRegisters(): bool
    {
        return $this->max_cash_registers === null;
    }

    public function hasFeature(string $feature): bool
    {
        if (empty($this->features)) {
            return false;
        }

        $aliases = [
            'purchases' => ['purchases', 'supplier_purchases'],
            'supplier_purchases' => ['purchases', 'supplier_purchases'],
            'returns' => ['returns', 'sale_returns'],
            'sale_returns' => ['returns', 'sale_returns'],
            'reports_export' => ['reports_export', 'export_csv', 'export_pdf'],
        ];

        $keysToCheck = $aliases[$feature] ?? [$feature];
        foreach ($keysToCheck as $key) {
            if (! empty($this->features[$key])) {
                return true;
            }
        }

        return false;
    }
}
