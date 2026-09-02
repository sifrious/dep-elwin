<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

use InvalidArgumentException;

final readonly class PrimaryAskUserInput extends UserInput implements HasStringInput, HasAttachmentInput
{
    /** @param list<UserInputPart> $parts */
    public function __construct(
        string $id,
        string $clientSubmissionId,
        string $semanticAuthorReference,
        string $submittingActorReference,
        InputChannel $channel,
        array $parts,
        string $acceptedAt,
        ?string $delegationAttestation = null,
    ) {
        if (array_filter($parts, static fn (mixed $part): bool => $part instanceof StringInputPart) === []) {
            throw new InvalidArgumentException('Primary Ask requires at least one human-authored string part.');
        }
        parent::__construct($id, $clientSubmissionId, $semanticAuthorReference, $submittingActorReference, $channel, $parts, $acceptedAt, $delegationAttestation);
    }

    public function stringInputParts(): array
    {
        return array_values(array_filter($this->parts, static fn (UserInputPart $part): bool => $part instanceof StringInputPart));
    }

    public function attachmentInputParts(): array
    {
        return array_values(array_filter($this->parts, static fn (UserInputPart $part): bool => $part instanceof AttachmentInputPart));
    }
}
