<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

use Sifrious\AuthorizationContract\AuthorizationContext;
use Sifrious\ReferenceContract\CrossPackageReference;

interface UserInputContract
{
    public function id(): string;
    public function clientSubmissionId(): string;
    public function authorizationContext(): AuthorizationContext;
    public function semanticAuthorReference(): CrossPackageReference;
    public function submittingActorReference(): CrossPackageReference;
    public function channel(): InputChannel;
    /** @return list<UserInputPart> */
    public function parts(): array;
    public function acceptedAt(): string;
}
