<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Sifrious\Elwin\Twinkle\InvalidTwinkleTransition;
use Sifrious\Elwin\Twinkle\ProposedObject;
use Sifrious\Elwin\Twinkle\ProposedObjectStatus;
use Sifrious\Elwin\Twinkle\TwinkleId;
use Sifrious\ReferenceContract\CrossPackageReference;

final class ProposedObjectContractTest extends TestCase
{
    public function test_acceptance_does_not_authorize_execution_and_materialization_links_many_results(): void
    {
        $accepted = $this->captured()->transition(ProposedObjectStatus::Accepted, new DateTimeImmutable('2026-08-30'), 1);
        self::assertFalse($accepted->isExecutionAuthorized());

        $materialized = $accepted
            ->transition(ProposedObjectStatus::Materializing, new DateTimeImmutable('2026-08-31'), 2)
            ->transition(ProposedObjectStatus::Materialized, new DateTimeImmutable('2026-09-01'), 3, [
                $this->ref('titan-plan', 'plan-1'),
                $this->ref('git-file', 'src/NewService.php'),
                $this->ref('git-commit', 'abc123'),
            ]);

        self::assertCount(3, $materialized->results);
        self::assertSame(ProposedObjectStatus::Materialized, $materialized->status);
    }

    public function test_rejection_preserves_identity_and_is_terminal(): void
    {
        $rejected = $this->captured()->transition(ProposedObjectStatus::Rejected, new DateTimeImmutable(), 1);
        self::assertSame('tw_1', $rejected->id->value);

        $this->expectException(InvalidTwinkleTransition::class);
        $rejected->transition(ProposedObjectStatus::Accepted, new DateTimeImmutable(), 2);
    }

    public function test_target_is_not_observed_materialization_evidence(): void
    {
        $proposal = ProposedObject::capture(new TwinkleId('tw_2'), $this->ref('burdgen-project', 'project-1'), 'future-file', 'Add a service', null, [$this->ref('conversation', 'turn-1')], new DateTimeImmutable(), $this->ref('git-file', 'src/Future.php'));

        self::assertSame(ProposedObjectStatus::Captured, $proposal->status);
        self::assertSame('src/Future.php', $proposal->target?->id);
        self::assertSame([], $proposal->results);
    }

    private function captured(): ProposedObject
    {
        return ProposedObject::capture(new TwinkleId('tw_1'), $this->ref('burdgen-project', 'project-1'), 'architecture-concept', 'Add a command bus', null, [$this->ref('conversation', 'turn-1')], new DateTimeImmutable('2026-08-29T12:00:00Z'));
    }

    private function ref(string $type, string $id): CrossPackageReference
    {
        return new CrossPackageReference('test/owner', $type, $id);
    }
}
