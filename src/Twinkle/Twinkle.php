<?php
declare(strict_types=1);

namespace Sifrious\Elwin\Twinkle;

use DateTimeImmutable;
use InvalidArgumentException;
use Sifrious\Elwin\Reference;

final readonly class Twinkle
{
    /** @param list<Reference> $context @param list<ConceptAssociation> $concepts @param list<Reference> $promotedWork */
    public function __construct(
        public TwinkleId $id,
        public string $title,
        public ?string $description,
        public TwinkleStatus $status,
        public int $version,
        public Reference $creator,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public Reference $provenance,
        public array $context = [],
        public array $concepts = [],
        public array $promotedWork = [],
        public ?TwinkleId $mergedInto = null,
    ) {
        if (trim($title) === '' || $version < 1) {
            throw new InvalidArgumentException('Twinkle requires a title and positive version.');
        }
    }

    /** @param list<Reference> $context */
    public static function capture(TwinkleId $id, string $title, ?string $description, Reference $creator, Reference $provenance, DateTimeImmutable $at, array $context = []): self
    {
        return new self($id, $title, $description, TwinkleStatus::Active, 1, $creator, $at, $at, $provenance, $context);
    }

    public function revise(string $title, ?string $description, Reference $actor, DateTimeImmutable $at, int $expectedVersion): TwinkleChange
    {
        $this->assertMutable($expectedVersion);
        return $this->change('revised', $actor, $at, title: $title, description: $description, replaceMeaning: true);
    }

    public function defer(Reference $actor, DateTimeImmutable $at, int $expectedVersion, ?string $rationale = null): TwinkleChange
    {
        $this->assertStatus([TwinkleStatus::Active], $expectedVersion, 'defer');
        return $this->change('deferred', $actor, $at, $rationale, status: TwinkleStatus::Deferred);
    }

    public function reactivate(Reference $actor, DateTimeImmutable $at, int $expectedVersion, ?string $rationale = null): TwinkleChange
    {
        $this->assertStatus([TwinkleStatus::Deferred], $expectedVersion, 'reactivate');
        return $this->change('reactivated', $actor, $at, $rationale, status: TwinkleStatus::Active);
    }

    public function dismiss(Reference $actor, DateTimeImmutable $at, int $expectedVersion, ?string $rationale = null): TwinkleChange
    {
        $this->assertStatus([TwinkleStatus::Active, TwinkleStatus::Deferred], $expectedVersion, 'dismiss');
        return $this->change('dismissed', $actor, $at, $rationale, status: TwinkleStatus::Dismissed);
    }

    /** @param list<Reference> $work */
    public function promote(array $work, Reference $actor, DateTimeImmutable $at, int $expectedVersion, ?string $rationale = null): TwinkleChange
    {
        $this->assertStatus([TwinkleStatus::Active], $expectedVersion, 'promote');
        if ($work === []) {
            throw new InvalidTwinkleTransition('Promotion requires at least one durable work reference.');
        }
        return $this->change('promoted', $actor, $at, $rationale, status: TwinkleStatus::Promoted, promotedWork: $work);
    }

    public function mergeInto(TwinkleId $survivor, Reference $actor, DateTimeImmutable $at, int $expectedVersion, ?string $rationale = null): TwinkleChange
    {
        $this->assertStatus([TwinkleStatus::Active, TwinkleStatus::Deferred], $expectedVersion, 'merge');
        if ($survivor == $this->id) {
            throw new InvalidTwinkleTransition('A Twinkle cannot merge into itself.');
        }
        return $this->change('merged', $actor, $at, $rationale, status: TwinkleStatus::Merged, mergedInto: $survivor);
    }

    public function associateConcept(ConceptAssociation $association, Reference $actor, DateTimeImmutable $at, int $expectedVersion): TwinkleChange
    {
        $this->assertMutable($expectedVersion);
        foreach ($this->concepts as $existing) {
            if ($existing->equals($association)) {
                throw new InvalidTwinkleTransition('The concept association already exists.');
            }
        }
        return $this->change('concept-associated', $actor, $at, concepts: [...$this->concepts, $association]);
    }

    public function removeConcept(ConceptAssociation $association, Reference $actor, DateTimeImmutable $at, int $expectedVersion): TwinkleChange
    {
        $this->assertMutable($expectedVersion);
        $remaining = array_values(array_filter($this->concepts, fn (ConceptAssociation $item) => ! $item->equals($association)));
        if (count($remaining) === count($this->concepts)) {
            throw new InvalidTwinkleTransition('The concept association does not exist.');
        }
        return $this->change('concept-removed', $actor, $at, concepts: $remaining);
    }

    private function assertMutable(int $expectedVersion): void
    {
        $this->assertVersion($expectedVersion);
        if ($this->status->isTerminal()) {
            throw new InvalidTwinkleTransition("A {$this->status->value} Twinkle cannot be changed.");
        }
    }

    /** @param list<TwinkleStatus> $allowed */
    private function assertStatus(array $allowed, int $expectedVersion, string $action): void
    {
        $this->assertVersion($expectedVersion);
        if (! in_array($this->status, $allowed, true)) {
            throw new InvalidTwinkleTransition("Cannot {$action} a {$this->status->value} Twinkle.");
        }
    }

    private function assertVersion(int $expectedVersion): void
    {
        if ($expectedVersion !== $this->version) {
            throw new StaleTwinkleVersion("Expected Twinkle version {$expectedVersion}; current version is {$this->version}.");
        }
    }

    /** @param list<ConceptAssociation>|null $concepts @param list<Reference>|null $promotedWork */
    private function change(string $type, Reference $actor, DateTimeImmutable $at, ?string $rationale = null, ?TwinkleStatus $status = null, ?string $title = null, ?string $description = null, bool $replaceMeaning = false, ?array $concepts = null, ?array $promotedWork = null, ?TwinkleId $mergedInto = null): TwinkleChange
    {
        $next = new self($this->id, $replaceMeaning ? (string) $title : $this->title, $replaceMeaning ? $description : $this->description, $status ?? $this->status, $this->version + 1, $this->creator, $this->createdAt, $at, $this->provenance, $this->context, $concepts ?? $this->concepts, $promotedWork ?? $this->promotedWork, $mergedInto ?? $this->mergedInto);
        return new TwinkleChange($next, new TwinkleTransition($type, $this->version, $next->version, $actor, $at, $rationale));
    }
}
