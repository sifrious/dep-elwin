<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

interface HasAttachmentInput
{
    /** @return list<AttachmentInputPart> */
    public function attachmentInputParts(): array;
}
