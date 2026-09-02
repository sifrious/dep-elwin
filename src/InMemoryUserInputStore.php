<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

use LogicException;

final class InMemoryUserInputStore implements UserInputStore
{
    /** @var array<string, UserInput> */
    private array $inputs = [];

    public function save(UserInput $input): void
    {
        $key = $this->key($input->channel, $input->submittingActorReference, $input->clientSubmissionId);
        if (isset($this->inputs[$key]) && $this->inputs[$key]->id !== $input->id) {
            throw new LogicException('A submission identity cannot be reused for a different input.');
        }
        $this->inputs[$key] = $input;
    }

    public function findBySubmission(InputChannel $channel, string $submittingActorReference, string $clientSubmissionId): ?UserInput
    {
        return $this->inputs[$this->key($channel, $submittingActorReference, $clientSubmissionId)] ?? null;
    }

    private function key(InputChannel $channel, string $actor, string $submission): string
    {
        return $channel->identity().'|'.$actor.'|'.$submission;
    }
}
