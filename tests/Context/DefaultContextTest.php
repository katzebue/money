<?php

declare(strict_types=1);

namespace Katzebue\Money\Tests\Context;

use Brick\Math\BigNumber;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Iterator;
use Katzebue\Money\Context\DefaultContext;
use Katzebue\Money\Currency;
use Katzebue\Money\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for class DefaultContext.
 */
final class DefaultContextTest extends AbstractTestCase
{
    #[DataProvider('providerApplyTo')]
    public function testApplyTo(string $amount, string $currency, RoundingMode $roundingMode, string $expected): void
    {
        $amount = BigNumber::of($amount);
        $currency = Currency::of($currency);

        $context = new DefaultContext();

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
        yield ['1', 'USD', RoundingMode::UNNECESSARY, '1.00'];
        yield ['1.001', 'USD', RoundingMode::UNNECESSARY, RoundingNecessaryException::class];
        yield ['1.001', 'USD', RoundingMode::DOWN, '1.00'];
        yield ['1.001', 'USD', RoundingMode::UP, '1.01'];
        yield ['1', 'JPY', RoundingMode::UNNECESSARY, '1'];
        yield ['1.00', 'JPY', RoundingMode::UNNECESSARY, '1'];
        yield ['1.01', 'JPY', RoundingMode::UNNECESSARY, RoundingNecessaryException::class];
        yield ['1.01', 'JPY', RoundingMode::DOWN, '1'];
        yield ['1.01', 'JPY', RoundingMode::UP, '2'];
    }

    public function testGetStep(): void
    {
        $context = new DefaultContext();
        self::assertSame(1, $context->getStep());
    }
}
