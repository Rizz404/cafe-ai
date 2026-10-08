<?php

namespace App\Modules\Assistant\Gateway;

/**
 * The moment by which a whole guest turn, every tool round trip included, has
 * to be answered.
 */
final readonly class TurnDeadline
{
    public function __construct(public float $at) {}

    public static function afterSeconds(int $seconds): self
    {
        return new self(microtime(true) + $seconds);
    }

    public function remainingSeconds(): float
    {
        return $this->at - microtime(true);
    }
}
