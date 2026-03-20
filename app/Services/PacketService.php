<?php

namespace App\Services;

use App\Enums\PacketStatus;
use App\Exceptions\InvalidPacketTransitionException;
use App\Jobs\SendPacketStatusWebhookJob;
use App\Models\Packet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class PacketService
{
    public function create(array $data): Packet
    {
        $packet = Packet::create($data);

        $this->clearListCaches();

        return $packet;
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

        $oldStatus = $packet->status->value;

        $packet->status = $newStatusEnum;
        $packet->save();

        SendPacketStatusWebhookJob::dispatch(
            $packet->tracking_code,
            $oldStatus,
            $newStatusEnum->value,
            $packet->updated_at->toIso8601String()
        );

        $this->clearPacketCaches($packet->id);

        return $packet->fresh();
    }

    public function list(?string $status = null): Builder
    {
        $query = Packet::query();

        if ($status) {
            $query->where('status', $status);
        }

        return $query;
    }

    public function find(int $id): ?Packet
    {
        return Cache::remember("packets:{$id}", 300, function () use ($id) {
            return Packet::find($id);
        });
    }

    private function clearListCaches(): void
    {
        Cache::forget('packets:list:all');
        Cache::forget('packets:list:created');
        Cache::forget('packets:list:in_transit');
        Cache::forget('packets:list:delivered');
        Cache::forget('packets:list:failed');
    }

    private function clearPacketCaches(int $id): void
    {
        Cache::forget("packets:{$id}");
        $this->clearListCaches();
    }
}
