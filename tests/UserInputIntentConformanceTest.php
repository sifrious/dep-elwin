<?php
declare(strict_types=1);
namespace Sifrious\Elwin\Tests;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Sifrious\Elwin\AttachmentInputPart;
use Sifrious\Elwin\InferredIntent;
use Sifrious\Elwin\HumanActorReference;
use Sifrious\Elwin\InputChannel;
use Sifrious\Elwin\InMemoryUserInputStore;
use Sifrious\Elwin\IntentOrigin;
use Sifrious\Elwin\IntentStatus;
use Sifrious\Elwin\NamedInputChannel;
use Sifrious\Elwin\PrimaryAskUserInput;
use Sifrious\Elwin\SendPrimaryAskInput;
use Sifrious\Elwin\StringInputPart;
use Sifrious\Elwin\UserEditedIntent;
use Sifrious\Elwin\UserInputDraft;
use Sifrious\Elwin\UserInputPart;

final class UserInputIntentConformanceTest extends TestCase
{
    public function test_draft_can_change_or_be_discarded_without_becoming_accepted_input(): void
    {
        $store = new InMemoryUserInputStore();
        $draft = new UserInputDraft('submission:draft', new HumanActorReference('user:1'), 'user:1', new NamedInputChannel('burdgen'), 'First wording.');
        $draft->replaceText('Revised before Send.');

        self::assertNull($store->findBySubmission($draft->channel, $draft->submittingActorReference, $draft->clientSubmissionId));
        self::assertSame('Revised before Send.', $draft->exactText);

        $draft->discard();
        self::assertTrue($draft->isDiscarded());
        self::assertNull($store->findBySubmission(new NamedInputChannel('burdgen'), 'user:1', 'submission:draft'));

        $this->expectException(LogicException::class);
        (new SendPrimaryAskInput($store))->send($draft, 'input:draft', '2026-09-02T12:00:00Z');
    }

    public function test_send_accepts_one_ordered_immutable_input_and_replay_is_idempotent(): void
    {
        $store = new InMemoryUserInputStore();
        $send = new SendPrimaryAskInput($store);
        $draft = new UserInputDraft(
            'submission:1',
            new HumanActorReference('human:author'),
            'service:mcp',
            new NamedInputChannel('mcp:codex'),
            "  Keep my spacing.\r\nDo not normalize café or 👩🏽‍💻.  \n",
            [new AttachmentInputPart('part:file', 0, 'artifact:file-1', hash('sha256', 'file bytes'))],
            'delegation:authorized-human-authorship',
        );

        self::assertNull($store->findBySubmission($draft->channel, $draft->submittingActorReference, $draft->clientSubmissionId));
        $first = $send->send($draft, 'input:1', '2026-09-02T12:00:00Z');
        $replayed = $send->send($draft, 'input:different-retry-id', '2026-09-02T12:00:01Z');

        self::assertSame($first, $replayed);
        self::assertSame('input:1', $replayed->id);
        self::assertSame('human:author', $first->semanticAuthor->identity());
        self::assertSame('service:mcp', $first->submittingActorReference);
        self::assertSame('delegation:authorized-human-authorship', $first->delegationAttestation);
        self::assertCount(2, $first->parts);
        self::assertInstanceOf(StringInputPart::class, $first->parts[0]);
        self::assertInstanceOf(AttachmentInputPart::class, $first->parts[1]);
        self::assertSame($draft->exactText, $first->stringInputParts()[0]->exactText);
    }

    public function test_accepted_send_is_recovered_by_a_new_sender_using_the_same_store_boundary(): void
    {
        $store = new InMemoryUserInputStore();
        $draft = new UserInputDraft('submission:recover', new HumanActorReference('user:1'), 'user:1', new NamedInputChannel('burdgen'), 'Keep this after disconnect.');
        $accepted = (new SendPrimaryAskInput($store))->send($draft, 'input:recover', '2026-09-02T12:00:00Z');

        $afterDisconnect = new SendPrimaryAskInput($store);
        $recovered = $afterDisconnect->send($draft, 'input:retry', '2026-09-02T12:01:00Z');

        self::assertSame($accepted, $recovered);
        self::assertSame('input:recover', $recovered->id);
    }

