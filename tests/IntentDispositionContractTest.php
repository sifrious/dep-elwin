<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Sifrious\Elwin\Disposition\ExecutionReadiness;
use Sifrious\Elwin\Disposition\IntentDisposition;
use Sifrious\Elwin\Disposition\NextTransition;
use Sifrious\Elwin\Disposition\UnresolvedRequirement;
use Sifrious\ReferenceContract\CrossPackageReference;

final class IntentDispositionContractTest extends TestCase
{
    public function test_vague_intent_can_plan_or_clarify_without_execution_authority(): void
    {
        $disposition = new IntentDisposition('disp-1', $this->ref('intent', 'intent-1'), $this->ref('conversation', 'conversation-1'), new DateTimeImmutable(), IntentDisposition::DEFINITION_VERSION, [], [new UnresolvedRequirement('scope', 'missing_constraint', 'Which repository is in scope?', true)], [NextTransition::Clarify, NextTransition::Plan], [NextTransition::Clarify], ExecutionReadiness::ReadyForPlanning, [], true);

        self::assertTrue($disposition->allows(NextTransition::Plan));
        self::assertFalse($disposition->authorizesExecution());
    }

    public function test_direct_execution_candidate_still_requires_explicit_selection(): void
    {
        $disposition = new IntentDisposition('disp-2', $this->ref('intent', 'intent-2'), $this->ref('conversation', 'conversation-2'), new DateTimeImmutable(), IntentDisposition::DEFINITION_VERSION, [$this->ref('requested-outcome', 'docs-change')], [], [NextTransition::DirectExecutionCandidate, NextTransition::Plan], [NextTransition::DirectExecutionCandidate], ExecutionReadiness::DirectExecutionCandidate, [$this->ref('evidence', 'bounded-single-repository')], true);

        self::assertTrue($disposition->requiresExplicitUserSelection);
        self::assertFalse($disposition->authorizesExecution());
    }

    public function test_unauthorized_path_is_explicitly_blocked(): void
    {
        $disposition = new IntentDisposition('disp-3', $this->ref('intent', 'intent-3'), $this->ref('conversation', 'conversation-3'), new DateTimeImmutable(), IntentDisposition::DEFINITION_VERSION, [], [], [NextTransition::ContinueConversation], [], ExecutionReadiness::Blocked, [$this->ref('policy', 'execution-context-authorization')], true, 'execution_context_authorization_missing');

        self::assertSame('execution_context_authorization_missing', $disposition->blockedReason);
        self::assertFalse($disposition->allows(NextTransition::DirectExecutionCandidate));
    }

    private function ref(string $type, string $id): CrossPackageReference
    {
        return new CrossPackageReference('test/owner', $type, $id);
    }
}
