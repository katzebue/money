<?php

declare(strict_types=1);

namespace Katzebue\Money\Tests;

use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Iterator;
use Katzebue\Money\Context;
use Katzebue\Money\Context\AutoContext;
use Katzebue\Money\Context\CustomContext;
use Katzebue\Money\Context\DefaultContext;
use Katzebue\Money\CurrencyConverter;
use Katzebue\Money\Exception\CurrencyConversionException;
use Katzebue\Money\ExchangeRateProvider\ConfigurableProvider;
use Katzebue\Money\Money;
use Katzebue\Money\MoneyBag;
use Katzebue\Money\RationalMoney;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for class CurrencyConverter.
 */
final class CurrencyConverterTest extends AbstractTestCase
{
    private function createCurrencyConverter(): CurrencyConverter
    {
        $exchangeRateProvider = new ConfigurableProvider();
        $exchangeRateProvider->setExchangeRate('EUR', 'USD', '1.1');
        $exchangeRateProvider->setExchangeRate('USD', 'EUR', '10/11');
        $exchangeRateProvider->setExchangeRate('BSD', 'USD', 1);

        return new CurrencyConverter($exchangeRateProvider);
    }

    /**
     *
     * @param array        $money          The base money.
     * @param string       $toCurrency     The currency code to convert to.
     * @param RoundingMode $roundingMode   The rounding mode to use.
     * @param string       $expectedResult The expected money's string representation, or an exception class name.
     */
    #[DataProvider('providerConvertMoney')]
    public function testConvertMoney(array $money, string $toCurrency, RoundingMode $roundingMode, string $expectedResult): void
    {
        $money = Money::of(...$money);
        $currencyConverter = $this->createCurrencyConverter();

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualResult = $currencyConverter->convert($money, $toCurrency, null, $roundingMode);

        if (! $this->isExceptionClass($expectedResult)) {
            $this->assertMoneyIs($expectedResult, $actualResult);
        }
    }

    public static function providerConvertMoney(): Iterator
    {
        yield [['1.23', 'EUR'], 'USD', RoundingMode::DOWN, 'USD 1.35'];
        yield [['1.23', 'EUR'], 'USD', RoundingMode::UP, 'USD 1.36'];
        yield [['1.10', 'EUR'], 'USD', RoundingMode::DOWN, 'USD 1.21'];
        yield [['1.10', 'EUR'], 'USD', RoundingMode::UP, 'USD 1.21'];
        yield [['123.57', 'USD'], 'EUR', RoundingMode::DOWN, 'EUR 112.33'];
        yield [['123.57', 'USD'], 'EUR', RoundingMode::UP, 'EUR 112.34'];
        yield [['123.57', 'USD'], 'EUR', RoundingMode::UNNECESSARY, RoundingNecessaryException::class];
        yield [['1724657496.87', 'USD', new AutoContext()], 'EUR', RoundingMode::UNNECESSARY, 'EUR 1567870451.70'];
        yield [['127.367429', 'BSD', new AutoContext()], 'USD', RoundingMode::UP, 'USD 127.37'];
        yield [['1.23', 'USD'], 'BSD', RoundingMode::DOWN, CurrencyConversionException::class];
        yield [['1.23', 'EUR'], 'EUR', RoundingMode::UNNECESSARY, 'EUR 1.23'];
        yield [['123456.789', 'JPY', new AutoContext()], 'JPY', RoundingMode::HALF_EVEN, 'JPY 123457'];
    }

    /**
     *
     * @param array        $monies       The mixed currency monies to add.
     * @param string       $currency     The target currency code.
     * @param Context      $context      The target context.
     * @param RoundingMode $roundingMode The rounding mode to use.
     * @param string       $total        The expected total.
     */
    #[DataProvider('providerConvertMoneyBag')]
    public function testConvertMoneyBag(array $monies, string $currency, Context $context, RoundingMode $roundingMode, string $total): void
    {
        $exchangeRateProvider = new ConfigurableProvider();
        $exchangeRateProvider->setExchangeRate('EUR', 'USD', '1.23456789');
        $exchangeRateProvider->setExchangeRate('JPY', 'USD', '0.00987654321');

        $moneyBag = new MoneyBag();

        foreach ($monies as [$amount, $currencyCode]) {
            $money = Money::of($amount, $currencyCode, new AutoContext());
            $moneyBag->add($money);
        }

        $currencyConverter = new CurrencyConverter($exchangeRateProvider);
        $this->assertMoneyIs($total, $currencyConverter->convert($moneyBag, $currency, $context, $roundingMode));
    }

