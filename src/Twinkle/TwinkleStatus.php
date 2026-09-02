<?php

namespace Sifrious\Elwin\Twinkle;

enum TwinkleStatus: string
{
    case Active = 'active';
    case Accepted = 'accepted';
    case Deferred = 'deferred';
    case Dismissed = 'dismissed';
    case Promoted = 'promoted';
    case Merged = 'merged';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Dismissed, self::Merged => true,
            default => false,
        };
    }
}
