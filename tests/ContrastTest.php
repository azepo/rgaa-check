<?php

declare(strict_types=1);

namespace Azepo\RgaaCheck\Tests;

use Azepo\RgaaCheck\Contrast;
use PHPUnit\Framework\TestCase;

final class ContrastTest extends TestCase
{
    public function testTheRatioFollowsTheWcagFormula(): void
    {
        self::assertEqualsWithDelta(21.0, Contrast::ratio('black', 'white'), 0.001);
        self::assertEqualsWithDelta(1.0, Contrast::ratio('#fff', '#ffffff'), 0.001);
        self::assertEqualsWithDelta(7.66, Contrast::ratio('#0051A8', 'white'), 0.01);
        self::assertEqualsWithDelta(6.48, Contrast::ratio('#C00000', 'white'), 0.01);
    }

    public function testColorsAreNormalised(): void
    {
        self::assertSame('#111d1d', Contrast::hex('rgb(17, 29, 29)'));
        self::assertSame('#aabbcc', Contrast::hex(' #ABC '));
        self::assertSame('#f5f5f5', Contrast::hex('WhiteSmoke'));
    }

    public function testThresholds(): void
    {
        // #0593ff sur blanc = 3,17:1 : suffisant pour un grand texte, pas pour un texte courant.
        self::assertFalse(Contrast::isEnough('#0593ff', '#ffffff'));
        self::assertTrue(Contrast::isEnough('#0593ff', '#ffffff', Contrast::LARGE_TEXT));
        self::assertTrue(Contrast::isEnough('#0051a8', '#ffffff'));
    }

    public function testAnUnknownColorIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Contrast::hex('bleu canard');
    }
}
