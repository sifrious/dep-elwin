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
        foreach ($constraints as $constraint) {
            if (! is_string($constraint) || trim($constraint) === '') {
                throw new InvalidArgumentException('Intent constraints must be non-empty strings.');
            }
        }
        if ($status === IntentStatus::Superseded && ($replacementIntentId === null || trim($replacementIntentId) === '')) {
            throw new InvalidArgumentException('A superseded intent must identify its replacement.');
        }
        if ($status !== IntentStatus::Superseded && $replacementIntentId !== null) {
            throw new InvalidArgumentException('Only a superseded intent may identify a replacement.');
        }
        if ($replacementIntentId === $id) {
            throw new InvalidArgumentException('An intent cannot supersede itself.');
        }
    }

    /**
     * Preserve this interpretation as historical evidence while linking its successor.
     * Creating the successor remains explicit so no provider or execution boundary is crossed here.
     */
    public function supersededBy(string $replacementIntentId): self
    {
        return new self(
            $this->id,
            $this->sourceInputId,
            $this->summary,
            $this->constraints,
            $this->uncertainty,
            $this->interpretationVersion,
            IntentStatus::Superseded,
            $replacementIntentId,
        );
    }
}
