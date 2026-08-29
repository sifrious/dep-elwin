<?php
declare(strict_types=1);
namespace Sifrious\Elwin;
use InvalidArgumentException;
final readonly class Conversation
{
    /** @param list<string> $inputIds @param list<string> $intentIds */
    public function __construct(public string $id, public array $inputIds, public array $intentIds = [], public ?string $providerSessionReference = null)
    {
        if (trim($id) === '' || $inputIds === []) {
            throw new InvalidArgumentException('Conversation identity and at least one input are required.');
        }
    }
}
