<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

use InvalidArgumentException;
use Sifrious\AuthorizationContract\AuthorizationContext;
use Sifrious\ReferenceContract\CrossPackageReference;

/** Immutable evidence accepted from a human through an explicit Send boundary. */
abstract readonly class UserInput implements UserInputContract
{
    /** @param list<UserInputPart> $parts */
    public function __construct(
        public string $id,
        public string $clientSubmissionId,
        AuthorizationContext $authorization,
        InputChannel $channel,
        array $parts,
        public string $acceptedAt,
    ) {
        foreach ([$id, $clientSubmissionId] as $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException('Input identity and submission identity are required.');
            }
        }
        if ($parts === []) {
            throw new InvalidArgumentException('A user input requires at least one content part.');
        }
        $snapshots = [];
        if (! array_is_list($parts)) {
            throw new InvalidArgumentException('Input parts must be ordered from zero without gaps.');
        }
        foreach ($parts as $position => $part) {
            if (! $part instanceof UserInputPart || $part->position() !== $position) {
                throw new InvalidArgumentException('Input parts must be typed and ordered from zero without gaps.');
            }
            $snapshots[] = match (true) {
                $part instanceof StringInputPart => new StringInputPart($part->id, $part->position, $part->exactText),
                $part instanceof AttachmentInputPart => new AttachmentInputPart($part->id, $part->position, $part->reference, $part->contentHash),
                default => throw new InvalidArgumentException('Accepted input parts must be supported immutable value objects.'),
            };
        }
        if (preg_match('/^(\\d{4})-(\\d{2})-(\\d{2})T(\\d{2}):(\\d{2}):(\\d{2})(?:\\.\\d+)?Z$/', $acceptedAt, $timestamp) !== 1
            || ! checkdate((int) $timestamp[2], (int) $timestamp[3], (int) $timestamp[1])
            || (int) $timestamp[4] > 23
            || (int) $timestamp[5] > 59
            || (int) $timestamp[6] > 59) {
            throw new InvalidArgumentException('Input acceptance time must be UTC ISO-8601.');
        }
        $this->authorization = AuthorizationContext::fromArray($authorization->toArray());
        $this->channel = new NamedInputChannel($channel->identity());
        $this->parts = $snapshots;
    }

    public AuthorizationContext $authorization;
    public InputChannel $channel;
    /** @var list<UserInputPart> */
    public array $parts;

    final public function id(): string { return $this->id; }
    final public function clientSubmissionId(): string { return $this->clientSubmissionId; }
    final public function authorizationContext(): AuthorizationContext { return $this->authorization; }
    final public function semanticAuthorReference(): CrossPackageReference { return $this->authorization->actor->actingFor ?? $this->authorization->actor->actor; }
    final public function submittingActorReference(): CrossPackageReference { return $this->authorization->actor->actor; }
    final public function channel(): InputChannel { return $this->channel; }
    final public function parts(): array { return $this->parts; }
    final public function acceptedAt(): string { return $this->acceptedAt; }
}
