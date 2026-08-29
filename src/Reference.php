<?php

namespace Sifrious\Elwin;

use JsonSerializable;
use Sifrious\ReferenceContract\CrossPackageReference;

/** @deprecated Prefer CrossPackageReference directly. This adapter preserves Elwin's constructor API and the shared v1 wire format. */
final readonly class Reference implements JsonSerializable
{
    public function __construct(
        public string $owner,
        public string $type,
        public string $identifier,
        public ?string $version = null,
        public ?CrossPackageReference $provenance = null,
    ) {
        new CrossPackageReference($owner, $type, $identifier, $version, $provenance);
    }

    public function equals(self $other): bool
    {
        return $this->toPortable()->equals($other->toPortable());
    }

    public function toPortable(): CrossPackageReference
    {
        return new CrossPackageReference($this->owner, $this->type, $this->identifier, $this->version, $this->provenance);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->toPortable()->toArray();
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
