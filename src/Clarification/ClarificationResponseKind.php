<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Clarification;

enum ClarificationResponseKind: string
{
    case SingleSelection = 'single_selection';
    case MultipleSelection = 'multiple_selection';
    case Text = 'text';
    case Confirmation = 'confirmation';
    case Decision = 'decision';
    case AttachmentEvidence = 'attachment_evidence';
    case Refusal = 'refusal';
    case Cancellation = 'cancellation';

    public function isSafeExit(): bool
    {
        return $this === self::Refusal || $this === self::Cancellation;
    }
}
