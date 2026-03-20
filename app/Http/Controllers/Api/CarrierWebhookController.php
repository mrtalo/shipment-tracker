<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CarrierWebhookRequest;
use App\Models\Packet;
use App\Services\PacketService;
use App\Services\WebhookSignatureService;
use Illuminate\Http\JsonResponse;

class CarrierWebhookController extends Controller
{
    public function __construct(
        private readonly PacketService $packetService,
        private readonly WebhookSignatureService $signatureService
    ) {}

    public function handle(CarrierWebhookRequest $request): JsonResponse
    {
        $signature = $request->input('signature');

        if (! $this->signatureService->validate(
            $request->except('signature'),
            $signature,
            config('services.carrier.webhook_secret')
        )) {
            return response()->json([
                'message' => 'Invalid signature',
            ], 401);
        }

        $packet = Packet::where('tracking_code', $request->input('tracking_code'))->first();

        if (! $packet) {
            return response()->json([
                'message' => 'Packet not found',
            ], 404);
        }

        $this->packetService->updateStatus($packet, $request->input('status'));

        return response()->json([
            'message' => 'Webhook processed successfully',
        ]);
    }
}
