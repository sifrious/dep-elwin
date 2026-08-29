<?php

namespace Sifrious\Elwin\Twinkle;

use Quain\Core\Concept\ConceptReference;
use Quain\Core\Concept\ConceptSnapshot;

final readonly class ConceptAssociation
{
    public function __construct(
        public ConceptReference $concept,
        public ConceptAssociationRole $role,
        public ?ConceptSnapshot $snapshot = null,
    ) {}

    public function equals(self $other): bool
    {
        return $this->concept == $other->concept && $this->role === $other->role;
    }
}
