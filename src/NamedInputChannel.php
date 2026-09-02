<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

use InvalidArgumentException;

final readonly class NamedInputChannel implements InputChannel
{
    public function __construct(private string $identity)
    {
        if (trim($identity) === '') {
            throw new InvalidArgumentException('An input channel identity is required.');
        }
    }

    public function identity(): string { return $this->identity; }
}
