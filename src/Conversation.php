<?php

declare(strict_types=1);

namespace Sifrious\Elwin;

use InvalidArgumentException;
use LogicException;
use Sifrious\ReferenceContract\CrossPackageReference;

/** Provider-neutral deliberation identity. Provider sessions remain observations. */
final readonly class Conversation
{
    /**
     * @param list<string> $inputIds
     * @param list<string> $intentIds
     * @param list<ConversationMessageReference> $messages
     * @param list<ConversationEvent> $events
     * @param list<CrossPackageReference> $handoffs
     */
    public function __construct(
        public string $id,
        public array $inputIds,
        public array $intentIds = [],
        public ?string $providerSessionReference = null,
        public array $messages = [],
        public array $events = [],
        public ConversationStatus $status = ConversationStatus::Active,
        public array $handoffs = [],
        public int $version = 1,
    ) {
        if (trim($id) === '' || $inputIds === [] || $version < 1) {
            throw new InvalidArgumentException('Conversation identity, at least one input, and a positive version are required.');
        }
        if (! array_is_list($inputIds) || ! array_is_list($intentIds) || ! array_is_list($messages) || ! array_is_list($events) || ! array_is_list($handoffs)) {
            throw new InvalidArgumentException('Conversation collections must be lists.');
        }
        foreach ($handoffs as $handoff) {
            if (! $handoff instanceof CrossPackageReference) {
                throw new InvalidArgumentException('Conversation handoffs must use the shared cross-package reference contract.');
            }
        }
    }

    public function attachMessage(ConversationMessageReference $message): self
    {
        $this->assertOpen();
        $inputIds = $this->inputIds;
        if ($message->inputId !== null && ! in_array($message->inputId, $inputIds, true)) {
            $inputIds[] = $message->inputId;
        }

        return $this->next(inputIds: $inputIds, messages: [...$this->messages, $message]);
    }

    public function recordIntent(string $intentId): self
    {
        $this->assertOpen();
        if (trim($intentId) === '') {
            throw new InvalidArgumentException('Intent identity cannot be blank.');
        }

        return $this->next(intentIds: [...$this->intentIds, $intentId]);
    }

    public function recordEvent(ConversationEvent $event): self
    {
        $this->assertOpen();

        return $this->next(events: [...$this->events, $event]);
    }

    public function pause(): self
    {
        $this->assertOpen();

        return $this->next(status: ConversationStatus::Paused);
    }

    public function resume(): self
    {
        if ($this->status !== ConversationStatus::Paused) {
            throw new LogicException('Only a paused conversation can resume.');
        }

        return $this->next(status: ConversationStatus::Active);
    }

    public function finishWith(CrossPackageReference $handoff): self
    {
        $this->assertOpen();

        return $this->next(status: ConversationStatus::Finished, handoffs: [...$this->handoffs, $handoff]);
    }

    private function assertOpen(): void
    {
        if ($this->status !== ConversationStatus::Active) {
            throw new LogicException('Only an active conversation can accept new deliberation.');
        }
    }

    /**
     * @param list<string>|null $inputIds
     * @param list<string>|null $intentIds
     * @param list<ConversationMessageReference>|null $messages
     * @param list<ConversationEvent>|null $events
     * @param list<CrossPackageReference>|null $handoffs
     */
    private function next(?array $inputIds = null, ?array $intentIds = null, ?array $messages = null, ?array $events = null, ?ConversationStatus $status = null, ?array $handoffs = null): self
    {
        return new self(
            $this->id,
            $inputIds ?? $this->inputIds,
            $intentIds ?? $this->intentIds,
            $this->providerSessionReference,
            $messages ?? $this->messages,
            $events ?? $this->events,
            $status ?? $this->status,
            $handoffs ?? $this->handoffs,
            $this->version + 1,
        );
    }
}
