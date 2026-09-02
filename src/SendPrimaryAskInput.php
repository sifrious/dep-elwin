<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

final readonly class SendPrimaryAskInput
{
    public function __construct(private UserInputStore $store) {}

    public function send(UserInputDraft $draft, string $inputId, string $acceptedAt): PrimaryAskUserInput
    {
        $existing = $this->store->findBySubmission($draft->channel, $draft->submittingActorReference, $draft->clientSubmissionId);
        if ($existing instanceof PrimaryAskUserInput) {
            return $existing;
        }

        $parts = [new StringInputPart($inputId.':text:0', 0, $draft->exactText)];
        foreach ($draft->attachments as $attachment) {
            $parts[] = new AttachmentInputPart(
                $attachment->id,
                count($parts),
                $attachment->reference,
                $attachment->contentHash,
            );
        }

        $input = new PrimaryAskUserInput(
            $inputId,
            $draft->clientSubmissionId,
            $draft->semanticAuthorReference,
            $draft->submittingActorReference,
            $draft->channel,
            $parts,
            $acceptedAt,
            $draft->delegationAttestation,
        );
        $this->store->save($input);

        return $input;
    }
}
