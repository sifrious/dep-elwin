<?php

namespace Sifrious\Elwin\Twinkle;

enum TwinkleStatus: string
{
    case Active = 'active';
    case Deferred = 'deferred';
    case Dismissed = 'dismissed';
    case Promoted = 'promoted';
    case Merged = 'merged';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Dismissed, self::Promoted, self::Merged => true,
            default => false,
        };
    }
}
