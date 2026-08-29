<?php

namespace Sifrious\Elwin;

use InvalidArgumentException;

final readonly class Reference
{
    public function __construct(
        public string $owner,
        public string $type,
        public string $identifier,
        public ?string $version = null,
    ) {
        foreach (['owner' => $owner, 'type' => $type, 'identifier' => $identifier] as $field => $value) {
            if (trim($value) === '' || preg_match('/\s/', $value)) {
                throw new InvalidArgumentException("Reference {$field} must be a non-empty token.");
            }
        }

        if ($version !== null && trim($version) === '') {
            throw new InvalidArgumentException('Reference version cannot be empty.');
        }
    }

    public function equals(self $other): bool
    {
        return $this == $other;
    }
}
