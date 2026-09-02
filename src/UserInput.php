<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

use InvalidArgumentException;

/** Immutable evidence accepted from a human through an explicit Send boundary. */
abstract readonly class UserInput implements UserInputContract
{
    /** @param list<UserInputPart> $parts */
    public function __construct(
        public string $id,
        public string $clientSubmissionId,
        public string $semanticAuthorReference,
        public string $submittingActorReference,
        public InputChannel $channel,
        public array $parts,
        public string $acceptedAt,
        public ?string $delegationAttestation = null,
    ) {
        foreach ([$id, $clientSubmissionId, $semanticAuthorReference, $submittingActorReference] as $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException('Input identity, submission identity, semantic author, and submitting actor are required.');
            }
        }
        if ($semanticAuthorReference !== $submittingActorReference && trim((string) $delegationAttestation) === '') {
            throw new InvalidArgumentException('Delegated human authorship requires an attestation reference.');
        }
        if ($parts === []) {
            throw new InvalidArgumentException('A user input requires at least one content part.');
        }
        foreach ($parts as $position => $part) {
            if (! $part instanceof UserInputPart || $part->position() !== $position) {
                throw new InvalidArgumentException('Input parts must be typed and ordered from zero without gaps.');
            }
        }
        if (preg_match('/^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}(?:\\.\\d+)?Z$/', $acceptedAt) !== 1) {
            throw new InvalidArgumentException('Input acceptance time must be UTC ISO-8601.');
        }
    }

    final public function id(): string { return $this->id; }
    final public function clientSubmissionId(): string { return $this->clientSubmissionId; }
    final public function semanticAuthorReference(): string { return $this->semanticAuthorReference; }
    final public function submittingActorReference(): string { return $this->submittingActorReference; }
    final public function channel(): InputChannel { return $this->channel; }
    final public function parts(): array { return $this->parts; }
    final public function acceptedAt(): string { return $this->acceptedAt; }
}
