<?php
declare(strict_types=1);
namespace Sifrious\Elwin;
use InvalidArgumentException;
/** Immutable evidence of exactly what a person submitted. */
final readonly class UserInput
{
    /** @param list<string> $attachmentReferences */
    public function __construct(public string $id, public string $actorReference, public string $exactText, public array $attachmentReferences, public string $source, public string $submittedAt)
    {
        if (trim($id) === '' || trim($actorReference) === '' || trim($exactText) === '' || trim($source) === '') {
            throw new InvalidArgumentException('User input identity, actor, exact text, and source are required.');
        }
        if (preg_match('/^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}(?:\\.\\d+)?Z$/', $submittedAt) !== 1) {
            throw new InvalidArgumentException('User input submission time must be UTC ISO-8601.');
        }
        foreach ($attachmentReferences as $reference) {
            if (! is_string($reference) || trim($reference) === '') {
                throw new InvalidArgumentException('Attachment references must be non-empty strings.');
            }
        }
    }
}
