<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Handoff;

use InvalidArgumentException;
use JsonSerializable;
use Sifrious\ReferenceContract\CrossPackageReference;

/** Opaque consumer-owned coordinates for resuming paused work. */
final readonly class ResumeContext implements JsonSerializable
{
    /**
     * @param list<CrossPackageReference> $references
     */
    public function __construct(
        public string $token,
        public CrossPackageReference $checkpoint,
        public array $references = [],
    ) {
        if (trim($token) === '') {
            throw new InvalidArgumentException('A resume token is required.');
        }
        if (! array_is_list($references)) {
            throw new InvalidArgumentException('Resume context references must be a list.');
        }
        foreach ($references as $reference) {
            if (! $reference instanceof CrossPackageReference) {
                throw new InvalidArgumentException('Resume context must use shared cross-package references.');
            }
        }
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'token' => $this->token,
            'checkpoint' => $this->checkpoint->toArray(),
            'references' => array_map(
                static fn (CrossPackageReference $reference): array => $reference->toArray(),
                $this->references,
            ),
        ];
    }
}
