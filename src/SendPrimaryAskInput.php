<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

use LogicException;

final readonly class SendPrimaryAskInput
{
    public function __construct(private UserInputStore $store) {}

    public function send(UserInputDraft $draft, string $inputId, string $acceptedAt): PrimaryAskUserInput
    {
        if ($draft->isDiscarded()) {
            throw new LogicException('A discarded draft cannot be sent.');
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
            $draft->authorization,
            $draft->channel,
            $parts,
            $acceptedAt,
        );
        $accepted = $this->store->findOrCreate($input);
        if (! $accepted instanceof PrimaryAskUserInput) {
            throw new LogicException('The submission identity belongs to a different input purpose.');
        }

        return $accepted;
    }
}
