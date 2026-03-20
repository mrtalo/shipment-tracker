<?php

namespace App\Services;

use App\Enums\PacketStatus;
use App\Exceptions\InvalidPacketTransitionException;
use App\Jobs\SendPacketStatusWebhookJob;
use App\Models\Packet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    public function list(?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $page = request()->query('page', 1);
        $statusKey = $status ?? 'all';
        $cacheKey = "packets:list:{$statusKey}:page:{$page}";

        return Cache::remember($cacheKey, 300, function () use ($status, $perPage) {
            $query = Packet::query();

            if ($status) {
                $query->where('status', $status);
            }

            return $query->latest()->paginate($perPage);
        });
    }

    public function find(int $id): ?Packet
    {
        return Cache::remember("packets:{$id}", 300, function () use ($id) {
            return Packet::find($id);
        });
    }

    private function clearListCaches(): void
    {
        $statuses = ['all', 'created', 'in_transit', 'delivered', 'failed'];
        foreach ($statuses as $status) {
            Cache::forget("packets:list:{$status}:page:1");
        }
    }

    private function clearPacketCaches(int $id): void
    {
        Cache::forget("packets:{$id}");
        $this->clearListCaches();
    }
}
