<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Handoff;

enum HandoffTransition: string
{
    case SubmitResponse = 'submit_response';
    case ResumePausedWork = 'resume_paused_work';
    case Cancel = 'cancel';
    case MarkExpired = 'mark_expired';
}
