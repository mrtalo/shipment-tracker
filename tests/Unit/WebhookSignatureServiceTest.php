<?php

namespace Tests\Unit;

use App\Services\WebhookSignatureService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WebhookSignatureServiceTest extends TestCase
{
    private WebhookSignatureService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WebhookSignatureService;
    }

    public function test_generates_valid_hmac_signature(): void
    {
        $payload = [
            'tracking_code' => 'TEST123',
            'status' => 'delivered',
            'timestamp' => '2026-03-19T12:00:00Z',
        ];

        $signature = $this->service->generate($payload, 'secret-key');

        $this->assertStringStartsWith('sha256=', $signature);
        $this->assertEquals(71, strlen($signature));
    }

    public function test_generates_consistent_signatures_for_same_payload(): void
    {
        $payload = [
            'tracking_code' => 'TEST123',
            'status' => 'delivered',
        ];

        $signature1 = $this->service->generate($payload, 'secret-key');
        $signature2 = $this->service->generate($payload, 'secret-key');

        $this->assertEquals($signature1, $signature2);
    }

    public function test_generates_different_signatures_for_different_payloads(): void
    {
        $payload1 = ['tracking_code' => 'TEST123'];
        $payload2 = ['tracking_code' => 'TEST456'];

        $signature1 = $this->service->generate($payload1, 'secret-key');
        $signature2 = $this->service->generate($payload2, 'secret-key');

        $this->assertNotEquals($signature1, $signature2);
    }

    public function test_generates_different_signatures_for_different_secrets(): void
    {
        $payload = ['tracking_code' => 'TEST123'];

        $signature1 = $this->service->generate($payload, 'secret-1');
        $signature2 = $this->service->generate($payload, 'secret-2');

        $this->assertNotEquals($signature1, $signature2);
    }

    public function test_sorts_payload_keys_before_signing(): void
    {
        $payload1 = [
            'tracking_code' => 'TEST123',
            'status' => 'delivered',
            'timestamp' => '2026-03-19',
        ];

        $payload2 = [
            'timestamp' => '2026-03-19',
            'tracking_code' => 'TEST123',
            'status' => 'delivered',
        ];

        $signature1 = $this->service->generate($payload1, 'secret');
        $signature2 = $this->service->generate($payload2, 'secret');

        $this->assertEquals($signature1, $signature2);
    }

    public function test_ignores_signature_field_in_payload(): void
    {
        $payloadWithoutSig = [
            'tracking_code' => 'TEST123',
            'status' => 'delivered',
        ];

        $payloadWithSig = [
            'tracking_code' => 'TEST123',
            'status' => 'delivered',
            'signature' => 'some-old-signature',
        ];

        $signature1 = $this->service->generate($payloadWithoutSig, 'secret');
        $signature2 = $this->service->generate($payloadWithSig, 'secret');

        $this->assertEquals($signature1, $signature2);
    }

    public function test_validates_correct_signature(): void
    {
        $payload = [
            'tracking_code' => 'TEST123',
            'status' => 'delivered',
        ];

        $signature = $this->service->generate($payload, 'secret-key');

        $isValid = $this->service->validate($payload, $signature, 'secret-key');

        $this->assertTrue($isValid);
    }

    public function test_rejects_invalid_signature(): void
    {
        $payload = [
            'tracking_code' => 'TEST123',
            'status' => 'delivered',
        ];

        $isValid = $this->service->validate($payload, 'sha256=invalid', 'secret-key');

        $this->assertFalse($isValid);
    }

    public function test_rejects_signature_with_wrong_secret(): void
    {
        $payload = [
            'tracking_code' => 'TEST123',
            'status' => 'delivered',
        ];

        $signature = $this->service->generate($payload, 'secret-1');

        $isValid = $this->service->validate($payload, $signature, 'secret-2');

        $this->assertFalse($isValid);
    }

    public function test_rejects_signature_for_modified_payload(): void
    {
        $originalPayload = [
            'tracking_code' => 'TEST123',
            'status' => 'delivered',
        ];

        $signature = $this->service->generate($originalPayload, 'secret-key');

        $modifiedPayload = [
            'tracking_code' => 'TEST456',
            'status' => 'delivered',
        ];

        $isValid = $this->service->validate($modifiedPayload, $signature, 'secret-key');

        $this->assertFalse($isValid);
    }

    #[DataProvider('specialCharacterPayloads')]
    public function test_handles_special_characters_in_payload(array $payload): void
    {
        $signature = $this->service->generate($payload, 'secret');

        $this->assertStringStartsWith('sha256=', $signature);

        $isValid = $this->service->validate($payload, $signature, 'secret');

        $this->assertTrue($isValid);
    }

    public static function specialCharacterPayloads(): array
    {
        return [
            'with slashes' => [
                ['url' => 'https://example.com/path/to/resource'],
            ],
            'with unicode' => [
                ['message' => 'Enviado a José María'],
            ],
            'with quotes' => [
                ['note' => 'Package "A" delivered'],
            ],
        ];
    }
}
