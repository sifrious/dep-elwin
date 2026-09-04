<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Handoff;

use DateTimeImmutable;
use Sifrious\ReferenceContract\CrossPackageReference;

/** Framework-neutral query semantics shared by Burdgeon and Logres adapters. */
final readonly class HandoffQuery
{
    private function __construct(
        private string $mode,
        public DateTimeImmutable $asOf,
        public ?CrossPackageReference $conversation = null,
        public ?CrossPackageReference $pausedWork = null,
    ) {}

    public static function awaitingResponse(
        DateTimeImmutable $asOf,
        ?CrossPackageReference $conversation = null,
    ): self {
        return new self('awaiting_response', $asOf, conversation: $conversation);
    }

    public static function resumable(
        DateTimeImmutable $asOf,
        ?CrossPackageReference $pausedWork = null,
    ): self {
        return new self('resumable', $asOf, pausedWork: $pausedWork);
    }

    public function matches(ResumableHandoff $handoff): bool
    {
        if ($this->conversation !== null && ! $this->conversation->equals($handoff->conversation)) {
            return false;
        }
        if ($this->pausedWork !== null && ! $this->pausedWork->equals($handoff->pausedWork)) {
            return false;
        }

        return match ($this->mode) {
            'awaiting_response' => $handoff->isAwaitingResponseAt($this->asOf),
            'resumable' => $handoff->isResumableAt($this->asOf),
        };
    }
}
