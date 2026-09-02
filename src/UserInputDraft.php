<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

/** Mutable editor state. It is not accepted source evidence until Send. */
final class UserInputDraft
{
    /** @param list<AttachmentInputPart> $attachments */
    public function __construct(
        public string $clientSubmissionId,
        public string $semanticAuthorReference,
        public string $submittingActorReference,
        public InputChannel $channel,
        public string $exactText = '',
        public array $attachments = [],
        public ?string $delegationAttestation = null,
    ) {}
}
