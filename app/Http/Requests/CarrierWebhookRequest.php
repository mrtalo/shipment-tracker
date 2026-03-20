<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CarrierWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tracking_code' => 'required|string',
            'status' => 'required|string|in:delivered',
            'timestamp' => 'required|date',
            'signature' => 'required|string',
        ];
    }
}
