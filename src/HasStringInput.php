<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

interface HasStringInput
{
    /** @return non-empty-list<StringInputPart> */
    public function stringInputParts(): array;
}