    public static function providerConvertMoneyBag(): Iterator
    {
        yield [[['354.40005', 'EUR'], ['3.1234', 'JPY']], 'USD', new DefaultContext(), RoundingMode::DOWN, 'USD 437.56'];
        yield [[['354.40005', 'EUR'], ['3.1234', 'JPY']], 'USD', new DefaultContext(), RoundingMode::UP, 'USD 437.57'];
        yield [[['1234.56', 'EUR'], ['31562', 'JPY']], 'USD', new CustomContext(6), RoundingMode::DOWN, 'USD 1835.871591'];
        yield [[['1234.56', 'EUR'], ['31562', 'JPY']], 'USD', new CustomContext(6), RoundingMode::UP, 'USD 1835.871592'];
    }

    /**
     *
     * @param array  $monies        The mixed monies to add.
     * @param string $currency      The target currency code.
     * @param string $expectedTotal The expected total.
     */
    #[DataProvider('providerConvertMoneyBagToRational')]
    public function testConvertMoneyBagToRational(array $monies, string $currency, string $expectedTotal): void
    {
        $exchangeRateProvider = new ConfigurableProvider();
        $exchangeRateProvider->setExchangeRate('EUR', 'USD', '1.123456789');
        $exchangeRateProvider->setExchangeRate('JPY', 'USD', '0.0098765432123456789');

        $moneyBag = new MoneyBag();

        foreach ($monies as [$amount, $currencyCode]) {
            $money = Money::of($amount, $currencyCode, new AutoContext());
            $moneyBag->add($money);
        }

        $currencyConverter = new CurrencyConverter($exchangeRateProvider);
        $actualTotal = $currencyConverter->convertToRational($moneyBag, $currency)->simplified();

        $this->assertRationalMoneyEquals($expectedTotal, $actualTotal);
    }

    public static function providerConvertMoneyBagToRational(): Iterator
    {
        yield [[['354.40005', 'EUR'], ['3.1234', 'JPY']], 'USD', 'USD 19909199529475444524673813/50000000000000000000000'];
        yield [[['1234.56', 'EUR'], ['31562', 'JPY']], 'USD', 'USD 8493491351479471587209/5000000000000000000'];
    }

    /**
     *
     * @param array        $money          The original amount and currency.
     * @param string       $toCurrency     The currency code to convert to.
     * @param RoundingMode $roundingMode   The rounding mode to use.
     * @param string       $expectedResult The expected money's string representation, or an exception class name.
     */
    #[DataProvider('providerConvertRationalMoney')]
    public function testConvertRationalMoney(array $money, string $toCurrency, RoundingMode $roundingMode, string $expectedResult): void
    {
        $currencyConverter = $this->createCurrencyConverter();

        $rationalMoney = RationalMoney::of(...$money);

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualResult = $currencyConverter->convert($rationalMoney, $toCurrency, null, $roundingMode);

        if (! $this->isExceptionClass($expectedResult)) {
            $this->assertMoneyIs($expectedResult, $actualResult);
        }
    }

    public static function providerConvertRationalMoney(): Iterator
    {
        yield [['7/9', 'USD'], 'EUR', RoundingMode::DOWN, 'EUR 0.70'];
        yield [['7/9', 'USD'], 'EUR', RoundingMode::UP, 'EUR 0.71'];
        yield [['4/3', 'EUR'], 'USD', RoundingMode::DOWN, 'USD 1.46'];
        yield [['4/3', 'EUR'], 'USD', RoundingMode::UP, 'USD 1.47'];
    }
}
