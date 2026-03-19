<?php

namespace App\Enums;

enum PacketStatus: string
{
    case CREATED = 'created';
    case IN_TRANSIT = 'in_transit';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::CREATED => $newStatus === self::IN_TRANSIT,
            self::IN_TRANSIT => in_array($newStatus, [self::DELIVERED, self::FAILED]),
            self::DELIVERED, self::FAILED => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Created',
            self::IN_TRANSIT => 'In Transit',
            self::DELIVERED => 'Delivered',
            self::FAILED => 'Failed',
        };
    }
}
