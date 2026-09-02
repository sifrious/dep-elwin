<?php

declare(strict_types=1);

namespace Sifrious\Elwin;

use InvalidArgumentException;
use Sifrious\ReferenceContract\CrossPackageReference;

final readonly class ConversationMessageReference
{
    public function __construct(
        public string $id,
        public string $sourceAdapter,
        public string $sourceMessageId,
        public ?string $inputId,
        public CrossPackageReference $author,
        public string $observedAt,
    ) {
        foreach ([$id, $sourceAdapter, $sourceMessageId, $observedAt] as $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException('Conversation message reference fields cannot be blank.');
            }
        }
    }
}
