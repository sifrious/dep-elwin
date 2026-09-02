<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

interface InputChannel
{
    public function identity(): string;
}
