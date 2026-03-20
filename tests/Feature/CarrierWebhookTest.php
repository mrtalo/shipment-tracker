<?php

namespace Tests\Feature;

use App\Models\Packet;
use App\Services\WebhookSignatureService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CarrierWebhookTest extends TestCase
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

    public function test_processes_valid_webhook_successfully(): void
    {
        $packet = Packet::factory()->create([
            'tracking_code' => 'TEST123',
            'status' => 'in_transit',
        ]);

        $payload = [
            'tracking_code' => 'TEST123',
            'status' => 'delivered',
            'timestamp' => now()->toIso8601String(),
        ];

        $signature = $this->signatureService->generate($payload, 'test-secret');

        $response = $this->postJson('/api/webhooks/carrier', array_merge($payload, [
            'signature' => $signature,
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Webhook processed successfully',
            ]);

        $this->assertDatabaseHas('packets', [
            'tracking_code' => 'TEST123',
            'status' => 'delivered',
        ]);
    }

    public function test_rejects_webhook_with_invalid_signature(): void
    {
        Packet::factory()->create([
            'tracking_code' => 'TEST123',
            'status' => 'in_transit',
        ]);

        $payload = [
            'tracking_code' => 'TEST123',
            'status' => 'delivered',
            'timestamp' => now()->toIso8601String(),
            'signature' => 'invalid-signature',
        ];

        $response = $this->postJson('/api/webhooks/carrier', $payload);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid signature',
            ]);

        $this->assertDatabaseHas('packets', [
            'tracking_code' => 'TEST123',
            'status' => 'in_transit',
        ]);
    }

    public function test_returns_404_for_non_existent_packet(): void
    {
        $payload = [
            'tracking_code' => 'NONEXISTENT',
            'status' => 'delivered',
            'timestamp' => now()->toIso8601String(),
        ];

        $signature = $this->signatureService->generate($payload, 'test-secret');

        $response = $this->postJson('/api/webhooks/carrier', array_merge($payload, [
            'signature' => $signature,
        ]));

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Packet not found',
            ]);
    }

    public function test_validates_required_fields(): void
    {
        $response = $this->postJson('/api/webhooks/carrier', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tracking_code', 'status', 'timestamp', 'signature']);
    }

    public function test_validates_status_must_be_delivered(): void
    {
        $payload = [
            'tracking_code' => 'TEST123',
            'status' => 'invalid_status',
            'timestamp' => now()->toIso8601String(),
            'signature' => 'some-signature',
        ];

        $response = $this->postJson('/api/webhooks/carrier', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_validates_timestamp_must_be_date(): void
    {
        $payload = [
            'tracking_code' => 'TEST123',
            'status' => 'delivered',
            'timestamp' => 'not-a-date',
            'signature' => 'some-signature',
        ];

        $response = $this->postJson('/api/webhooks/carrier', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['timestamp']);
    }
}
