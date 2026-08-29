<?php
declare(strict_types=1);
namespace Sifrious\Elwin\Tests;
use PHPUnit\Framework\TestCase;
use Sifrious\Elwin\Conversation;
use Sifrious\Elwin\Intent;
use Sifrious\Elwin\UserInput;
final class LifecycleContractTest extends TestCase
{
    public function test_raw_input_remains_separate_from_its_interpretation(): void
    {
        $input = new UserInput('input:1', 'user:1', 'Maybe auth should be global.', [], 'burdgeon', '2026-08-29T12:00:00Z');
        $intent = new Intent('intent:1:v1', $input->id, 'Explore global authentication.', [], 'Scope is unresolved.', 1);
        $conversation = new Conversation('conversation:1', [$input->id], [$intent->id], 'claude:session:external');
        self::assertSame('Maybe auth should be global.', $input->exactText);
        self::assertSame($input->id, $intent->sourceInputId);
        self::assertSame('claude:session:external', $conversation->providerSessionReference);
    }
}
