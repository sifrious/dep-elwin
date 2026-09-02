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
        HumanActorReference $semanticAuthor,
        public string $submittingActorReference,
        InputChannel $channel,
        array $parts,
        public string $acceptedAt,
        public ?string $delegationAttestation = null,
    ) {
        foreach ([$id, $clientSubmissionId, $submittingActorReference] as $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException('Input identity, submission identity, semantic author, and submitting actor are required.');
            }
        }
        if ($semanticAuthor->identity() !== $submittingActorReference && trim((string) $delegationAttestation) === '') {
            throw new InvalidArgumentException('Delegated human authorship requires an attestation reference.');
        }
        if ($parts === []) {
            throw new InvalidArgumentException('A user input requires at least one content part.');
        }
        $snapshots = [];
        foreach ($parts as $position => $part) {
            if (! $part instanceof UserInputPart || $part->position() !== $position) {
                throw new InvalidArgumentException('Input parts must be typed and ordered from zero without gaps.');
            }
            $snapshots[] = match (true) {
                $part instanceof StringInputPart => new StringInputPart($part->id, $part->position, $part->exactText),
                $part instanceof AttachmentInputPart => new AttachmentInputPart($part->id, $part->position, $part->reference, $part->contentHash),
                default => throw new InvalidArgumentException('Accepted input parts must be supported immutable value objects.'),
            };
        }
        if (preg_match('/^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}(?:\\.\\d+)?Z$/', $acceptedAt) !== 1) {
            throw new InvalidArgumentException('Input acceptance time must be UTC ISO-8601.');
        }
        $this->semanticAuthor = new HumanActorReference($semanticAuthor->identity());
        $this->channel = new NamedInputChannel($channel->identity());
        $this->parts = $snapshots;
    }

    public HumanActorReference $semanticAuthor;
    public InputChannel $channel;
    /** @var list<UserInputPart> */
    public array $parts;

    final public function id(): string { return $this->id; }
    final public function clientSubmissionId(): string { return $this->clientSubmissionId; }
    final public function semanticAuthor(): HumanActorReference { return $this->semanticAuthor; }
    final public function submittingActorReference(): string { return $this->submittingActorReference; }
    final public function channel(): InputChannel { return $this->channel; }
    final public function parts(): array { return $this->parts; }
    final public function acceptedAt(): string { return $this->acceptedAt; }
}
