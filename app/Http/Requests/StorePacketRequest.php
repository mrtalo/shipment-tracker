<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePacketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tracking_code' => ['required', 'string', 'max:255', 'unique:packets,tracking_code'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_email' => ['required', 'email', 'max:255'],
            'destination_address' => ['required', 'string'],
            'weight_grams' => ['required', 'integer', 'min:1'],
        ];
    }
}
