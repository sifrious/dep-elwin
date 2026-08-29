<?php

namespace Sifrious\Elwin\Twinkle;

use InvalidArgumentException;

final readonly class TwinkleId
{
    public function __construct(public string $value)
    {
        if (trim($value) === '' || preg_match('/\s/', $value)) {
            throw new InvalidArgumentException('Twinkle ID must be a non-empty token.');
        }
    }
}
