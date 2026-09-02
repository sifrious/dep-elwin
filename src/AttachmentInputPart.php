<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

use InvalidArgumentException;

final readonly class AttachmentInputPart implements UserInputPart
{
    public function __construct(public string $id, public int $position, public string $reference, public string $contentHash)
    {
        if (trim($id) === '' || trim($reference) === '' || preg_match('/^[a-f0-9]{64}$/', $contentHash) !== 1 || $position < 0) {
            throw new InvalidArgumentException('An attachment part requires identity, position, reference, and SHA-256 hash.');
        }
    }

    public function id(): string { return $this->id; }
    public function position(): int { return $this->position; }
    public function contentHash(): string { return $this->contentHash; }
}
