<?php
declare(strict_types=1);
namespace Sifrious\Elwin\Tests;
use PHPUnit\Framework\TestCase;
use Sifrious\Elwin\Conversation;
use Sifrious\Elwin\InferredIntent;
use Sifrious\Elwin\NamedInputChannel;
use Sifrious\Elwin\PrimaryAskUserInput;
use Sifrious\Elwin\StringInputPart;
final class LifecycleContractTest extends TestCase
{
    public function test_raw_input_remains_separate_from_its_interpretation(): void
    {
        $input = new PrimaryAskUserInput('input:1', 'submission:1', 'user:1', 'user:1', new NamedInputChannel('burdgen'), [new StringInputPart('part:1', 0, 'Maybe auth should be global.')], '2026-08-29T12:00:00Z');
        $intent = new InferredIntent('intent:1:v1', 'intent:1', $input->id, 'Explore global authentication.', [], 'Scope is unresolved.', 1, 'fixture:deterministic');
        $conversation = new Conversation('conversation:1', [$input->id], [$intent->id], 'claude:session:external');
        self::assertSame('Maybe auth should be global.', $input->stringInputParts()[0]->exactText);
        self::assertSame($input->id, $intent->sourceInputId);
        self::assertSame('claude:session:external', $conversation->providerSessionReference);
    }
}
