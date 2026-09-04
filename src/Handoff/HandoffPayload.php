<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Handoff;

use InvalidArgumentException;
use JsonException;
use JsonSerializable;

/** JSON-portable presentation context; it carries no provider or UI behavior. */
final readonly class HandoffPayload implements JsonSerializable
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $schema,
        public array $data,
    ) {
        if (trim($schema) === '') {
            throw new InvalidArgumentException('A handoff payload schema is required.');
        }
        if (array_is_list($data)) {
            throw new InvalidArgumentException('Handoff payload data must be an object-shaped array.');
        }

        try {
            json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Handoff payload data must be JSON-portable.', previous: $exception);
        }
    }

    /** @return array{schema: string, data: array<string, mixed>} */
    public function jsonSerialize(): array
    {
        return ['schema' => $this->schema, 'data' => $this->data];
    }
}
