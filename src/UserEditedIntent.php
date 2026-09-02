<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

final readonly class UserEditedIntent extends Intent
{
    /** @param list<string> $constraints */
    public function __construct(string $id, string $familyId, string $sourceInputId, string $summary, array $constraints, ?string $uncertainty, int $interpretationVersion, string $provenance, IntentStatus $status = IntentStatus::Active, ?string $replacementIntentId = null)
    {
        parent::__construct($id, $familyId, $sourceInputId, $summary, $constraints, $uncertainty, $interpretationVersion, IntentOrigin::UserEdited, $provenance, $status, $replacementIntentId);
    }

    protected function withStatus(IntentStatus $status, ?string $replacementIntentId): Intent
    {
        return new self($this->id, $this->familyId, $this->sourceInputId, $this->summary, $this->constraints, $this->uncertainty, $this->interpretationVersion, $this->provenance, $status, $replacementIntentId);
    }
}
