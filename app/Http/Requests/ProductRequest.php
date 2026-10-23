<?php

namespace App\Http\Requests;

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
        return true;
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
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
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
            'name' => 'nombre del producto',
            'category' => 'categoría',
            'description' => 'descripción',
            'price' => 'precio',
            'stock' => 'stock',
            'unit' => 'unidad de medida',
        ];
    }
}
