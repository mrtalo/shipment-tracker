<?php

namespace Tests\Feature;

use App\Models\Packet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PacketShowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Cache::flush();
    }

    public function test_shows_existing_packet(): void
    {
        $packet = Packet::factory()->create([
            'tracking_code' => 'TEST-123',
            'recipient_name' => 'John Doe',
        ]);

        $response = $this->getJson("/api/packets/{$packet->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $packet->id)
            ->assertJsonPath('data.tracking_code', 'TEST-123')
            ->assertJsonPath('data.recipient_name', 'John Doe');
    }

    public function test_returns_404_for_non_existent_packet(): void
    {
        $response = $this->getJson('/api/packets/99999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Packet not found',
            ]);
    }

    public function test_show_uses_cache(): void
    {
        $packet = Packet::factory()->create();

        DB::enableQueryLog();

        $this->getJson("/api/packets/{$packet->id}");
        $firstCallQueries = count(DB::getQueryLog());

        DB::flushQueryLog();

        $this->getJson("/api/packets/{$packet->id}");
        $secondCallQueries = count(DB::getQueryLog());

        $this->assertGreaterThan($secondCallQueries, $firstCallQueries);
    }

    public function test_cache_is_invalidated_on_status_update(): void
    {
        $packet = Packet::factory()->create(['status' => 'created']);

        $this->getJson("/api/packets/{$packet->id}");

        $this->assertTrue(Cache::has("packets:{$packet->id}"));

        $this->putJson("/api/packets/{$packet->id}/status", [
            'status' => 'in_transit',
        ]);

        $this->assertFalse(Cache::has("packets:{$packet->id}"));
    }

    public function test_shows_packet_with_all_fields(): void
    {
        $packet = Packet::factory()->create([
            'tracking_code' => 'ABC-456',
            'recipient_name' => 'Jane Smith',
            'recipient_email' => 'jane@example.com',
            'destination_address' => '456 Oak Ave',
            'weight_grams' => 2500,
            'status' => 'in_transit',
        ]);

        $response = $this->getJson("/api/packets/{$packet->id}");

        $response->assertStatus(200)
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
            ->assertJsonPath('data.tracking_code', 'ABC-456')
            ->assertJsonPath('data.status', 'in_transit')
            ->assertJsonPath('data.weight_grams', 2500);
    }

    public function test_different_packets_have_separate_cache(): void
    {
        $packet1 = Packet::factory()->create();
        $packet2 = Packet::factory()->create();

        $this->getJson("/api/packets/{$packet1->id}");
        $this->getJson("/api/packets/{$packet2->id}");

        $this->assertTrue(Cache::has("packets:{$packet1->id}"));
        $this->assertTrue(Cache::has("packets:{$packet2->id}"));
    }
}
