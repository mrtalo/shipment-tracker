<?php

namespace App\Http\Resources;

use App\Models\Packet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Packet
 */
class PacketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tracking_code' => $this->tracking_code,
            'recipient_name' => $this->recipient_name,
            'recipient_email' => $this->recipient_email,
            'destination_address' => $this->destination_address,
            'weight_grams' => $this->weight_grams,
            'status' => $this->status->value,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
