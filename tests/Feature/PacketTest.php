<?php

namespace Tests\Feature;

use App\Models\Packet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PacketTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_create_packet()
    {
        $data = [
            'tracking_code' => 'TEST-12345',
            'recipient_name' => 'John Doe',
            'recipient_email' => 'john@example.com',
            'destination_address' => '123 Main St, City',
            'weight_grams' => 1500,
        ];

        $response = $this->postJson('/api/packets', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'tracking_code',
                    'recipient_name',
                    'recipient_email',
                    'destination_address',
                    'weight_grams',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'tracking_code' => 'TEST-12345',
                    'recipient_name' => 'John Doe',
                    'recipient_email' => 'john@example.com',
                    'status' => 'created',
                ],
            ]);

        $this->assertDatabaseHas('packets', [
            'tracking_code' => 'TEST-12345',
            'status' => 'created',
        ]);
    }

    public function test_cannot_create_packet_with_duplicate_tracking_code()
    {
        Packet::factory()->create(['tracking_code' => 'DUPLICATE-123']);

        $data = [
            'tracking_code' => 'DUPLICATE-123',
            'recipient_name' => 'Jane Doe',
            'recipient_email' => 'jane@example.com',
            'destination_address' => '456 Oak Ave',
            'weight_grams' => 2000,
        ];

        $response = $this->postJson('/api/packets', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tracking_code']);
    }

    public function test_requires_all_fields()
    {
        $response = $this->postJson('/api/packets', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'tracking_code',
                'recipient_name',
                'recipient_email',
                'destination_address',
                'weight_grams',
            ]);
    }

    public function test_validates_email_format()
    {
        $data = [
            'tracking_code' => 'TEST-EMAIL',
            'recipient_name' => 'John Doe',
            'recipient_email' => 'invalid-email',
            'destination_address' => '123 Main St',
            'weight_grams' => 1000,
        ];

        $response = $this->postJson('/api/packets', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['recipient_email']);
    }

    public function test_weight_must_be_at_least_one()
    {
        $data = [
            'tracking_code' => 'TEST-WEIGHT',
            'recipient_name' => 'John Doe',
            'recipient_email' => 'john@example.com',
            'destination_address' => '123 Main St',
            'weight_grams' => 0,
        ];

        $response = $this->postJson('/api/packets', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['weight_grams']);
    }
}
