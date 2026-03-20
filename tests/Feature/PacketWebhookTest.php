<?php

namespace Tests\Feature;

use App\Jobs\SendPacketStatusWebhookJob;
use App\Models\Packet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PacketWebhookTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dispatches_job_after_status_change(): void
    {
        Queue::fake();

        $packet = Packet::factory()->create(['status' => 'created']);

        $this->putJson("/api/packets/{$packet->id}/status", [
            'status' => 'in_transit',
        ]);

        Queue::assertPushed(SendPacketStatusWebhookJob::class, function ($job) use ($packet) {
            return $job->trackingCode === $packet->tracking_code
                && $job->oldStatus === 'created'
                && $job->newStatus === 'in_transit';
        });
    }

    public function test_job_not_dispatched_on_invalid_transition(): void
    {
        Queue::fake();

        $packet = Packet::factory()->create(['status' => 'created']);

        $this->putJson("/api/packets/{$packet->id}/status", [
            'status' => 'delivered',
        ]);

        Queue::assertNothingPushed();
    }

    public function test_job_sends_correct_payload(): void
    {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        config(['services.webhook.url' => 'https://webhook.test']);

        $packet = Packet::factory()->create([
            'tracking_code' => 'TEST-123',
            'status' => 'created',
        ]);

        $packet->status = 'in_transit';
        $packet->save();
        $packet->refresh();

        $job = new SendPacketStatusWebhookJob(
            'TEST-123',
            'created',
            'in_transit',
            $packet->updated_at->toIso8601String()
        );

        $job->handle();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://webhook.test'
                && $request['tracking_code'] === 'TEST-123'
                && $request['old_status'] === 'created'
                && $request['new_status'] === 'in_transit'
                && isset($request['updated_at']);
        });
    }

    public function test_job_skips_when_webhook_url_not_configured(): void
    {
        Http::fake();

        config(['services.webhook.url' => null]);

        $job = new SendPacketStatusWebhookJob(
            'TEST-123',
            'created',
            'in_transit',
            now()->toIso8601String()
        );

        $job->handle();

        Http::assertNothingSent();
    }
}
