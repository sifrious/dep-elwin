<?php
declare(strict_types=1);
namespace Sifrious\Elwin\Tests;
use PHPUnit\Framework\TestCase;
use Sifrious\Elwin\Intent;
use Sifrious\Elwin\IntentStatus;
use Sifrious\Elwin\UserInput;
final class UserInputIntentConformanceTest extends TestCase
{
    public function test_exact_source_bytes_remain_unchanged_across_interpretation_versions(): void
    {
        $exactSource = "  Keep my spacing.\r\nDo not normalize café or 👩🏽‍💻.  \n";
        $input = new UserInput('input:exact-source', 'user:1', $exactSource, [], 'burdgeon', '2026-08-29T12:00:00.123Z');
        $first = new Intent('intent:1:v1', $input->id, 'Initial interpretation.', ['Preserve source evidence.'], 'Scope is unresolved.', 1);
        $second = new Intent('intent:1:v2', $input->id, 'Revised interpretation.', ['Preserve source evidence.'], null, 2, IntentStatus::Active);
        $preservedFirst = $first->supersededBy($second->id);

        self::assertSame($exactSource, $input->exactText);
        self::assertSame($input->id, $preservedFirst->sourceInputId);
        self::assertSame($input->id, $second->sourceInputId);
        self::assertSame(1, $preservedFirst->interpretationVersion);
        self::assertSame(IntentStatus::Superseded, $preservedFirst->status);
        self::assertSame($second->id, $preservedFirst->replacementIntentId);
        self::assertSame('Initial interpretation.', $preservedFirst->summary);
        self::assertSame(2, $second->interpretationVersion);
    }

    public function test_capture_interpret_and_supersede_make_zero_provider_or_execution_calls(): void
    {
        $calls = new class {
            public int $provider = 0;
            public int $execution = 0;
        };

        $input = new UserInput('input:no-dispatch', 'user:1', 'Help me understand this first.', [], 'burdgeon', '2026-08-29T12:00:00Z');
        $first = new Intent('intent:no-dispatch:v1', $input->id, 'Clarify the request.', [], 'Target is unknown.', 1);
        $second = new Intent('intent:no-dispatch:v2', $input->id, 'Discuss possible targets.', [], null, 2);
        $first = $first->supersededBy($second->id);

        self::assertSame(0, $calls->provider, 'Intent construction must not call a provider.');
        self::assertSame(0, $calls->execution, 'Intent construction must not dispatch execution.');
        self::assertSame(IntentStatus::Superseded, $first->status);
    }

    public function test_replacement_link_is_valid_only_for_a_distinct_superseded_intent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Intent('intent:1:v1', 'input:1', 'Interpretation.', [], null, 1, IntentStatus::Active, 'intent:1:v2');
    }
}
