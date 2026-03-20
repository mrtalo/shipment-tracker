<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePacketRequest;
use App\Http\Requests\UpdatePacketStatusRequest;
use App\Http\Resources\PacketResource;
use App\Models\Packet;
use App\Services\PacketService;
use Illuminate\Http\JsonResponse;

class PacketController extends Controller
{
    public function __construct(
        private readonly PacketService $packetService
    ) {}

    public function store(StorePacketRequest $request): JsonResponse
    {
        $packet = $this->packetService->create($request->validated());

        return (new PacketResource($packet))
            ->response()
            ->setStatusCode(201);
    }

    public function updateStatus(
        UpdatePacketStatusRequest $request,
        Packet $packet
    ): JsonResponse {
        $updatedPacket = $this->packetService->updateStatus(
            $packet,
            $request->validated('status')
        );

        return (new PacketResource($updatedPacket))
            ->response()
            ->setStatusCode(200);
    }
}
