<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Twinkle;

use DateTimeImmutable;
use InvalidArgumentException;
use Sifrious\ReferenceContract\CrossPackageReference;

/** Provider-neutral proposal contract. Proposed targets never assert observed existence. */
final readonly class ProposedObject
{
    public const string DEFINITION_VERSION = 'twinkle.proposed-object/v1';

    /**
     * @param list<CrossPackageReference> $origins
     * @param list<CrossPackageReference> $results
     * @param list<CrossPackageReference> $relations
     */
    public function __construct(
        public TwinkleId $id,
        public CrossPackageReference $project,
        public string $kind,
        public string $title,
        public ?string $description,
        public ProposedObjectStatus $status,
        public int $version,
        public array $origins,
        public ?CrossPackageReference $target,
        public array $results,
        public array $relations,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?TwinkleId $supersededBy = null,
    ) {
        if (trim($kind) === '' || trim($title) === '' || $version < 1 || $origins === []) {
            throw new InvalidArgumentException('A proposed object requires kind, title, positive version, and origin references.');
        }
        if ($status === ProposedObjectStatus::Materialized && $results === []) {
            throw new InvalidArgumentException('A materialized proposal requires observed result references.');
        }
        if ($status === ProposedObjectStatus::Superseded && $supersededBy === null) {
            throw new InvalidArgumentException('A superseded proposal identifies its replacement.');
        }
    }

    /** @param list<CrossPackageReference> $origins */
    public static function capture(TwinkleId $id, CrossPackageReference $project, string $kind, string $title, ?string $description, array $origins, DateTimeImmutable $at, ?CrossPackageReference $target = null): self
    {
        return new self($id, $project, $kind, $title, $description, ProposedObjectStatus::Captured, 1, $origins, $target, [], [], $at, $at);
    }

    /** @param list<CrossPackageReference> $results */
    public function transition(ProposedObjectStatus $next, DateTimeImmutable $at, int $expectedVersion, array $results = [], ?TwinkleId $supersededBy = null): self
    {
        if ($expectedVersion !== $this->version) {
            throw new StaleTwinkleVersion("Expected proposal version {$expectedVersion}; current version is {$this->version}.");
        }
        $allowed = match ($this->status) {
            ProposedObjectStatus::Captured => [ProposedObjectStatus::Discussing, ProposedObjectStatus::Accepted, ProposedObjectStatus::Rejected, ProposedObjectStatus::Deferred, ProposedObjectStatus::Superseded],
            ProposedObjectStatus::Discussing => [ProposedObjectStatus::Accepted, ProposedObjectStatus::Rejected, ProposedObjectStatus::Deferred, ProposedObjectStatus::Superseded],
            ProposedObjectStatus::Deferred => [ProposedObjectStatus::Discussing, ProposedObjectStatus::Accepted, ProposedObjectStatus::Rejected, ProposedObjectStatus::Superseded],
            ProposedObjectStatus::Accepted => [ProposedObjectStatus::Discussing, ProposedObjectStatus::Deferred, ProposedObjectStatus::Materializing, ProposedObjectStatus::Superseded],
            ProposedObjectStatus::Materializing => [ProposedObjectStatus::Accepted, ProposedObjectStatus::Materialized, ProposedObjectStatus::Superseded],
            ProposedObjectStatus::Rejected, ProposedObjectStatus::Materialized, ProposedObjectStatus::Superseded => [],
        };
        if (! in_array($next, $allowed, true)) {
            throw new InvalidTwinkleTransition("Cannot transition proposal from {$this->status->value} to {$next->value}.");
        }

        return new self($this->id, $this->project, $this->kind, $this->title, $this->description, $next, $this->version + 1, $this->origins, $this->target, $results, $this->relations, $this->createdAt, $at, $supersededBy);
    }

    public function isExecutionAuthorized(): bool
    {
        return false;
    }
}
