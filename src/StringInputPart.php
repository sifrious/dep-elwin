<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

use InvalidArgumentException;

final readonly class StringInputPart implements UserInputPart
{
    public string $contentHash;

    public function __construct(public string $id, public int $position, public string $exactText)
    {
        if (trim($id) === '' || trim($exactText) === '' || $position < 0) {
            throw new InvalidArgumentException('A string input part requires identity, position, and nonempty human-authored text.');
        }
        $this->contentHash = hash('sha256', $exactText);
    }

    public function id(): string { return $this->id; }
    public function position(): int { return $this->position; }
    public function contentHash(): string { return $this->contentHash; }
}
