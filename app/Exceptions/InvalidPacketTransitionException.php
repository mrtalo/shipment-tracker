<?php

namespace App\Exceptions;

use App\Enums\PacketStatus;
use Exception;

class InvalidPacketTransitionException extends Exception
{
    public static function fromTransition(PacketStatus $from, PacketStatus $to): self
    {
        $validStates = array_map(fn ($s) => $s->value, $from->validNextStates());
        $validList = empty($validStates)
            ? 'none (final state)'
            : implode(', ', $validStates);

        return new self(
            "Cannot transition from '{$from->value}' to '{$to->value}'. ".
            "Valid transitions: {$validList}"
        );
    }
}
