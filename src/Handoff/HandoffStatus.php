<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Handoff;

enum HandoffStatus: string
{
    case AwaitingResponse = 'awaiting_response';
    case Answered = 'answered';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
}
