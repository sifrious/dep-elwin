<?php
declare(strict_types=1);

namespace Sifrious\Elwin\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Quain\Core\Concept\ConceptReference;
use Quain\Core\Concept\VocabularySchemeReference;
use Sifrious\Elwin\Reference;
use Sifrious\Elwin\Twinkle\ConceptAssociation;
use Sifrious\Elwin\Twinkle\ConceptAssociationRole;
use Sifrious\Elwin\Twinkle\InvalidTwinkleTransition;
use Sifrious\Elwin\Twinkle\StaleTwinkleVersion;
use Sifrious\Elwin\Twinkle\Twinkle;
use Sifrious\Elwin\Twinkle\TwinkleId;
use Sifrious\Elwin\Twinkle\TwinkleStatus;

final class TwinkleContractTest extends TestCase
{
    public function test_repository_free_capture_defer_and_reactivate(): void
    {
        $captured = $this->captured();
        $deferred = $captured->defer($this->ref('user', 'mary'), new DateTimeImmutable('2026-08-30'), 1, 'Not this week');
        $active = $deferred->twinkle->reactivate($this->ref('user', 'mary'), new DateTimeImmutable('2026-09-01'), 2);
        self::assertSame([], $captured->context);
        self::assertSame(TwinkleStatus::Active, $active->twinkle->status);
        self::assertSame('twinkle-1', $active->twinkle->id->value);
        self::assertSame(3, $active->twinkle->version);
    }

    public function test_stale_transition_is_explicit(): void
    {
        $this->expectException(StaleTwinkleVersion::class);
        $this->captured()->dismiss($this->ref('user', 'mary'), new DateTimeImmutable(), 9);
    }

    public function test_acceptance_and_multiple_promotions_do_not_terminalize_the_proposal(): void
    {
        $accepted = $this->captured()->accept($this->ref('user', 'mary'), new DateTimeImmutable(), 1);
        $first = $accepted->twinkle->promote([$this->ref('titan-plan', 'plan-7')], $this->ref('user', 'mary'), new DateTimeImmutable(), 2);
        $second = $first->twinkle->promote([$this->ref('linear-issue', 'MME-7')], $this->ref('user', 'mary'), new DateTimeImmutable(), 3);
        $deferred = $second->twinkle->defer($this->ref('user', 'mary'), new DateTimeImmutable(), 4);
        $reactivated = $deferred->twinkle->reactivate($this->ref('user', 'mary'), new DateTimeImmutable(), 5);

        self::assertSame(TwinkleStatus::Accepted, $second->twinkle->status);
        self::assertSame(['plan-7', 'MME-7'], array_map(static fn (Reference $reference): string => $reference->identifier, $second->twinkle->promotedWork));
        self::assertSame('promotion-recorded', $second->transition->type);
        self::assertSame(TwinkleStatus::Active, $reactivated->twinkle->status);
    }

    public function test_merge_preserves_source_and_rejects_self_merge(): void
    {
        $change = $this->captured()->mergeInto(new TwinkleId('twinkle-2'), $this->ref('user', 'mary'), new DateTimeImmutable(), 1);
        self::assertSame('twinkle-1', $change->twinkle->id->value);
        self::assertSame('twinkle-2', $change->twinkle->mergedInto?->value);
        $this->expectException(InvalidTwinkleTransition::class);
        $this->captured()->mergeInto(new TwinkleId('twinkle-1'), $this->ref('user', 'mary'), new DateTimeImmutable(), 1);
    }

    public function test_cross_domain_concepts_remain_quain_owned_references(): void
    {
        $associations = [
            $this->association('sifrious/quain', 'programming-language', 'algebraic-effects', ConceptAssociationRole::About),
            $this->association('sifrious/quain', 'software-architecture', 'command-bus', ConceptAssociationRole::Applies),
            $this->association('user:mary', 'talk-ideas', 'laracon-2027', ConceptAssociationRole::InspiredBy),
        ];
        $twinkle = $this->captured();
        foreach ($associations as $association) {
            $twinkle = $twinkle->associateConcept($association, $this->ref('user', 'mary'), new DateTimeImmutable(), $twinkle->version)->twinkle;
        }
        self::assertCount(3, $twinkle->concepts);
        self::assertSame('user:mary', $twinkle->concepts[2]->concept->scheme->owner);
    }

    private function captured(): Twinkle
    {
        return Twinkle::capture(new TwinkleId('twinkle-1'), 'Explore algebraic effects', 'Could effects make command handling explicit?', $this->ref('user', 'mary'), $this->ref('conversation', 'chat-1'), new DateTimeImmutable('2026-08-29T12:00:00Z'));
    }

    private function ref(string $type, string $identifier): Reference
    {
        return new Reference('test/owner', $type, $identifier);
    }

    private function association(string $owner, string $scheme, string $identifier, ConceptAssociationRole $role): ConceptAssociation
    {
        return new ConceptAssociation(new ConceptReference(new VocabularySchemeReference($owner, $scheme, '1'), $identifier), $role);
    }
}
