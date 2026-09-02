<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Disposition;

use InvalidArgumentException;

final readonly class UnresolvedRequirement
{
    public function __construct(
        public string $id,
        public string $kind,
        public string $prompt,
        public bool $required,
    ) {
        if (trim($id) === '' || trim($kind) === '' || trim($prompt) === '') {
            throw new InvalidArgumentException('An unresolved requirement needs identity, kind, and prompt.');
        }
    }
}
