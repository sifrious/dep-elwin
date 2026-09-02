<?php

declare(strict_types=1);

namespace Sifrious\Elwin;

use InvalidArgumentException;
use Sifrious\ReferenceContract\CrossPackageReference;

final readonly class ConversationEvent
{
    public function __construct(
        public string $id,
        public ConversationEventType $type,
        public CrossPackageReference $subject,
        public string $recordedAt,
        public ?CrossPackageReference $respondsTo = null,
    ) {
        if (trim($id) === '' || trim($recordedAt) === '') {
            throw new InvalidArgumentException('Conversation event identity and timestamp are required.');
        }
    }
}