    public function test_primary_ask_requires_a_human_authored_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PrimaryAskUserInput(
            'input:1',
            'submission:1',
            new HumanActorReference('user:1'),
            'user:1',
            new NamedInputChannel('burdgen'),
            [new AttachmentInputPart('part:file', 0, 'artifact:file-1', hash('sha256', 'file bytes'))],
            '2026-09-02T12:00:00Z',
        );
    }

    public function test_inferred_intent_is_separate_and_user_edit_supersedes_without_rewriting_input(): void
    {
        $input = new PrimaryAskUserInput(
            'input:source',
            'submission:source',
            new HumanActorReference('user:1'),
            'user:1',
            new NamedInputChannel('burdgen'),
            [new StringInputPart('part:text', 0, 'Help me understand this first.')],
            '2026-09-02T12:00:00Z',
        );
        $inferred = new InferredIntent('intent:a:v1', 'intent:a', $input->id, 'Clarify the request.', [], 'Target is unknown.', 1, 'model:local/config:v1');
        $edited = new UserEditedIntent('intent:a:v2', 'intent:a', $input->id, 'Discuss possible targets.', [], null, 2, 'user-input:edit-1');
        $preserved = $inferred->supersededBy($edited);

        self::assertSame('Help me understand this first.', $input->stringInputParts()[0]->exactText);
        self::assertSame(IntentOrigin::Inferred, $preserved->origin);
        self::assertSame(IntentStatus::Superseded, $preserved->status);
        self::assertSame($edited->id, $preserved->replacementIntentId);
        self::assertSame(IntentOrigin::UserEdited, $edited->origin);
        self::assertSame('model:local/config:v1', $preserved->provenance);
    }

    public function test_one_input_can_start_independent_sibling_intent_families(): void
    {
        $first = new InferredIntent('intent:a:v1', 'intent:a', 'input:1', 'Change authentication.', [], null, 1, 'fixture');
        $second = new InferredIntent('intent:b:v1', 'intent:b', 'input:1', 'Write release notes.', [], null, 1, 'fixture');

        self::assertSame($first->sourceInputId, $second->sourceInputId);
        self::assertNotSame($first->familyId, $second->familyId);
    }

    public function test_submission_keys_do_not_collide_when_values_contain_delimiters(): void
    {
        $store = new InMemoryUserInputStore();
        $first = new PrimaryAskUserInput('input:1', 'c', new HumanActorReference('human:1'), 'b', new NamedInputChannel('a'), [new StringInputPart('part:1', 0, 'First')], '2026-09-02T12:00:00Z', 'delegation:1');
        $second = new PrimaryAskUserInput('input:2', 'c', new HumanActorReference('human:2'), 'a|b', new NamedInputChannel('a'), [new StringInputPart('part:2', 0, 'Second')], '2026-09-02T12:00:00Z', 'delegation:2');

        $store->save($first);
        $store->save($second);

        self::assertSame($first, $store->findBySubmission(new NamedInputChannel('a'), 'b', 'c'));
        self::assertSame($second, $store->findBySubmission(new NamedInputChannel('a'), 'a|b', 'c'));
    }

    public function test_submission_identity_cannot_overwrite_different_evidence(): void
    {
        $store = new InMemoryUserInputStore();
        $first = new PrimaryAskUserInput('input:1', 'submission:1', new HumanActorReference('user:1'), 'user:1', new NamedInputChannel('burdgen'), [new StringInputPart('part:1', 0, 'Original')], '2026-09-02T12:00:00Z');
        $changed = new PrimaryAskUserInput('input:1', 'submission:1', new HumanActorReference('user:1'), 'user:1', new NamedInputChannel('burdgen'), [new StringInputPart('part:1', 0, 'Changed')], '2026-09-02T12:00:01Z');
        $store->save($first);

        $this->expectException(LogicException::class);
        $store->save($changed);
    }

    public function test_accepted_input_snapshots_channel_and_parts(): void
    {
        $channel = new class implements InputChannel {
            public string $value = 'burdgen';
            public function identity(): string { return $this->value; }
        };
        $part = new StringInputPart('part:1', 0, 'Original');
        $input = new PrimaryAskUserInput('input:1', 'submission:1', new HumanActorReference('user:1'), 'user:1', $channel, [$part], '2026-09-02T12:00:00Z');
        $channel->value = 'changed';

        self::assertSame('burdgen', $input->channel->identity());
        self::assertNotSame($channel, $input->channel);
        self::assertNotSame($part, $input->parts[0]);
        self::assertSame('Original', $input->stringInputParts()[0]->exactText);
    }

    public function test_unsupported_mutable_part_cannot_be_accepted(): void
    {
        $part = new class implements UserInputPart {
            public function id(): string { return 'part:1'; }
            public function position(): int { return 0; }
            public function contentHash(): string { return hash('sha256', 'mutable'); }
        };

        $this->expectException(InvalidArgumentException::class);
        new PrimaryAskUserInput('input:1', 'submission:1', new HumanActorReference('user:1'), 'user:1', new NamedInputChannel('burdgen'), [$part], '2026-09-02T12:00:00Z');
    }

    public function test_sparse_parts_cannot_be_accepted(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PrimaryAskUserInput('input:1', 'submission:1', new HumanActorReference('user:1'), 'user:1', new NamedInputChannel('burdgen'), [1 => new StringInputPart('part:1', 1, 'Text')], '2026-09-02T12:00:00Z');
    }

    public function test_impossible_acceptance_timestamp_cannot_be_accepted(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PrimaryAskUserInput('input:1', 'submission:1', new HumanActorReference('user:1'), 'user:1', new NamedInputChannel('burdgen'), [new StringInputPart('part:1', 0, 'Text')], '2026-99-99T99:99:99Z');
    }

    public function test_constraints_must_be_a_list(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new InferredIntent('intent:1:v1', 'intent:1', 'input:1', 'Clarify.', ['constraint' => 'not a list'], null, 1, 'fixture');
    }

    public function test_a_superseded_intent_cannot_be_superseded_again(): void
    {
        $first = new InferredIntent('intent:a:v1', 'intent:a', 'input:1', 'First.', [], null, 1, 'fixture');
        $second = new UserEditedIntent('intent:a:v2', 'intent:a', 'input:1', 'Second.', [], null, 2, 'fixture');
        $third = new UserEditedIntent('intent:a:v3', 'intent:a', 'input:1', 'Third.', [], null, 3, 'fixture');
        $superseded = $first->supersededBy($second);

        $this->expectException(InvalidArgumentException::class);
        $superseded->supersededBy($third);
    }
}
