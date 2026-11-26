<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\ParkingProduct;
use App\Models\Reservation;

class UpdateParkingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-parkings');
    }

    public function rules(): array
    {
        return [
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
