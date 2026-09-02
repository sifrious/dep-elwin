<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

use Sifrious\AuthorizationContract\AuthorizationContext;

/** Mutable editor state. It is not accepted source evidence until Send. */
final class UserInputDraft
{
    private bool $discarded = false;

    /** @param list<AttachmentInputPart> $attachments */
    public function __construct(
        public string $clientSubmissionId,
        public AuthorizationContext $authorization,
        public InputChannel $channel,
        public string $exactText = '',
        public array $attachments = [],
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
