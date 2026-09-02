<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

enum IntentOrigin: string
{
    case Inferred = 'inferred';
    case UserEdited = 'user-edited';
}
