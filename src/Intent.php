<?php
declare(strict_types=1);
namespace Sifrious\Elwin;
use InvalidArgumentException;
/** A versioned interpretation; it never replaces its source input. */
abstract readonly class Intent implements IntentContract
{
    /** @param list<string> $constraints */
    public function __construct(
        public string $id,
        public string $familyId,
        public string $sourceInputId,
        public string $summary,
        public array $constraints,
        public ?string $uncertainty,
        public int $interpretationVersion,
        public IntentOrigin $origin,
        public string $provenance,
        public IntentStatus $status = IntentStatus::Proposed,
        public ?string $replacementIntentId = null,
    )
    {
        if (trim($id) === '' || trim($familyId) === '' || trim($sourceInputId) === '' || trim($summary) === '' || trim($provenance) === '' || $interpretationVersion < 1) {
            throw new InvalidArgumentException('Intent requires identity, family, source input, summary, provenance, and a positive interpretation version.');
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
    final public function supersededBy(Intent $replacement): Intent
    {
        if ($replacement->familyId !== $this->familyId || $replacement->sourceInputId !== $this->sourceInputId || $replacement->interpretationVersion !== $this->interpretationVersion + 1) {
            throw new InvalidArgumentException('A replacement must be the next version in the same intent family and source input.');
        }

        return $this->withStatus(IntentStatus::Superseded, $replacement->id);
    }

    abstract protected function withStatus(IntentStatus $status, ?string $replacementIntentId): Intent;
}
