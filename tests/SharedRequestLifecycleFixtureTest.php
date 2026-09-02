<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Tests;

use PHPUnit\Framework\TestCase;
use Sifrious\HarnessContractFixtures\Fixture;

final class SharedRequestLifecycleFixtureTest extends TestCase
{
    public function test_elwin_boundary_uses_the_shared_request_lifecycle_fixture(): void
    {
        $fixture = Fixture::load('request-lifecycle-v1');

        self::assertTrue($fixture['user_input']['immutable']);
        self::assertSame($fixture['user_input']['id'], $fixture['intent']['source_input_id']);
        self::assertContains($fixture['user_input']['id'], $fixture['conversation']['input_ids']);
        self::assertContains($fixture['intent']['id'], $fixture['conversation']['intent_ids']);
    }
}
