<?php

namespace App\Services;

class WebhookSignatureService
{
    public function generate(array $payload, string $secret): string
    {
        $payloadWithoutSignature = $payload;
        unset($payloadWithoutSignature['signature']);

        ksort($payloadWithoutSignature);

        $json = json_encode($payloadWithoutSignature, JSON_UNESCAPED_SLASHES);

        return 'sha256='.hash_hmac('sha256', $json, $secret);
    }

    public function validate(array $payload, string $receivedSignature, string $secret): bool
    {
        $expectedSignature = $this->generate($payload, $secret);

        return hash_equals($expectedSignature, $receivedSignature);
    }
}
