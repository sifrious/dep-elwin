<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Clarification;

/** Semantic question kinds. Consumers decide how to render them. */
enum ClarificationQuestionType: string
{
    case SingleSelection = 'single_selection';
    case MultipleSelection = 'multiple_selection';
    case BoundedText = 'bounded_text';
    case Confirmation = 'confirmation';
    case DecisionRequest = 'decision_request';
    case AttachmentEvidenceRequest = 'attachment_evidence_request';
}
