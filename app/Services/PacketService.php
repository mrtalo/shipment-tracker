<?php

namespace App\Services;

use App\Models\Packet;
use Illuminate\Database\Eloquent\Collection;

class PacketService
{
    public function create(array $data): Packet
    {
        return Packet::create($data);
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
