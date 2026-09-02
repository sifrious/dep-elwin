<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

interface UserInputPart
{
    public function id(): string;
    public function position(): int;
    public function contentHash(): string;
}
