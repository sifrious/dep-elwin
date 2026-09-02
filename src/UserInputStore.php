<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

interface UserInputStore
{
    public function save(UserInput $input): void;
    public function findBySubmission(InputChannel $channel, string $submittingActorReference, string $clientSubmissionId): ?UserInput;
}
