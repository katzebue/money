<?php

declare(strict_types=1);

namespace Katzebue\Money\Tests\Context;

use Brick\Math\BigNumber;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Iterator;
use Katzebue\Money\Context\CustomContext;
use Katzebue\Money\Currency;
use Katzebue\Money\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for class CustomContext.
 */
final class CustomContextTest extends AbstractTestCase
{
    #[DataProvider('providerApplyTo')]
    public function testApplyTo(int $scale, int $step, string $amount, string $currency, RoundingMode $roundingMode, string $expected): void
    {
        $amount = BigNumber::of($amount);
        $currency = Currency::of($currency);

        $context = new CustomContext($scale, $step);

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
        yield [2, 1, '1', 'USD', RoundingMode::UNNECESSARY, '1.00'];
        yield [2, 1, '1.001', 'USD', RoundingMode::UNNECESSARY, RoundingNecessaryException::class];
        yield [2, 1, '1.001', 'USD', RoundingMode::DOWN, '1.00'];
        yield [2, 1, '1.001', 'USD', RoundingMode::UP, '1.01'];
        yield [4, 1, '1', 'USD', RoundingMode::UNNECESSARY, '1.0000'];
        yield [4, 1, '1.0001', 'USD', RoundingMode::UNNECESSARY, '1.0001'];
        yield [4, 1, '1.00005', 'USD', RoundingMode::UNNECESSARY, RoundingNecessaryException::class];
        yield [4, 1, '1.00005', 'USD', RoundingMode::HALF_DOWN, '1.0000'];
        yield [4, 1, '1.00005', 'USD', RoundingMode::HALF_UP, '1.0001'];
        yield [0, 1, '1', 'JPY', RoundingMode::UNNECESSARY, '1'];
        yield [0, 1, '1.00', 'JPY', RoundingMode::UNNECESSARY, '1'];
        yield [0, 1, '1.01', 'JPY', RoundingMode::UNNECESSARY, RoundingNecessaryException::class];
        yield [0, 1, '1.01', 'JPY', RoundingMode::DOWN, '1'];
        yield [0, 1, '1.01', 'JPY', RoundingMode::UP, '2'];
        yield [2, 1, '1', 'JPY', RoundingMode::UNNECESSARY, '1.00'];
        yield [2, 1, '1.00', 'JPY', RoundingMode::UNNECESSARY, '1.00'];
        yield [2, 1, '1.01', 'JPY', RoundingMode::UNNECESSARY, '1.01'];
        yield [2, 1, '1.001', 'JPY', RoundingMode::UNNECESSARY, RoundingNecessaryException::class];
        yield [2, 1, '1.001', 'JPY', RoundingMode::DOWN, '1.00'];
        yield [2, 1, '1.001', 'JPY', RoundingMode::UP, '1.01'];
        yield [2, 5, '1', 'CHF', RoundingMode::UNNECESSARY, '1.00'];
        yield [2, 5, '1.05', 'CHF', RoundingMode::UNNECESSARY, '1.05'];
        yield [2, 5, '1.07', 'CHF', RoundingMode::UNNECESSARY, RoundingNecessaryException::class];
        yield [2, 5, '1.07', 'CHF', RoundingMode::DOWN, '1.05'];
        yield [2, 5, '1.07', 'CHF', RoundingMode::UP, '1.10'];
        yield [2, 5, '1.075', 'CHF', RoundingMode::HALF_DOWN, '1.05'];
        yield [2, 5, '1.075', 'CHF', RoundingMode::HALF_UP, '1.10'];
        yield [4, 5, '1', 'CHF', RoundingMode::UNNECESSARY, '1.0000'];
        yield [4, 5, '1.05', 'CHF', RoundingMode::UNNECESSARY, '1.0500'];
        yield [4, 5, '1.0005', 'CHF', RoundingMode::UNNECESSARY, '1.0005'];
        yield [4, 5, '1.0007', 'CHF', RoundingMode::DOWN, '1.0005'];
        yield [4, 5, '1.0007', 'CHF', RoundingMode::UP, '1.0010'];
        yield [2, 100, '-1', 'CZK', RoundingMode::UNNECESSARY, '-1.00'];
        yield [2, 100, '-1.00', 'CZK', RoundingMode::UNNECESSARY, '-1.00'];
        yield [2, 100, '-1.5', 'CZK', RoundingMode::UNNECESSARY, RoundingNecessaryException::class];
        yield [2, 100, '-1.5', 'CZK', RoundingMode::DOWN, '-1.00'];
        yield [2, 100, '-1.5', 'CZK', RoundingMode::UP, '-2.00'];
        yield [4, 10000, '-1', 'CZK', RoundingMode::UNNECESSARY, '-1.0000'];
        yield [4, 10000, '-1.00', 'CZK', RoundingMode::UNNECESSARY, '-1.0000'];
        yield [4, 10000, '-1.5', 'CZK', RoundingMode::UNNECESSARY, RoundingNecessaryException::class];
        yield [4, 10000, '-1.5', 'CZK', RoundingMode::DOWN, '-1.0000'];
        yield [4, 10000, '-1.5', 'CZK', RoundingMode::UP, '-2.0000'];
    }

    public function testGetScaleGetStep(): void
    {
        $context = new CustomContext(8, 50);
        self::assertSame(8, $context->getScale());
        self::assertSame(50, $context->getStep());
    }
}
