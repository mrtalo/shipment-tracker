<?php

namespace Tests\Feature;

use App\Models\Packet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
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

    public function test_paginates_results(): void
    {
        Packet::factory()->count(20)->create();

        $response = $this->getJson('/api/packets');

        $response->assertStatus(200)
            ->assertJsonCount(15, 'data')
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_can_navigate_pages(): void
    {
        Packet::factory()->count(20)->create();

        $response = $this->getJson('/api/packets?page=2');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_pagination_respects_status_filter(): void
    {
        Packet::factory()->count(20)->create(['status' => 'created']);
        Packet::factory()->count(5)->create(['status' => 'in_transit']);

        $response = $this->getJson('/api/packets?status=in_transit');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 5);
    }
}
