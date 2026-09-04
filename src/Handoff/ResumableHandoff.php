<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Handoff;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;
use LogicException;
use Sifrious\ReferenceContract\CrossPackageReference;

/**
 * Provider-neutral intervention contract.
 *
 * This object describes whether a consumer may resume paused work. It does not
 * suspend, resume, or otherwise model the consumer's execution state.
 */
final readonly class ResumableHandoff implements JsonSerializable
{
    public const string DEFINITION_VERSION = 'resumable-handoff/v1';

    public function __construct(
        public string $id,
        public CrossPackageReference $conversation,
        public CrossPackageReference $pausedWork,
        public CrossPackageReference $question,
        public ResumeContext $resumeContext,
        public HandoffPayload $payload,
        public DateTimeImmutable $requestedAt,
        public ?DateTimeImmutable $expiresAt = null,
        public HandoffStatus $status = HandoffStatus::AwaitingResponse,
        public ?CrossPackageReference $response = null,
        public ?DateTimeImmutable $answeredAt = null,
        public ?DateTimeImmutable $cancelledAt = null,
        public ?DateTimeImmutable $expiredAt = null,
        public int $version = 1,
    ) {
        if (trim($id) === '' || $version < 1) {
            throw new InvalidArgumentException('A handoff requires identity and a positive version.');
        }
        if ($expiresAt !== null && $expiresAt <= $requestedAt) {
            throw new InvalidArgumentException('Handoff expiry must be later than its request time.');
        }
        if (($response === null) !== ($answeredAt === null)) {
            throw new InvalidArgumentException('A handoff response and answer time must be recorded together.');
        }
        if ($answeredAt !== null && ($answeredAt < $requestedAt || ($expiresAt !== null && $answeredAt >= $expiresAt))) {
            throw new InvalidArgumentException('A handoff answer must occur after its request and before expiry.');
        }
        if ($status === HandoffStatus::AwaitingResponse && $response !== null) {
            throw new InvalidArgumentException('An awaiting handoff cannot already contain a response.');
        }
        if ($status === HandoffStatus::Answered && ($response === null || $answeredAt === null)) {
            throw new InvalidArgumentException('An answered handoff requires a response and answer time.');
        }
        if ($status === HandoffStatus::Cancelled && $cancelledAt === null) {
            throw new InvalidArgumentException('A cancelled handoff requires a cancellation time.');
        }
        if (($status === HandoffStatus::Cancelled) !== ($cancelledAt !== null)) {
            throw new InvalidArgumentException('Only a cancelled handoff may have a cancellation time.');
        }
        if ($cancelledAt !== null && ($cancelledAt < $requestedAt || ($answeredAt !== null && $cancelledAt < $answeredAt))) {
            throw new InvalidArgumentException('Cancellation cannot predate the handoff request or answer.');
        }
        if ($status === HandoffStatus::Expired && $expiredAt === null) {
            throw new InvalidArgumentException('An expired handoff requires an expiry observation time.');
        }
        if (($status === HandoffStatus::Expired) !== ($expiredAt !== null)) {
            throw new InvalidArgumentException('Only an expired handoff may have an expiry observation time.');
        }
        if ($expiredAt !== null && ($expiresAt === null || $expiredAt < $expiresAt)) {
            throw new InvalidArgumentException('Expiry can be recorded only at or after a configured deadline.');
        }
    }

    public function reference(): CrossPackageReference
    {
        return new CrossPackageReference(
            'sifrious/elwin',
            'resumable-handoff',
            $this->id,
            (string) $this->version,
        );
    }

    /** @return list<HandoffTransition> */
    public function allowedTransitions(DateTimeImmutable $at): array
    {
        if ($at < $this->requestedAt) {
            return [];
        }
        if ($this->status === HandoffStatus::Cancelled || $this->status === HandoffStatus::Expired) {
            return [];
        }
        if ($this->deadlineReached($at)) {
            return [HandoffTransition::MarkExpired];
        }
        if ($this->status === HandoffStatus::AwaitingResponse) {
            return [HandoffTransition::SubmitResponse, HandoffTransition::Cancel];
        }

        return [HandoffTransition::ResumePausedWork, HandoffTransition::Cancel];
    }

    public function isAwaitingResponseAt(DateTimeImmutable $at): bool
    {
        return $at >= $this->requestedAt
            && $this->status === HandoffStatus::AwaitingResponse
            && ! $this->deadlineReached($at);
    }

    public function isResumableAt(DateTimeImmutable $at): bool
    {
        return $this->answeredAt !== null
            && $at >= $this->answeredAt
            && $this->status === HandoffStatus::Answered
            && ! $this->deadlineReached($at);
    }

    public function answer(CrossPackageReference $response, DateTimeImmutable $answeredAt): self
    {
        if (! in_array(HandoffTransition::SubmitResponse, $this->allowedTransitions($answeredAt), true)) {
            throw new LogicException('Only an unexpired handoff awaiting a response can be answered.');
        }

        return $this->next(
            status: HandoffStatus::Answered,
            response: $response,
            answeredAt: $answeredAt,
        );
    }

    public function cancel(DateTimeImmutable $cancelledAt): self
    {
        if (! in_array(HandoffTransition::Cancel, $this->allowedTransitions($cancelledAt), true)) {
            throw new LogicException('Only an active handoff can be cancelled.');
        }

        return $this->next(status: HandoffStatus::Cancelled, cancelledAt: $cancelledAt);
    }

    public function expire(DateTimeImmutable $expiredAt): self
    {
        if (! in_array(HandoffTransition::MarkExpired, $this->allowedTransitions($expiredAt), true)) {
            throw new LogicException('A handoff can expire only after its deadline.');
        }

        return $this->next(status: HandoffStatus::Expired, expiredAt: $expiredAt);
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'definition_version' => self::DEFINITION_VERSION,
            'id' => $this->id,
            'version' => $this->version,
            'conversation' => $this->conversation->toArray(),
            'paused_work' => $this->pausedWork->toArray(),
            'question' => $this->question->toArray(),
            'response' => $this->response?->toArray(),
            'resume_context' => $this->resumeContext->jsonSerialize(),
            'payload' => $this->payload->jsonSerialize(),
            'status' => $this->status->value,
            'requested_at' => $this->requestedAt->format(DATE_ATOM),
            'answered_at' => $this->answeredAt?->format(DATE_ATOM),
            'expires_at' => $this->expiresAt?->format(DATE_ATOM),
            'cancelled_at' => $this->cancelledAt?->format(DATE_ATOM),
            'expired_at' => $this->expiredAt?->format(DATE_ATOM),
        ];
    }

    private function deadlineReached(DateTimeImmutable $at): bool
    {
        return $this->expiresAt !== null && $at >= $this->expiresAt;
    }

    private function next(
        HandoffStatus $status,
        ?CrossPackageReference $response = null,
        ?DateTimeImmutable $answeredAt = null,
        ?DateTimeImmutable $cancelledAt = null,
        ?DateTimeImmutable $expiredAt = null,
    ): self {
        return new self(
            $this->id,
            $this->conversation,
            $this->pausedWork,
            $this->question,
            $this->resumeContext,
            $this->payload,
            $this->requestedAt,
            $this->expiresAt,
            $status,
            $response ?? $this->response,
            $answeredAt ?? $this->answeredAt,
            $cancelledAt,
            $expiredAt,
            $this->version + 1,
        );
    }
}
