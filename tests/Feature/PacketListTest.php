<?php

namespace Tests\Feature;

use App\Models\Packet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PacketListTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Cache::flush();
    }

    public function test_lists_all_packets(): void
    {
        Packet::factory()->count(3)->create();

        $response = $this->getJson('/api/packets');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_filters_packets_by_status(): void
    {
        Packet::factory()->create(['status' => 'created']);
        Packet::factory()->create(['status' => 'in_transit']);
        Packet::factory()->create(['status' => 'delivered']);

        $response = $this->getJson('/api/packets?status=in_transit');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'in_transit');
    }

    public function test_returns_empty_array_when_no_packets(): void
    {
        $response = $this->getJson('/api/packets');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_list_uses_cache(): void
    {
        Packet::factory()->count(2)->create();

        DB::enableQueryLog();

        $this->getJson('/api/packets');
        $firstCallQueries = count(DB::getQueryLog());

        DB::flushQueryLog();

        $this->getJson('/api/packets');
        $secondCallQueries = count(DB::getQueryLog());

        $this->assertGreaterThan($secondCallQueries, $firstCallQueries);
    }

    public function test_cache_is_invalidated_on_create(): void
    {
        Packet::factory()->create();

        $this->getJson('/api/packets');

        $this->assertTrue(Cache::has('packets:list:all'));

        $this->postJson('/api/packets', [
            'tracking_code' => 'NEW-123',
            'recipient_name' => 'John Doe',
            'recipient_email' => 'john@example.com',
            'destination_address' => '123 Main St',
            'weight_grams' => 1000,
        ]);

        $this->assertFalse(Cache::has('packets:list:all'));
    }

    public function test_cache_is_invalidated_on_status_update(): void
    {
        $packet = Packet::factory()->create(['status' => 'created']);

        $this->getJson('/api/packets');

        $this->assertTrue(Cache::has('packets:list:all'));

        $this->putJson("/api/packets/{$packet->id}/status", [
            'status' => 'in_transit',
        ]);

        $this->assertFalse(Cache::has('packets:list:all'));
    }

    public function test_filtered_list_uses_separate_cache(): void
    {
        Packet::factory()->create(['status' => 'created']);
        Packet::factory()->create(['status' => 'in_transit']);

        $this->getJson('/api/packets?status=created');
        $this->getJson('/api/packets?status=in_transit');

        $this->assertTrue(Cache::has('packets:list:created'));
        $this->assertTrue(Cache::has('packets:list:in_transit'));
    }
}
