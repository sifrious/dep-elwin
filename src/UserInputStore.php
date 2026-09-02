<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

interface UserInputStore
{
    /** Atomically returns the accepted replay or persists and returns the candidate. */
    public function findOrCreate(UserInput $candidate): UserInput;
    public function save(UserInput $input): void;
    public function findBySubmission(InputChannel $channel, string $submittingActorReference, string $clientSubmissionId): ?UserInput;
}
