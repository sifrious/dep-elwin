<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Tests;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Sifrious\Elwin\Clarification\AllowedResponseShape;
use Sifrious\Elwin\Clarification\ClarificationOption;
use Sifrious\Elwin\Clarification\ClarificationQuestion;
use Sifrious\Elwin\Clarification\ClarificationQuestionType;
use Sifrious\Elwin\Clarification\ClarificationResponse;
use Sifrious\ReferenceContract\CrossPackageReference;

final class ClarificationQuestionContractTest extends TestCase
{
    public function test_choice_question_names_why_it_is_needed_and_what_unblocks_it(): void
    {
        $question = $this->question(
            'q:repository',
            'The target repository is ambiguous.',
            'Which repository should be changed?',
            AllowedResponseShape::singleSelection($this->options('api', 'web')),
        );

        self::assertSame(ClarificationQuestionType::SingleSelection, $question->type());
        self::assertSame('The target repository is ambiguous.', $question->reason);
        self::assertTrue($question->accepts(ClarificationResponse::singleSelection('r:1', $question->id, 'api', $this->at())));
        self::assertFalse($question->accepts(ClarificationResponse::singleSelection('r:2', $question->id, 'worker', $this->at())));
    }

    public function test_free_text_question_enforces_its_bounds(): void
    {
        $question = $this->question(
            'q:scope',
            'A bounded scope statement is needed before planning.',
            'Describe the requested scope.',
            AllowedResponseShape::boundedText(20, 3),
        );

        self::assertTrue($question->accepts(ClarificationResponse::text('r:1', $question->id, 'API only', $this->at())));
        self::assertFalse($question->accepts(ClarificationResponse::text('r:2', $question->id, 'No', $this->at())));
        self::assertFalse($question->accepts(ClarificationResponse::text('r:3', $question->id, str_repeat('x', 21), $this->at())));
    }

    public function test_multiple_selection_confirmation_and_decision_have_distinct_shapes(): void
    {
        $multiple = $this->question('q:areas', 'Affected areas are unknown.', 'Select affected areas.', AllowedResponseShape::multipleSelection($this->options('api', 'web', 'docs'), 1, 2));
        $confirmation = $this->question('q:confirm', 'The destructive action needs confirmation.', 'Continue?', AllowedResponseShape::confirmation());
        $decision = $this->question('q:decision', 'A durable direction is required.', 'Choose the migration direction.', AllowedResponseShape::decisionRequest($this->options('forward', 'backward'), 40));

        self::assertTrue($multiple->accepts(ClarificationResponse::multipleSelection('r:1', $multiple->id, ['api', 'docs'], $this->at())));
        self::assertFalse($multiple->accepts(ClarificationResponse::multipleSelection('r:2', $multiple->id, ['api', 'web', 'docs'], $this->at())));
        self::assertTrue($confirmation->accepts(ClarificationResponse::confirmation('r:3', $confirmation->id, false, $this->at())));
        self::assertTrue($decision->accepts(ClarificationResponse::decision('r:4', $decision->id, 'forward', $this->at(), 'Preserves current data.')));
        self::assertFalse($decision->accepts(ClarificationResponse::singleSelection('r:5', $decision->id, 'forward', $this->at())));
    }

    public function test_attachment_request_uses_portable_evidence_references_and_bounds(): void
    {
        $question = $this->question(
            'q:evidence',
            'A log is needed to diagnose the failure.',
            'Attach one or two log artifacts.',
            AllowedResponseShape::attachmentEvidenceRequest(1, 2, ['log']),
        );
        $log = new CrossPackageReference('sifrious/funes', 'log', 'artifact:1');
        $image = new CrossPackageReference('sifrious/funes', 'image', 'artifact:2');

        self::assertTrue($question->accepts(ClarificationResponse::attachmentEvidence('r:1', $question->id, [$log], $this->at())));
        self::assertFalse($question->accepts(ClarificationResponse::attachmentEvidence('r:2', $question->id, [$image], $this->at())));
    }

    public function test_every_question_allows_explicit_refusal_or_cancellation_without_fabricating_an_answer(): void
    {
        $shapes = [
            AllowedResponseShape::singleSelection($this->options('a', 'b')),
            AllowedResponseShape::multipleSelection($this->options('a', 'b')),
            AllowedResponseShape::boundedText(20),
            AllowedResponseShape::confirmation(),
            AllowedResponseShape::decisionRequest($this->options('a', 'b')),
            AllowedResponseShape::attachmentEvidenceRequest(),
        ];

        foreach ($shapes as $index => $shape) {
            $question = $this->question("q:{$index}", 'Input is needed.', 'Provide input.', $shape);
            self::assertTrue($question->accepts(ClarificationResponse::refusal("r:refuse:{$index}", $question->id, $this->at(), 'Not safe to disclose.')));
            self::assertTrue($question->accepts(ClarificationResponse::cancellation("r:cancel:{$index}", $question->id, $this->at())));
        }
    }

    public function test_response_for_another_question_does_not_unblock_this_question(): void
    {
        $question = $this->question('q:one', 'Input is needed.', 'Continue?', AllowedResponseShape::confirmation());

        self::assertFalse($question->accepts(ClarificationResponse::confirmation('r:1', 'q:other', true, $this->at())));
    }

    public function test_invalid_shape_constraints_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        AllowedResponseShape::multipleSelection($this->options('a', 'b'), 2, 1);
    }

    private function question(string $id, string $reason, string $prompt, AllowedResponseShape $shape): ClarificationQuestion
    {
        return new ClarificationQuestion(
            $id,
            new CrossPackageReference('sifrious/elwin', 'conversation', 'conversation:1'),
            $reason,
            $prompt,
            $shape,
            $this->at(),
        );
    }

    /** @return list<ClarificationOption> */
    private function options(string ...$values): array
    {
        return array_map(static fn (string $value): ClarificationOption => new ClarificationOption($value, ucfirst($value)), $values);
    }

    private function at(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-09-04T13:00:00Z');
    }
}
