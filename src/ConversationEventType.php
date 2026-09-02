<?php

declare(strict_types=1);

namespace Sifrious\Elwin;

enum ConversationEventType: string
{
    case QuestionAsked = 'question_asked';
    case ResponseReceived = 'response_received';
    case InterventionRecorded = 'intervention_recorded';
    case DecisionRecorded = 'decision_recorded';
}
