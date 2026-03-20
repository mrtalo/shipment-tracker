<?php

namespace Tests\Feature;

use App\Models\Packet;
use App\Services\WebhookSignatureService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use DatabaseTransactions;

    private WebhookSignatureService $signatureService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signatureService = new WebhookSignatureService;
        Queue::fake();
        config(['services.carrier.webhook_secret' => 'test-secret']);
    }

    public function test_webhook_endpoint_has_rate_limiting(): void
    {
        $successCount = 0;

        for ($i = 0; $i < 60; $i++) {
            Packet::factory()->create([
                'tracking_code' => "TEST-{$i}",
                'status' => 'in_transit',
            ]);

            $payload = [
                'tracking_code' => "TEST-{$i}",
                'status' => 'delivered',
                'timestamp' => now()->toIso8601String(),
            ];

            $signature = $this->signatureService->generate($payload, 'test-secret');

            $response = $this->postJson('/api/webhooks/carrier', array_merge($payload, [
                'signature' => $signature,
            ]));

            if ($response->status() === 200) {
                $successCount++;
            }
        }

        $this->assertEquals(60, $successCount);

        $payload = [
            'tracking_code' => 'TEST-EXTRA',
            'status' => 'delivered',
            'timestamp' => now()->toIso8601String(),
        ];

        Packet::factory()->create([
            'tracking_code' => 'TEST-EXTRA',
            'status' => 'in_transit',
        ]);

        $signature = $this->signatureService->generate($payload, 'test-secret');

        $response = $this->postJson('/api/webhooks/carrier', array_merge($payload, [
            'signature' => $signature,
        ]));

        $this->assertEquals(429, $response->status());
    }
}
