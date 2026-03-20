<?php

namespace Tests\Feature;

use App\Enums\PacketStatus;
use App\Models\Packet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PacketStatusTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('validTransitionsProvider')]
    public function test_allows_valid_status_transitions(string $from, string $to): void
    {
        $packet = Packet::factory()->create(['status' => $from]);

        $response = $this->putJson("/api/packets/{$packet->id}/status", [
            'status' => $to,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $packet->id,
                    'status' => $to,
                ],
            ]);

        $this->assertDatabaseHas('packets', [
            'id' => $packet->id,
            'status' => $to,
        ]);
    }

    #[DataProvider('invalidTransitionsProvider')]
    public function test_rejects_invalid_status_transitions(string $from, string $to): void
    {
        $packet = Packet::factory()->create(['status' => $from]);

        $response = $this->putJson("/api/packets/{$packet->id}/status", [
            'status' => $to,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => "Cannot transition from '{$from}' to '{$to}'. Valid transitions: ".$this->getValidTransitionsMessage($from),
            ]);

        $this->assertDatabaseHas('packets', [
            'id' => $packet->id,
            'status' => $from,
        ]);
    }

    public function test_returns_404_for_nonexistent_packet(): void
    {
        $response = $this->putJson('/api/packets/99999/status', [
            'status' => 'in_transit',
        ]);

        $response->assertStatus(404);
    }

    public function test_requires_status_field(): void
    {
        $packet = Packet::factory()->create(['status' => 'created']);

        $response = $this->putJson("/api/packets/{$packet->id}/status", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_rejects_invalid_status_value(): void
    {
        $packet = Packet::factory()->create(['status' => 'created']);

        $response = $this->putJson("/api/packets/{$packet->id}/status", [
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * @return array<string, array<string>>
     */
    public static function validTransitionsProvider(): array
    {
        return [
            'created to in_transit' => ['created', 'in_transit'],
            'in_transit to delivered' => ['in_transit', 'delivered'],
            'in_transit to failed' => ['in_transit', 'failed'],
        ];
    }

    /**
     * @return array<string, array<string>>
     */
    public static function invalidTransitionsProvider(): array
    {
        return [
            'created to delivered' => ['created', 'delivered'],
            'created to failed' => ['created', 'failed'],
            'delivered to in_transit' => ['delivered', 'in_transit'],
            'delivered to failed' => ['delivered', 'failed'],
            'failed to in_transit' => ['failed', 'in_transit'],
            'failed to delivered' => ['failed', 'delivered'],
        ];
    }

    private function getValidTransitionsMessage(string $from): string
    {
        $status = PacketStatus::from($from);
        $validStates = array_map(fn ($s) => $s->value, $status->validNextStates());

        return empty($validStates) ? 'none (final state)' : implode(', ', $validStates);
    }
}
