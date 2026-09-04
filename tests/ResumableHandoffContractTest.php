<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Tests;

use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Sifrious\Elwin\Handoff\HandoffPayload;
use Sifrious\Elwin\Handoff\HandoffQuery;
use Sifrious\Elwin\Handoff\HandoffStatus;
use Sifrious\Elwin\Handoff\HandoffTransition;
use Sifrious\Elwin\Handoff\ResumableHandoff;
use Sifrious\Elwin\Handoff\ResumeContext;
use Sifrious\HarnessContractFixtures\Fixture;
use Sifrious\ReferenceContract\CrossPackageReference;

final class ResumableHandoffContractTest extends TestCase
{
    public function test_shared_pause_answer_resume_fixture_exposes_checkpoint_without_modeling_execution_state(): void
    {
        $fixture = Fixture::load('request-lifecycle-v1');
        $stageTwo = $fixture['stage_2'];
        $pausedWork = $this->ref('sifrious/logres', 'execution-request', $fixture['execution_request']['id']);
        $requestedAt = new DateTimeImmutable('2026-09-04T12:00:00+00:00');
        $handoff = new ResumableHandoff(
            'handoff_01',
            $this->ref('sifrious/elwin', 'conversation', $stageTwo['conversation']['id']),
            $pausedWork,
            $this->ref('sifrious/elwin', 'question', $stageTwo['conversation']['clarification']['question_id']),
            new ResumeContext(
                'fixture-token-01',
                $this->ref('sifrious/logres', 'execution-checkpoint', 'checkpoint_after_completed_handlers'),
                [$this->ref('sifrious/elwin', 'intent', $stageTwo['conversation']['selected_intent_id'])],
            ),
            new HandoffPayload('sifrious.elwin.intervention-context/v1', [
                'source_adapters' => $stageTwo['conversation']['source_adapters'],
                'common_origin' => $stageTwo['common_origin'],
            ]),
            $requestedAt,
            new DateTimeImmutable('2026-09-05T12:00:00+00:00'),
        );

        self::assertTrue(HandoffQuery::awaitingResponse($requestedAt, $handoff->conversation)->matches($handoff));
        self::assertSame(
            [HandoffTransition::SubmitResponse, HandoffTransition::Cancel],
            $handoff->allowedTransitions($requestedAt),
        );

        $executionConsumer = new class {
            public int $completedHandlerCalls = 3;
            public int $resumeRequests = 0;
            public ?string $checkpoint = null;

            public function accept(ResumableHandoff $handoff, DateTimeImmutable $at): void
            {
                if (! $handoff->isResumableAt($at)) {
                    return;
                }

                ++$this->resumeRequests;
                $this->checkpoint = $handoff->resumeContext->checkpoint->id;
            }
        };
        $executionConsumer->accept($handoff, $requestedAt);
        self::assertSame(0, $executionConsumer->resumeRequests);

        $answered = $handoff->answer(
            $this->ref('sifrious/elwin', 'response', $stageTwo['conversation']['clarification']['response_id']),
            $requestedAt->modify('+5 minutes'),
        );
        $executionConsumer->accept($answered, $requestedAt->modify('+6 minutes'));

        self::assertSame(HandoffStatus::Answered, $answered->status);
        self::assertFalse(HandoffQuery::resumable($requestedAt->modify('+4 minutes'), $pausedWork)->matches($answered));
        self::assertTrue(HandoffQuery::resumable($requestedAt->modify('+6 minutes'), $pausedWork)->matches($answered));
        self::assertSame([HandoffTransition::ResumePausedWork, HandoffTransition::Cancel], $answered->allowedTransitions($requestedAt->modify('+6 minutes')));
        self::assertSame(1, $executionConsumer->resumeRequests);
        self::assertSame('checkpoint_after_completed_handlers', $executionConsumer->checkpoint);
        self::assertSame(3, $executionConsumer->completedHandlerCalls, 'Elwin exposes a checkpoint; it does not replay or coordinate handlers.');
    }

    public function test_cancellation_and_expiry_make_a_handoff_non_resumable(): void
    {
        $requestedAt = new DateTimeImmutable('2026-09-04T12:00:00+00:00');
        $handoff = $this->handoff($requestedAt);
        $cancelled = $handoff->cancel($requestedAt->modify('+1 minute'));
        $expired = $this->handoff($requestedAt)->expire($requestedAt->modify('+1 hour'));

        self::assertSame(HandoffStatus::Cancelled, $cancelled->status);
        self::assertSame([], $cancelled->allowedTransitions($requestedAt->modify('+2 minutes')));
        self::assertSame(HandoffStatus::Expired, $expired->status);
        self::assertFalse($expired->isAwaitingResponseAt($requestedAt->modify('+1 hour')));
    }

    public function test_a_response_at_or_after_expiry_is_rejected(): void
    {
        $requestedAt = new DateTimeImmutable('2026-09-04T12:00:00+00:00');
        $handoff = $this->handoff($requestedAt);

        $this->expectException(LogicException::class);
        $handoff->answer(
            $this->ref('sifrious/elwin', 'response', 'response_late'),
            $requestedAt->modify('+1 hour'),
        );
    }

    public function test_queries_and_transitions_do_not_apply_before_the_handoff_exists(): void
    {
        $requestedAt = new DateTimeImmutable('2026-09-04T12:00:00+00:00');
        $handoff = $this->handoff($requestedAt);
        $beforeRequest = $requestedAt->modify('-1 second');

        self::assertFalse(HandoffQuery::awaitingResponse($beforeRequest)->matches($handoff));
        self::assertSame([], $handoff->allowedTransitions($beforeRequest));
    }

    public function test_payload_rejects_mutable_objects(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new HandoffPayload('sifrious.elwin.intervention-context/v1', [
            'mutable' => new \stdClass(),
        ]);
    }

    private function handoff(DateTimeImmutable $requestedAt): ResumableHandoff
    {
        return new ResumableHandoff(
            'handoff_lifecycle',
            $this->ref('sifrious/elwin', 'conversation', 'conversation_01'),
            $this->ref('sifrious/logres', 'execution-request', 'request_01'),
            $this->ref('sifrious/elwin', 'question', 'question_01'),
            new ResumeContext(
                'fixture-token-lifecycle',
                $this->ref('sifrious/logres', 'execution-checkpoint', 'checkpoint_01'),
            ),
            new HandoffPayload('sifrious.elwin.intervention-context/v1', ['summary' => 'Fixture']),
            $requestedAt,
            $requestedAt->modify('+1 hour'),
        );
    }

    private function ref(string $owner, string $type, string $id): CrossPackageReference
    {
        return new CrossPackageReference($owner, $type, $id);
    }
}
