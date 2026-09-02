<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

/** Mutable editor state. It is not accepted source evidence until Send. */
final class UserInputDraft
{
    private bool $discarded = false;

    /** @param list<AttachmentInputPart> $attachments */
    public function __construct(
        public string $clientSubmissionId,
        public HumanActorReference $semanticAuthor,
        public string $submittingActorReference,
        public InputChannel $channel,
        public string $exactText = '',
        public array $attachments = [],
        public ?string $delegationAttestation = null,
    ) {}

    public function replaceText(string $exactText): void
    {
        if ($this->discarded) {
            throw new \LogicException('A discarded draft cannot be edited.');
        }
        $this->exactText = $exactText;
    }

    public function discard(): void { $this->discarded = true; }
    public function isDiscarded(): bool { return $this->discarded; }
}
