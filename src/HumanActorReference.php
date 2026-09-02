<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

use InvalidArgumentException;

/** Identifies the human semantic author of accepted user input. */
final readonly class HumanActorReference
{
    public function __construct(public string $identity)
    {
        if (trim($identity) === '') {
            throw new InvalidArgumentException('A human actor identity is required.');
        }
    }

    public function identity(): string { return $this->identity; }
}
