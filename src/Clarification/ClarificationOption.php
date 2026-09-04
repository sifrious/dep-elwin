<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Clarification;

use InvalidArgumentException;

final readonly class ClarificationOption
{
    public function __construct(
        public string $value,
        public string $label,
        public ?string $description = null,
    ) {
        if (trim($value) === '' || trim($label) === '') {
            throw new InvalidArgumentException('A clarification option requires a value and label.');
        }
    }
}
