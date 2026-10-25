<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Services\TenantManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $product = $this->route('product');
        if ($product instanceof Product) {
            return $user->can('update', $product);
        }

        return $user->can('create', Product::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $product = $this->route('product');
        $productId = is_object($product) ? $product->id : $product;
        $businessId = app(TenantManager::class)->getTenantId() ?: auth()->user()?->current_business_id;

        return [
            'sku' => [
                'nullable',
                'max:255',
                'alpha_dash',
                Rule::unique('products', 'sku')
                    ->where(fn ($query) => $query->where('business_id', $businessId))
                    ->ignore($productId),
            ],
            'barcode' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:active,inactive'],
            'unit' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * Custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'sku' => 'código SKU',
            'barcode' => 'código de barras',
            'name' => 'nombre del producto',
            'category' => 'categoría',
            'description' => 'descripción',
            'cost' => 'costo unitario',
            'price' => 'precio de venta',
            'stock' => 'existencias',
            'min_stock' => 'stock mínimo',
            'status' => 'estado',
            'unit' => 'unidad de medida',
        ];
    }
}
