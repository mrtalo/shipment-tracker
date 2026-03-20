<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendPacketStatusWebhookJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $backoff = 30;

    public function __construct(
        public readonly string $trackingCode,
        public readonly string $oldStatus,
        public readonly string $newStatus,
        public readonly string $updatedAt
    ) {}

    public function uniqueId(): string
    {
        return "{$this->trackingCode}-{$this->newStatus}";
    }

    public function handle(): void
    {
        $webhookUrl = config('services.webhook.url');

        if (! $webhookUrl) {
            Log::warning('WEBHOOK_URL not configured, skipping webhook', [
                'tracking_code' => $this->trackingCode,
            ]);

            return;
        }

        $payload = [
            'tracking_code' => $this->trackingCode,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'updated_at' => $this->updatedAt,
        ];

        $response = Http::post($webhookUrl, $payload);

        if ($response->failed()) {
            throw new \Exception(
                "Webhook failed with status {$response->status()}"
            );
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Webhook delivery failed after all retries', [
            'tracking_code' => $this->trackingCode,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'error' => $exception?->getMessage(),
        ]);
    }
}
