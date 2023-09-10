<?php

declare(strict_types=1);

namespace Brick\Money\Tests;

use Brick\Money\Currency;
use Brick\Money\Exception\UnknownCurrencyException;
use InvalidArgumentException;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for class Currency.
 */
final class CurrencyTest extends AbstractTestCase
{
    /**
     *
     * @param string $currencyCode   The currency code.
     * @param int    $numericCode    The currency's numeric code.
     * @param int    $fractionDigits The currency's default fraction digits.
     * @param string $name           The currency's name.
     */
    #[DataProvider('providerOf')]
    public function testOf(string $currencyCode, int $numericCode, int $fractionDigits, string $name) : void
    {
        $currency = Currency::of($currencyCode);
        $this->assertCurrencyEquals($currencyCode, $numericCode, $name, $fractionDigits, $currency);

        $currency = Currency::of($numericCode);
        $this->assertCurrencyEquals($currencyCode, $numericCode, $name, $fractionDigits, $currency);
    }

    public static function providerOf(): Iterator
    {
        yield ['USD', 840, 2, 'US Dollar'];
        yield ['EUR', 978, 2, 'Euro'];
        yield ['GBP', 826, 2, 'Pound Sterling'];
        yield ['JPY', 392, 0, 'Yen'];
        yield ['DZD', 12, 2, 'Algerian Dinar'];
    }

    #[DataProvider('providerOfUnknownCurrencyCode')]
    public function testOfUnknownCurrencyCode(string|int $currencyCode) : void
    {
        $this->expectException(UnknownCurrencyException::class);
        Currency::of($currencyCode);
    }

    public static function providerOfUnknownCurrencyCode(): Iterator
    {
        yield ['XXX'];
        yield [-1];
    }

    public function testConstructor() : void
    {
        $bitCoin = new Currency('BTC', -1, 'BitCoin', 8);
        $this->assertCurrencyEquals('BTC', -1, 'BitCoin', 8, $bitCoin);
    }

    public function testOfReturnsSameInstance() : void
    {
        self::assertSame(Currency::of('EUR'), Currency::of('EUR'));
    }

    #[DataProvider('providerOfCountry')]
    public function testOfCountry(string $countryCode, string $expected) : void
    {
        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = Currency::ofCountry($countryCode);

        if (! $this->isExceptionClass($expected)) {
            self::assertInstanceOf(Currency::class, $actual);
            self::assertSame($expected, $actual->getCurrencyCode());
        }
    }

    public static function providerOfCountry(): Iterator
    {
        yield ['CA', 'CAD'];
        yield ['CH', 'CHF'];
        yield ['DE', 'EUR'];
        yield ['ES', 'EUR'];
        yield ['FR', 'EUR'];
        yield ['GB', 'GBP'];
        yield ['IT', 'EUR'];
        yield ['US', 'USD'];
        yield ['AQ', UnknownCurrencyException::class];
        // no currency
        yield ['CU', UnknownCurrencyException::class];
        // 2 currencies
        yield ['XX', UnknownCurrencyException::class];
    }

    public function testCreateWithNegativeFractionDigits() : void
    {
        $this->expectException(InvalidArgumentException::class);
        new Currency('BTC', 0, 'BitCoin', -1);
    }

    public function testIs() : void
    {
        $currency = Currency::of('EUR');

        self::assertTrue($currency->is('EUR'));
        self::assertTrue($currency->is(978));

        self::assertFalse($currency->is('USD'));
        self::assertFalse($currency->is(840));

        $clone = clone $currency;

        self::assertNotSame($currency, $clone);
        self::assertTrue($clone->is($currency));
    }
}
