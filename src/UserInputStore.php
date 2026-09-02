<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

use Sifrious\ReferenceContract\CrossPackageReference;

interface UserInputStore
{
    /** Atomically returns the accepted replay or persists and returns the candidate. */
    public function findOrCreate(UserInput $candidate): UserInput;
    public function save(UserInput $input): void;
    public function findBySubmission(InputChannel $channel, CrossPackageReference $submittingActorReference, string $clientSubmissionId): ?UserInput;
}
