<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Disposition;

enum NextTransition: string
{
    case ContinueConversation = 'continue_conversation';
    case Clarify = 'clarify';
    case Research = 'research';
    case RecordDecision = 'record_decision';
    case Plan = 'plan';
    case CreateOrUpdateWorkItems = 'create_or_update_work_items';
    case DirectExecutionCandidate = 'direct_execution_candidate';
    case NoAction = 'no_action';
}
