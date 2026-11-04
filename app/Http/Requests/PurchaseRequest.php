<?php

namespace App\Http\Requests;

use App\Models\Purchase;
use App\Services\TenantManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Purchase::class);
    }

    public function rules(): array
    {
        $businessId = app(TenantManager::class)->getTenantId() ?: auth()->user()?->current_business_id;

        return [
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->where(fn ($q) => $q->where('business_id', $businessId)),
            ],
            'purchase_date' => ['required', 'date'],
            'supplier_invoice_number' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:pending,received,completed'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(fn ($q) => $q->where('business_id', $businessId)),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'supplier_id' => 'proveedor',
            'purchase_date' => 'fecha de compra',
            'supplier_invoice_number' => 'número de factura de proveedor',
            'status' => 'estado de la compra',
            'discount' => 'descuento global',
            'items' => 'artículos de la compra',
            'items.*.product_id' => 'producto',
            'items.*.quantity' => 'cantidad',
            'items.*.unit_cost' => 'costo unitario',
            'items.*.discount' => 'descuento por producto',
        ];
    }
}
