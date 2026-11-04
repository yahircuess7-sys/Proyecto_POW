<?php

namespace App\Http\Requests;

use App\Models\Supplier;
use App\Services\TenantManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $supplier = $this->route('supplier');
        if ($supplier instanceof Supplier) {
            return $user->can('update', $supplier);
        }

        return $user->can('create', Supplier::class);
    }

    public function rules(): array
    {
        $supplier = $this->route('supplier');
        $supplierId = is_object($supplier) ? $supplier->id : $supplier;
        $businessId = app(TenantManager::class)->getTenantId() ?: auth()->user()?->current_business_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'tax_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('suppliers', 'tax_id')
                    ->where(fn ($query) => $query->where('business_id', $businessId))
                    ->ignore($supplierId),
            ],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'in:active,inactive'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'razón social o nombre del proveedor',
            'tax_id' => 'identificación fiscal (RUC / RFC / NIT)',
            'contact_name' => 'nombre del contacto',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
            'address' => 'dirección fiscal o bodega',
            'status' => 'estado',
            'notes' => 'observaciones',
        ];
    }
}
