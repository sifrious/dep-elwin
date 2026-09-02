<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Disposition;

use DateTimeImmutable;
use InvalidArgumentException;
use Sifrious\ReferenceContract\CrossPackageReference;

final readonly class IntentDisposition
{
    public const string DEFINITION_VERSION = 'intent-disposition/v1';

    /**
     * @param list<CrossPackageReference> $understanding
     * @param list<UnresolvedRequirement> $unresolved
     * @param list<NextTransition> $allowedTransitions
     * @param list<NextTransition> $suggestedTransitions
     * @param list<CrossPackageReference> $evidence
     */
    public function __construct(
        public string $id,
        public CrossPackageReference $intent,
        public CrossPackageReference $conversation,
        public DateTimeImmutable $evaluatedAt,
        public string $definitionVersion,
        public array $understanding,
        public array $unresolved,
        public array $allowedTransitions,
        public array $suggestedTransitions,
        public ExecutionReadiness $readiness,
        public array $evidence,
        public bool $requiresExplicitUserSelection,
        public ?string $blockedReason = null,
    ) {
        if (trim($id) === '' || trim($definitionVersion) === '' || $allowedTransitions === []) {
            throw new InvalidArgumentException('A disposition requires identity, definition version, and allowed transitions.');
        }
        foreach ($suggestedTransitions as $transition) {
            if (! in_array($transition, $allowedTransitions, true)) {
                throw new InvalidArgumentException('Suggested transitions must be allowed transitions.');
            }
        }
        if ($readiness === ExecutionReadiness::Blocked && trim((string) $blockedReason) === '') {
            throw new InvalidArgumentException('A blocked disposition requires a machine-readable reason.');
        }
    }

    /** Readiness is advisory; only a separate user-attributed selection can authorize execution. */
    public function authorizesExecution(): bool
    {
        return false;
    }

    public function allows(NextTransition $transition): bool
    {
        return in_array($transition, $this->allowedTransitions, true);
    }
}
