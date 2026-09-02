<?php

declare(strict_types=1);

namespace Sifrious\Elwin\Tests;

use PHPUnit\Framework\TestCase;
use Sifrious\Elwin\Reference;
use Sifrious\ReferenceContract\CrossPackageReference;

final class PortableReferenceCompatibilityTest extends TestCase
{
    public function test_legacy_elwin_reference_serializes_as_the_shared_v1_contract(): void
    {
        $provenance = new CrossPackageReference('sifrious/burdgeon', 'conversation-message', 'message_01', '1');
        $legacy = new Reference('sifrious/elwin', 'user-input', 'input_01', '1', $provenance);

        $expected = new CrossPackageReference('sifrious/elwin', 'user-input', 'input_01', '1', $provenance);
        self::assertSame($expected->toArray(), $legacy->toArray());
        self::assertSame(
            $expected->toArray(),
            json_decode(json_encode($legacy, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR),
        );
        self::assertTrue($legacy->toPortable()->equals($expected));
    }
}
