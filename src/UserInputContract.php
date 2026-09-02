<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

interface UserInputContract
{
    public function id(): string;
    public function clientSubmissionId(): string;
    public function semanticAuthorReference(): string;
    public function submittingActorReference(): string;
    public function channel(): InputChannel;
    /** @return list<UserInputPart> */
    public function parts(): array;
    public function acceptedAt(): string;
}
