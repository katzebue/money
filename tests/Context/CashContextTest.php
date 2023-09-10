<?php

declare(strict_types=1);

namespace Brick\Money\Tests\Context;

use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Context\CashContext;
use Brick\Money\Currency;
use Brick\Money\Tests\AbstractTestCase;

use Brick\Math\BigNumber;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for class CashContext.
 */
final class CashContextTest extends AbstractTestCase
{
    #[DataProvider('providerApplyTo')]
    public function testApplyTo(int $step, string $amount, string $currency, RoundingMode $roundingMode, string $expected) : void
    {
        $amount = BigNumber::of($amount);
        $currency = Currency::of($currency);

        $context = new CashContext($step);

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $context->applyTo($amount, $currency, $roundingMode);

        if (! $this->isExceptionClass($expected)) {
            $this->assertBigDecimalIs($expected, $actual);
        }
    }

    public static function providerApplyTo(): Iterator
    {
        yield [1, '1', 'USD', RoundingMode::UNNECESSARY, '1.00'];
        yield [1, '1.001', 'USD', RoundingMode::UNNECESSARY, RoundingNecessaryException::class];
        yield [1, '1.001', 'USD', RoundingMode::DOWN, '1.00'];
        yield [1, '1.001', 'USD', RoundingMode::UP, '1.01'];
        yield [1, '1', 'JPY', RoundingMode::UNNECESSARY, '1'];
        yield [1, '1.00', 'JPY', RoundingMode::UNNECESSARY, '1'];
        yield [1, '1.01', 'JPY', RoundingMode::UNNECESSARY, RoundingNecessaryException::class];
        yield [1, '1.01', 'JPY', RoundingMode::DOWN, '1'];
        yield [1, '1.01', 'JPY', RoundingMode::UP, '2'];
        yield [5, '1', 'CHF', RoundingMode::UNNECESSARY, '1.00'];
        yield [5, '1.05', 'CHF', RoundingMode::UNNECESSARY, '1.05'];
        yield [5, '1.07', 'CHF', RoundingMode::UNNECESSARY, RoundingNecessaryException::class];
        yield [5, '1.07', 'CHF', RoundingMode::DOWN, '1.05'];
        yield [5, '1.07', 'CHF', RoundingMode::UP, '1.10'];
        yield [5, '1.075', 'CHF', RoundingMode::HALF_DOWN, '1.05'];
        yield [5, '1.075', 'CHF', RoundingMode::HALF_UP, '1.10'];
        yield [100, '-1', 'CZK', RoundingMode::UNNECESSARY, '-1.00'];
        yield [100, '-1.00', 'CZK', RoundingMode::UNNECESSARY, '-1.00'];
        yield [100, '-1.5', 'CZK', RoundingMode::UNNECESSARY, RoundingNecessaryException::class];
        yield [100, '-1.5', 'CZK', RoundingMode::DOWN, '-1.00'];
        yield [100, '-1.5', 'CZK', RoundingMode::UP, '-2.00'];
    }

    public function testGetStep() : void
    {
        $context = new CashContext(5);
        self::assertSame(5, $context->getStep());
    }
}
