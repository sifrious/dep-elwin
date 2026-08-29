<?php
declare(strict_types=1);
namespace Sifrious\Elwin;
enum IntentStatus: string
{
    case Proposed = 'proposed';
    case Active = 'active';
    case Satisfied = 'satisfied';
    case Abandoned = 'abandoned';
    case Superseded = 'superseded';
}
