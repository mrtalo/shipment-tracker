<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePacketRequest;
use App\Http\Resources\PacketResource;
use App\Services\PacketService;

class PacketController extends Controller
{
    public function __construct(
        private readonly PacketService $packetService
    ) {}

    public function store(StorePacketRequest $request)
    {
        $packet = $this->packetService->create($request->validated());

        return (new PacketResource($packet))
            ->response()
            ->setStatusCode(201);
    }
}
