<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Disposition;

enum ExecutionReadiness: string
{
    case NotApplicable = 'not_applicable';
    case NotReady = 'not_ready';
    case ReadyForPlanning = 'ready_for_planning';
    case DirectExecutionCandidate = 'direct_execution_candidate';
    case Blocked = 'blocked';
}
