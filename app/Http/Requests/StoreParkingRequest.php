<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreParkingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage-parkings');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'separate_management' => ['boolean'],
            'management_name' => ['required_if:separate_management,1', 'nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'total_spots' => ['required', 'integer', 'min:1'],
            'capacity_mode' => ['required', 'string', 'in:shared,per_product'],
            'is_active' => ['boolean'],
            'car_wash_interior_price' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'car_wash_exterior_price' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'car_wash_both_price' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
        ];
    }

    public function attributes(): array
    {
        return [
            'car_wash_interior_price' => 'prezzo del lavaggio interno',
            'car_wash_exterior_price' => 'prezzo del lavaggio esterno',
            'car_wash_both_price' => 'prezzo del lavaggio interno + esterno',
        ];
    }
}
