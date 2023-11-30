<?php

declare(strict_types=1);

namespace Katzebue\Money\Tests\Context;

use Brick\Math\BigNumber;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use InvalidArgumentException;
use Iterator;
use Katzebue\Money\Context\AutoContext;
use Katzebue\Money\Context\CashContext;
use Katzebue\Money\Currency;
use Katzebue\Money\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for class AutoContext.
 */
final class AutoContextTest extends AbstractTestCase
{
    #[DataProvider('providerApplyTo')]
    public function testApplyTo(string $amount, string $currency, RoundingMode $roundingMode, string $expected): void
    {
        $amount = BigNumber::of($amount);
        $currency = Currency::of($currency);

        $context = new AutoContext();

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $context->applyTo($amount, $currency, $roundingMode);

        if (!$this->isExceptionClass($expected)) {
            $this->assertBigDecimalIs($expected, $actual);
        }
    }

    public static function providerApplyTo(): Iterator
    {
        yield ['1', 'USD', RoundingMode::UNNECESSARY, '1'];
        yield ['1.23', 'JPY', RoundingMode::UNNECESSARY, '1.23'];
        yield ['123/5000', 'EUR', RoundingMode::UNNECESSARY, '0.0246'];
        yield ['5/7', 'EUR', RoundingMode::UNNECESSARY, RoundingNecessaryException::class];
        yield ['5/7', 'EUR', RoundingMode::DOWN, InvalidArgumentException::class];
    }

    public function testGetStep(): void
    {
        $context = new CashContext(5);
        self::assertSame(5, $context->getStep());
    }
}
