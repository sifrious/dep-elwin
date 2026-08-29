<?php
declare(strict_types=1);
namespace Sifrious\Elwin;
use InvalidArgumentException;
/** A versioned interpretation; it never replaces its source input. */
final readonly class Intent
{
    /** @param list<string> $constraints */
    public function __construct(public string $id, public string $sourceInputId, public string $summary, public array $constraints, public ?string $uncertainty, public int $interpretationVersion, public IntentStatus $status = IntentStatus::Proposed, public ?string $replacementIntentId = null)
    {
        if (trim($id) === '' || trim($sourceInputId) === '' || trim($summary) === '' || $interpretationVersion < 1) {
            throw new InvalidArgumentException('Intent requires identity, source input, summary, and a positive interpretation version.');
        }
        if ($status === IntentStatus::Superseded && $replacementIntentId === null) {
            throw new InvalidArgumentException('A superseded intent must identify its replacement.');
        }
    }
}
