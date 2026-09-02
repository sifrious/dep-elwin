<?php

declare(strict_types=1);

namespace Sifrious\Elwin;

enum ConversationStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Finished = 'finished';
}
