<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Tests;

use PHPUnit\Framework\TestCase;
use Sifrious\AuthorizationContract\ActorContext;
use Sifrious\AuthorizationContract\ActorKind;
use Sifrious\AuthorizationContract\AuthorizationContext;
use Sifrious\AuthorizationContract\TenantScope;
use Sifrious\Elwin\Conversation;
use Sifrious\Elwin\ConversationEvent;
use Sifrious\Elwin\ConversationEventType;
use Sifrious\Elwin\ConversationMessageReference;
use Sifrious\Elwin\ConversationStatus;
use Sifrious\Elwin\InferredIntent;
use Sifrious\Elwin\NamedInputChannel;
use Sifrious\Elwin\PrimaryAskUserInput;
use Sifrious\Elwin\StringInputPart;
use Sifrious\Elwin\UserEditedIntent;
use Sifrious\ReferenceContract\CrossPackageReference;

final class ConversationEnvelopeContractTest extends TestCase
{
    public function test_one_conversation_spans_two_sources_and_preserves_exact_input_and_intent_history(): void
    {
        $exact = "  Maybe start with auth?\r\nKeep this exact.  ";
        $input = $this->input('input:1', $exact, 'burdgen');
        $clarification = $this->input('input:2', 'Only the API repository.', 'mcp:codex');
        $firstIntent = new InferredIntent('intent:1:v1', 'intent:1', $input->id, 'Clarify scope.', [], 'Repository unknown.', 1, 'fixture:model');
        $revisedIntent = new UserEditedIntent('intent:1:v2', 'intent:1', $input->id, 'Change API authentication.', ['API repository only.'], null, 2, 'input:2');
        $preserved = $firstIntent->supersededBy($revisedIntent);

        $conversation = new Conversation('conversation:1', [$input->id], [$firstIntent->id]);
        $conversation = $conversation
            ->attachMessage($this->message('message:1', 'burdgen', 'ask:1', $input->id))
            ->recordEvent(new ConversationEvent('event:q1', ConversationEventType::QuestionAsked, $this->ref('question', 'question:1'), '2026-09-02T03:00:01Z'))
            ->attachMessage($this->message('message:2', 'mcp:codex', 'turn:9', $clarification->id))
            ->recordEvent(new ConversationEvent('event:r1', ConversationEventType::ResponseReceived, $this->ref('response', 'response:1'), '2026-09-02T03:00:02Z', $this->ref('question', 'question:1')))
            ->recordIntent($revisedIntent->id);

        self::assertSame($exact, $input->stringInputParts()[0]->exactText);
        self::assertSame(['burdgen', 'mcp:codex'], array_map(static fn ($message): string => $message->sourceAdapter, $conversation->messages));
        self::assertSame($revisedIntent->id, $preserved->replacementIntentId);
        self::assertSame([$input->id, $clarification->id], $conversation->inputIds);
        self::assertSame(ConversationStatus::Active, $conversation->status);
    }

    public function test_conversation_can_pause_or_finish_with_plan_without_creating_a_run(): void
    {
        $calls = new class { public int $execution = 0; };
        $conversation = new Conversation('conversation:2', ['input:3']);
        $paused = $conversation->pause();
        $finished = $paused->resume()->finishWith($this->ref('plan', 'plan:1'));

        self::assertSame(ConversationStatus::Paused, $paused->status);
        self::assertSame(ConversationStatus::Finished, $finished->status);
        self::assertSame('plan:1', $finished->handoffs[0]->id);
        self::assertSame(0, $calls->execution, 'Conversation state must not dispatch execution.');
    }

    private function input(string $id, string $text, string $channel): PrimaryAskUserInput
    {
        $actor = $this->ref('account', 'user:1');
        $authorization = new AuthorizationContext(
            new ActorContext($actor, ActorKind::Human),
            TenantScope::forTenant('organization', $this->ref('organization', 'tenant:a')),
        );

        return new PrimaryAskUserInput(
            $id,
            'submission:'.$id,
            $authorization,
            new NamedInputChannel($channel),
            [new StringInputPart('part:'.$id, 0, $text)],
            '2026-09-02T03:00:00Z',
        );
    }

    private function message(string $id, string $source, string $sourceMessageId, string $inputId): ConversationMessageReference
    {
        return new ConversationMessageReference($id, $source, $sourceMessageId, $inputId, $this->ref('account', 'user:1'), '2026-09-02T03:00:00Z');
    }

    private function ref(string $type, string $id): CrossPackageReference
    {
        return new CrossPackageReference('sifrious/elwin', $type, $id);
    }
}
