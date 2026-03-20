<?php

namespace Tests\Unit;

use App\Jobs\SendPacketStatusWebhookJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SendPacketStatusWebhookJobTest extends TestCase
{
    public function test_sends_webhook_successfully(): void
    {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        config(['services.webhook.url' => 'https://webhook.test']);

        $job = new SendPacketStatusWebhookJob(
            'PKT-12345',
            'created',
            'in_transit',
            '2026-03-19T21:30:00+00:00'
        );

        $job->handle();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://webhook.test'
                && $request['tracking_code'] === 'PKT-12345'
                && $request['old_status'] === 'created'
                && $request['new_status'] === 'in_transit'
                && $request['updated_at'] === '2026-03-19T21:30:00+00:00';
        });
    }

    public function test_throws_exception_on_http_error(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Server error'], 500),
        ]);

        config(['services.webhook.url' => 'https://webhook.test']);

        $job = new SendPacketStatusWebhookJob(
            'PKT-12345',
            'created',
            'in_transit',
            '2026-03-19T21:30:00+00:00'
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Webhook failed with status 500');

        $job->handle();
    }

    public function test_unique_id_prevents_duplicates(): void
    {
        $job = new SendPacketStatusWebhookJob(
            'PKT-12345',
            'created',
            'in_transit',
            '2026-03-19T21:30:00+00:00'
        );

        $this->assertEquals('PKT-12345-in_transit', $job->uniqueId());
    }

    public function test_failed_method_logs_error(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Webhook delivery failed after all retries', [
                'tracking_code' => 'PKT-12345',
                'old_status' => 'created',
                'new_status' => 'in_transit',
                'error' => 'Connection timeout',
            ]);

        $job = new SendPacketStatusWebhookJob(
            'PKT-12345',
            'created',
            'in_transit',
            '2026-03-19T21:30:00+00:00'
        );

        $exception = new \Exception('Connection timeout');
        $job->failed($exception);
    }

    public function test_job_has_correct_retry_configuration(): void
    {
        $job = new SendPacketStatusWebhookJob(
            'PKT-12345',
            'created',
            'in_transit',
            '2026-03-19T21:30:00+00:00'
        );

        $this->assertEquals(2, $job->tries);
        $this->assertEquals(30, $job->backoff);
    }
}
