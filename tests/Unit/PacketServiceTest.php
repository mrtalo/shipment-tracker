<?php

namespace Tests\Unit;

use App\Enums\PacketStatus;
use App\Exceptions\InvalidPacketTransitionException;
use App\Models\Packet;
use App\Services\PacketService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PacketServiceTest extends TestCase
{
    use DatabaseTransactions;

    private PacketService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PacketService;
        Queue::fake();
    }

    public function test_updates_packet_status_successfully(): void
    {
        $packet = Packet::factory()->create(['status' => PacketStatus::CREATED]);

        $result = $this->service->updateStatus($packet, 'in_transit');

        $this->assertEquals(PacketStatus::IN_TRANSIT, $result->status);
        $this->assertDatabaseHas('packets', [
            'id' => $packet->id,
            'status' => 'in_transit',
        ]);
    }

    public function test_throws_exception_on_invalid_transition(): void
    {
        $packet = Packet::factory()->create(['status' => PacketStatus::CREATED]);

        $this->expectException(InvalidPacketTransitionException::class);
        $this->expectExceptionMessage("Cannot transition from 'created' to 'delivered'");

        $this->service->updateStatus($packet, 'delivered');
    }

    public function test_does_not_update_packet_when_transition_fails(): void
    {
        $packet = Packet::factory()->create(['status' => PacketStatus::DELIVERED]);

        try {
            $this->service->updateStatus($packet, 'in_transit');
        } catch (InvalidPacketTransitionException $e) {
            // Expected exception
        }

        $this->assertDatabaseHas('packets', [
            'id' => $packet->id,
            'status' => 'delivered',
        ]);
    }

    public function test_returns_fresh_packet_instance(): void
    {
        $packet = Packet::factory()->create(['status' => PacketStatus::CREATED]);
        $originalUpdatedAt = $packet->updated_at;

        sleep(1);

        $result = $this->service->updateStatus($packet, 'in_transit');

        $this->assertNotEquals($originalUpdatedAt, $result->updated_at);
        $this->assertTrue($result->updated_at->isAfter($originalUpdatedAt));
    }
}
