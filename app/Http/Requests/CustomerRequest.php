<?php

namespace App\Http\Requests;

use App\Models\Customer;
use App\Services\TenantManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $customer = $this->route('customer');
        if ($customer instanceof Customer) {
            return $user->can('update', $customer);
        }

        return $user->can('create', Customer::class);
    }

    public function rules(): array
    {
        $customer = $this->route('customer');
        $customerId = is_object($customer) ? $customer->id : $customer;
        $businessId = app(TenantManager::class)->getTenantId() ?: auth()->user()?->current_business_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'string', 'in:DNI,Cédula,RUC,RFC,Pasaporte,Otro'],
            'document_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('customers', 'document_number')
                    ->where(fn ($query) => $query->where('business_id', $businessId))
                    ->ignore($customerId),
            ],
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
            'name' => 'nombre del cliente',
            'document_type' => 'tipo de documento',
            'document_number' => 'número de documento',
            'email' => 'correo electrónico',
            'phone' => 'teléfono de contacto',
            'address' => 'dirección',
            'status' => 'estado',
            'notes' => 'observaciones',
        ];
    }
}
