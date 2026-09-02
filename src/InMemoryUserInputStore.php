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
        $accepted = $this->findOrCreate($input);
        if ($accepted !== $input) {
            throw new LogicException('A submission identity cannot overwrite accepted input.');
        }
    }

    public function findOrCreate(UserInput $candidate): UserInput
    {
        $key = $this->key($candidate->channel, $candidate->submittingActorReference, $candidate->clientSubmissionId);
        $existing = $this->inputs[$key] ?? null;
        if ($existing !== null) {
            if ($this->evidence($existing) !== $this->evidence($candidate)) {
                throw new LogicException('A submission identity cannot be reused for different evidence.');
            }
            return $existing;
        }
        $this->inputs[$key] = $candidate;
        return $candidate;
    }

    public function findBySubmission(InputChannel $channel, string $submittingActorReference, string $clientSubmissionId): ?UserInput
    {
        return $this->inputs[$this->key($channel, $submittingActorReference, $clientSubmissionId)] ?? null;
    }

    private function key(InputChannel $channel, string $actor, string $submission): string
    {
        return hash('sha256', json_encode([$channel->identity(), $actor, $submission], JSON_THROW_ON_ERROR));
    }

    private function evidence(UserInput $input): string
    {
        $parts = array_map(static fn (UserInputPart $part): array => match (true) {
            $part instanceof StringInputPart => ['string', $part->position, $part->exactText],
            $part instanceof AttachmentInputPart => ['attachment', $part->position, $part->reference, $part->contentHash],
        }, $input->parts);

        return json_encode([
            $input::class,
            $input->semanticAuthor->identity(),
            $input->submittingActorReference,
            $input->channel->identity(),
            $parts,
            $input->delegationAttestation,
        ], JSON_THROW_ON_ERROR);
    }
}
