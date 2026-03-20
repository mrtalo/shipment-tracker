<?php

namespace App\Models;

use App\Enums\PacketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property PacketStatus $status
 */
class Packet extends Model
{
    use HasFactory;

    protected $fillable = [
        'tracking_code',
        'recipient_name',
        'recipient_email',
        'destination_address',
        'weight_grams',
    ];

    protected $attributes = [
        'status' => 'created',
    ];

    protected function casts(): array
    {
        return [
            'status' => PacketStatus::class,
            'weight_grams' => 'integer',
        ];
    }
}
