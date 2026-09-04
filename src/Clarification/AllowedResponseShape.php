<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Clarification;

use InvalidArgumentException;

/**
 * Machine-readable response constraints. It deliberately contains no widget,
 * layout, provider, or presentation metadata.
 */
final readonly class AllowedResponseShape
{
    /**
     * @param list<ClarificationOption> $options
     * @param list<string> $allowedEvidenceTypes
     */
    private function __construct(
        public ClarificationQuestionType $type,
        public array $options = [],
        public ?int $minimum = null,
        public ?int $maximum = null,
        public array $allowedEvidenceTypes = [],
        public ?int $maximumRationaleLength = null,
    ) {
    }

    /** @param list<ClarificationOption> $options */
    public static function singleSelection(array $options): self
    {
        self::validateOptions($options);

        return new self(ClarificationQuestionType::SingleSelection, $options, 1, 1);
    }

    /** @param list<ClarificationOption> $options */
    public static function multipleSelection(array $options, int $minimum = 1, ?int $maximum = null): self
    {
        self::validateOptions($options);
        $maximum ??= count($options);
        if ($minimum < 1 || $maximum < $minimum || $maximum > count($options)) {
            throw new InvalidArgumentException('Multiple-selection bounds must fit the available options.');
        }

        return new self(ClarificationQuestionType::MultipleSelection, $options, $minimum, $maximum);
    }

    public static function boundedText(int $maximumLength, int $minimumLength = 1): self
    {
        if ($minimumLength < 1 || $maximumLength < $minimumLength) {
            throw new InvalidArgumentException('Text bounds require a positive maximum no smaller than the minimum.');
        }

        return new self(ClarificationQuestionType::BoundedText, minimum: $minimumLength, maximum: $maximumLength);
    }

    public static function confirmation(): self
    {
        return new self(ClarificationQuestionType::Confirmation);
    }

    /** @param list<ClarificationOption> $options */
    public static function decisionRequest(array $options, ?int $maximumRationaleLength = null): self
    {
        self::validateOptions($options);
        if ($maximumRationaleLength !== null && $maximumRationaleLength < 1) {
            throw new InvalidArgumentException('A rationale limit must be positive when provided.');
        }

        return new self(
            ClarificationQuestionType::DecisionRequest,
            $options,
            1,
            1,
            maximumRationaleLength: $maximumRationaleLength,
        );
    }

    /**
     * Evidence types are portable reference types, not MIME types or provider identifiers.
     *
     * @param list<string> $allowedEvidenceTypes Empty means any portable evidence type.
     */
    public static function attachmentEvidenceRequest(int $minimum = 1, ?int $maximum = null, array $allowedEvidenceTypes = []): self
    {
        if ($minimum < 1 || ($maximum !== null && $maximum < $minimum) || ! array_is_list($allowedEvidenceTypes)) {
            throw new InvalidArgumentException('Attachment/evidence bounds require a positive, ordered range and a list of evidence types.');
        }
        foreach ($allowedEvidenceTypes as $type) {
            if (trim($type) === '') {
                throw new InvalidArgumentException('Allowed evidence types cannot be blank.');
            }
        }
        if (count(array_unique($allowedEvidenceTypes)) !== count($allowedEvidenceTypes)) {
            throw new InvalidArgumentException('Allowed evidence types must be unique.');
        }

        return new self(ClarificationQuestionType::AttachmentEvidenceRequest, minimum: $minimum, maximum: $maximum, allowedEvidenceTypes: $allowedEvidenceTypes);
    }

    public function accepts(ClarificationResponse $response): bool
    {
        if ($response->kind->isSafeExit()) {
            return true;
        }

        return match ($this->type) {
            ClarificationQuestionType::SingleSelection => $response->kind === ClarificationResponseKind::SingleSelection
                && $this->allSelectionsExist($response->selections),
            ClarificationQuestionType::MultipleSelection => $response->kind === ClarificationResponseKind::MultipleSelection
                && $this->withinBounds(count($response->selections))
                && $this->allSelectionsExist($response->selections),
            ClarificationQuestionType::BoundedText => $response->kind === ClarificationResponseKind::Text
                && $this->withinBounds(mb_strlen((string) $response->text)),
            ClarificationQuestionType::Confirmation => $response->kind === ClarificationResponseKind::Confirmation,
            ClarificationQuestionType::DecisionRequest => $response->kind === ClarificationResponseKind::Decision
                && $this->allSelectionsExist($response->selections)
                && ($response->note === null || $this->maximumRationaleLength === null || mb_strlen($response->note) <= $this->maximumRationaleLength),
            ClarificationQuestionType::AttachmentEvidenceRequest => $response->kind === ClarificationResponseKind::AttachmentEvidence
                && $this->withinBounds(count($response->attachments))
                && $this->allEvidenceTypesAreAllowed($response),
        };
    }

    /** @param list<ClarificationOption> $options */
    private static function validateOptions(array $options): void
    {
        if (! array_is_list($options) || count($options) < 2) {
            throw new InvalidArgumentException('Selection and decision questions require at least two options.');
        }
        $values = [];
        foreach ($options as $option) {
            if (! $option instanceof ClarificationOption) {
                throw new InvalidArgumentException('Question options must be clarification options.');
            }
            $values[] = $option->value;
        }
        if (count(array_unique($values)) !== count($values)) {
            throw new InvalidArgumentException('Question option values must be unique.');
        }
    }

    /** @param list<string> $selections */
    private function allSelectionsExist(array $selections): bool
    {
        $allowed = array_map(static fn (ClarificationOption $option): string => $option->value, $this->options);

        return $selections !== [] && count(array_diff($selections, $allowed)) === 0;
    }

    private function withinBounds(int $count): bool
    {
        return $count >= (int) $this->minimum && ($this->maximum === null || $count <= $this->maximum);
    }

    private function allEvidenceTypesAreAllowed(ClarificationResponse $response): bool
    {
        if ($this->allowedEvidenceTypes === []) {
            return true;
        }
        foreach ($response->attachments as $attachment) {
            if (! in_array($attachment->type, $this->allowedEvidenceTypes, true)) {
                return false;
            }
        }

        return true;
    }
}
