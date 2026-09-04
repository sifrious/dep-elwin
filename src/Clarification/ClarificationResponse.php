<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Clarification;

use DateTimeImmutable;
use InvalidArgumentException;
use Sifrious\ReferenceContract\CrossPackageReference;

/**
 * Provider-neutral response data. Use the named constructors so incompatible
 * payload fields cannot be combined.
 */
final readonly class ClarificationResponse
{
    /**
     * @param list<string> $selections
     * @param list<CrossPackageReference> $attachments
     */
    private function __construct(
        public string $id,
        public string $questionId,
        public ClarificationResponseKind $kind,
        public DateTimeImmutable $recordedAt,
        public array $selections = [],
        public ?string $text = null,
        public ?bool $confirmed = null,
        public array $attachments = [],
        public ?string $note = null,
    ) {
        if (trim($id) === '' || trim($questionId) === '') {
            throw new InvalidArgumentException('A clarification response requires response and question identities.');
        }
    }

    public static function singleSelection(string $id, string $questionId, string $selection, DateTimeImmutable $recordedAt): self
    {
        self::requireText($selection, 'A single-selection response requires one selection.');

        return new self($id, $questionId, ClarificationResponseKind::SingleSelection, $recordedAt, [$selection]);
    }

    /** @param list<string> $selections */
    public static function multipleSelection(string $id, string $questionId, array $selections, DateTimeImmutable $recordedAt): self
    {
        if (! array_is_list($selections) || $selections === [] || count(array_unique($selections)) !== count($selections)) {
            throw new InvalidArgumentException('A multiple-selection response requires a nonempty list of unique selections.');
        }
        foreach ($selections as $selection) {
            self::requireText($selection, 'Selection values cannot be blank.');
        }

        return new self($id, $questionId, ClarificationResponseKind::MultipleSelection, $recordedAt, $selections);
    }

    public static function text(string $id, string $questionId, string $text, DateTimeImmutable $recordedAt): self
    {
        self::requireText($text, 'A text response cannot be blank.');

        return new self($id, $questionId, ClarificationResponseKind::Text, $recordedAt, text: $text);
    }

    public static function confirmation(string $id, string $questionId, bool $confirmed, DateTimeImmutable $recordedAt): self
    {
        return new self($id, $questionId, ClarificationResponseKind::Confirmation, $recordedAt, confirmed: $confirmed);
    }

    public static function decision(string $id, string $questionId, string $selection, DateTimeImmutable $recordedAt, ?string $rationale = null): self
    {
        self::requireText($selection, 'A decision response requires one decision.');

        return new self($id, $questionId, ClarificationResponseKind::Decision, $recordedAt, [$selection], note: $rationale);
    }

    /** @param list<CrossPackageReference> $attachments */
    public static function attachmentEvidence(string $id, string $questionId, array $attachments, DateTimeImmutable $recordedAt, ?string $note = null): self
    {
        if (! array_is_list($attachments) || $attachments === []) {
            throw new InvalidArgumentException('An attachment/evidence response requires a nonempty list of portable references.');
        }
        foreach ($attachments as $attachment) {
            if (! $attachment instanceof CrossPackageReference) {
                throw new InvalidArgumentException('Attachment evidence must use the shared cross-package reference contract.');
            }
        }

        return new self($id, $questionId, ClarificationResponseKind::AttachmentEvidence, $recordedAt, attachments: $attachments, note: $note);
    }

    public static function refusal(string $id, string $questionId, DateTimeImmutable $recordedAt, ?string $reason = null): self
    {
        return new self($id, $questionId, ClarificationResponseKind::Refusal, $recordedAt, note: $reason);
    }

    public static function cancellation(string $id, string $questionId, DateTimeImmutable $recordedAt, ?string $reason = null): self
    {
        return new self($id, $questionId, ClarificationResponseKind::Cancellation, $recordedAt, note: $reason);
    }

    private static function requireText(string $value, string $message): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException($message);
        }
    }
}
