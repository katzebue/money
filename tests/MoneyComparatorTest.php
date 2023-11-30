<?php

declare(strict_types=1);

namespace Katzebue\Money\Tests;

use Iterator;
use Katzebue\Money\Context\AutoContext;
use Katzebue\Money\Exception\CurrencyConversionException;
use Katzebue\Money\ExchangeRateProvider\ConfigurableProvider;
use Katzebue\Money\Money;
use Katzebue\Money\MoneyComparator;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for class MoneyComparator.
 */
final class MoneyComparatorTest extends AbstractTestCase
{
    private function getExchangeRateProvider(): ConfigurableProvider
    {
        $provider = new ConfigurableProvider();

        $provider->setExchangeRate('EUR', 'USD', 1.1);
        $provider->setExchangeRate('USD', 'EUR', 0.9);

        $provider->setExchangeRate('USD', 'BSD', 1);
        $provider->setExchangeRate('BSD', 'USD', 1);

        $provider->setExchangeRate('EUR', 'GBP', 0.8);
        $provider->setExchangeRate('GBP', 'EUR', 1.2);

        return $provider;
    }

    /**
     *
     * @param array      $a   The money to compare.
     * @param array      $b   The money to compare to.
     * @param int|string $cmp The expected comparison value, or an exception class.
     */
    #[DataProvider('providerCompare')]
    public function testCompare(array $a, array $b, int|string $cmp): void
    {
        $comparator = new MoneyComparator($this->getExchangeRateProvider());

        $a = Money::of(...$a);
        $b = Money::of(...$b);

        if ($this->isExceptionClass($cmp)) {
            $this->expectException($cmp);
        }

        self::assertSame($cmp, $comparator->compare($a, $b));
        self::assertSame($cmp < 0, $comparator->isLess($a, $b));
        self::assertSame($cmp > 0, $comparator->isGreater($a, $b));
        self::assertSame($cmp <= 0, $comparator->isLessOrEqual($a, $b));
        self::assertSame($cmp >= 0, $comparator->isGreaterOrEqual($a, $b));
        self::assertSame($cmp === 0, $comparator->isEqual($a, $b));
    }

    public static function providerCompare(): Iterator
    {
        yield [['1.00', 'EUR'], ['1', 'EUR'], 0];
        yield [['1.00', 'EUR'], ['1.09', 'USD'], 1];
        yield [['1.00', 'EUR'], ['1.10', 'USD'], 0];
        yield [['1.00', 'EUR'], ['1.11', 'USD'], -1];
        yield [['1.11', 'USD'], ['1.00', 'EUR'], -1];
        yield [['1.12', 'USD'], ['1.00', 'EUR'], 1];
        yield [['123.57', 'USD'], ['123.57', 'BSD'], 0];
        yield [['123.57', 'BSD'], ['123.57', 'USD'], 0];
        yield [['1000250.123456', 'EUR', new AutoContext()], ['800200.0987648', 'GBP', new AutoContext()], 0];
        yield [['1000250.123456', 'EUR', new AutoContext()], ['800200.098764', 'GBP', new AutoContext()], 1];
        yield [['1000250.123456', 'EUR', new AutoContext()], ['800200.098765', 'GBP', new AutoContext()], -1];
        yield [['800200.098764', 'GBP', new AutoContext()], ['1000250.123456', 'EUR', new AutoContext()], -1];
        yield [['800200.098764', 'GBP', new AutoContext()], ['960240.1185168000', 'EUR', new AutoContext()], 0];
        yield [['800200.098764', 'GBP', new AutoContext()], ['960240.118516', 'EUR', new AutoContext()], 1];
        yield [['800200.098764', 'GBP', new AutoContext()], ['960240.118517', 'EUR', new AutoContext()], -1];
        yield [['1.0', 'EUR'], ['1.0', 'BSD'], CurrencyConversionException::class];
    }

    /**
     *
     * @param array  $monies      The monies to compare.
     * @param string $expectedMin The expected minimum money, or an exception class.
     */
    #[DataProvider('providerMin')]
    public function testMin(array $monies, string $expectedMin): void
    {
        $comparator = new MoneyComparator($this->getExchangeRateProvider());

        $monies = array_map(
            fn (array $money) => Money::of(...$money),
            $monies,
        );

        if ($this->isExceptionClass($expectedMin)) {
            $this->expectException($expectedMin);
        }

        $actualMin = $comparator->min(...$monies);

        if (!$this->isExceptionClass($expectedMin)) {
            $this->assertMoneyIs($expectedMin, $actualMin);
        }
    }

    public static function providerMin(): Iterator
    {
        yield [[['1.00', 'EUR'], ['1.09', 'USD']], 'USD 1.09'];
        yield [[['1.00', 'EUR'], ['1.10', 'USD']], 'EUR 1.00'];
        yield [[['1.00', 'EUR'], ['1.11', 'USD']], 'EUR 1.00'];
        yield [[['1.00', 'EUR'], ['1.09', 'USD'], ['1.20', 'BSD']], 'USD 1.09'];
        yield [[['1.00', 'EUR'], ['1.12', 'USD'], ['1.20', 'BSD']], CurrencyConversionException::class];
        yield [[['1.05', 'EUR'], ['1.00', 'GBP'], ['1.19', 'EUR']], 'EUR 1.05'];
    }

    /**
     *
     * @param array  $monies      The monies to compare.
     * @param string $expectedMin The expected maximum money, or an exception class.
     */
    #[DataProvider('providerMax')]
    public function testMax(array $monies, string $expectedMin): void
    {
        $comparator = new MoneyComparator($this->getExchangeRateProvider());

        $monies = array_map(
            fn (array $money) => Money::of(...$money),
            $monies,
        );

        if ($this->isExceptionClass($expectedMin)) {
            $this->expectException($expectedMin);
        }

        $actualMin = $comparator->max(...$monies);

        if (!$this->isExceptionClass($expectedMin)) {
            $this->assertMoneyIs($expectedMin, $actualMin);
        }
    }

    public static function providerMax(): Iterator
    {
        yield [[['1.00', 'EUR'], ['1.09', 'USD']], 'EUR 1.00'];
        yield [[['1.00', 'EUR'], ['1.10', 'USD']], 'EUR 1.00'];
        yield [[['1.00', 'EUR'], ['1.11', 'USD']], 'USD 1.11'];
        yield [[['1.00', 'EUR'], ['1.09', 'USD'], ['1.20', 'BSD']], CurrencyConversionException::class];
        yield [[['1.00', 'EUR'], ['1.22', 'USD'], ['1.20', 'BSD']], 'USD 1.22'];
        yield [[['1.00', 'EUR'], ['1.12', 'USD'], ['1.20', 'BSD']], 'BSD 1.20'];
        yield [[['1.05', 'EUR'], ['1.00', 'GBP'], ['1.19', 'EUR']], 'GBP 1.00'];
        yield [[['1.05', 'EUR'], ['1.00', 'GBP'], ['1.2001', 'EUR', new AutoContext()]], 'EUR 1.2001'];
    }
}
