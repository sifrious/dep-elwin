<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Twinkle;

enum ProposedObjectStatus: string
{
    case Captured = 'captured';
    case Discussing = 'discussing';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Deferred = 'deferred';
    case Materializing = 'materializing';
    case Materialized = 'materialized';
    case Superseded = 'superseded';
}
