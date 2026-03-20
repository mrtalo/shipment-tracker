<?php

namespace App\Services;

use App\Enums\PacketStatus;
use App\Exceptions\InvalidPacketTransitionException;
use App\Models\Packet;
use Illuminate\Database\Eloquent\Collection;

class PacketService
{
    public function create(array $data): Packet
    {
        return Packet::create($data);
    }

    public function updateStatus(Packet $packet, string $newStatus): Packet
    {
        $newStatusEnum = PacketStatus::from($newStatus);

        if (! $packet->status->canTransitionTo($newStatusEnum)) {
            throw InvalidPacketTransitionException::fromTransition(
                $packet->status,
                $newStatusEnum
            );
        }

        $packet->status = $newStatusEnum;
        $packet->save();

        return $packet->fresh();
    }

    public function list(?string $status = null): Collection
    {
        $query = Packet::query();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    public function find(int $id): ?Packet
    {
        return Packet::find($id);
    }
}
