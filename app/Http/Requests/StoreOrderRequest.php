<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name'          => ['required', 'string', 'max:255'],
            'customer_email'         => ['required', 'email', 'max:255'],
            'payment_method'         => ['required', 'in:card,cash,transfer'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.name'           => ['required', 'string'],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
            'items.*.unit_price'     => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'              => 'El pedido debe contener al menos un ítem.',
            'items.*.name.required'       => 'Cada ítem debe tener un nombre.',
            'items.*.quantity.required'   => 'Cada ítem debe tener una cantidad.',
            'items.*.unit_price.required' => 'Cada ítem debe tener un precio.',
        ];
    }
}
