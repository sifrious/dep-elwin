<?php

namespace Sifrious\Elwin\Twinkle;

use DateTimeImmutable;
use Sifrious\Elwin\Reference;

final readonly class TwinkleTransition
{
    /** @param array<string, mixed> $details */
    public function __construct(
        public string $type,
        public int $fromVersion,
        public int $toVersion,
        public Reference $actor,
        public DateTimeImmutable $occurredAt,
        public ?string $rationale = null,
        public array $details = [],
    ) {}
}
