<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Clarification;

use DateTimeImmutable;
use InvalidArgumentException;
use Sifrious\ReferenceContract\CrossPackageReference;

/** Provider-neutral clarification request and the exact response shape that can unblock it. */
final readonly class ClarificationQuestion
{
    public const string DEFINITION_VERSION = 'clarification-question/v1';

    public function __construct(
        public string $id,
        public CrossPackageReference $conversation,
        public string $reason,
        public string $prompt,
        public AllowedResponseShape $allowedResponse,
        public DateTimeImmutable $askedAt,
        public string $definitionVersion = self::DEFINITION_VERSION,
    ) {
        if (trim($id) === '' || trim($reason) === '' || trim($prompt) === '' || trim($definitionVersion) === '') {
            throw new InvalidArgumentException('A clarification question requires identity, reason, prompt, and definition version.');
        }
    }

    public function type(): ClarificationQuestionType
    {
        return $this->allowedResponse->type;
    }

    public function accepts(ClarificationResponse $response): bool
    {
        return $response->questionId === $this->id && $this->allowedResponse->accepts($response);
    }
}
