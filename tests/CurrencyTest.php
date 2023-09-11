<?php

declare(strict_types=1);

namespace Katzebue\Money\Tests;

use InvalidArgumentException;
use Katzebue\Money\Currency;
use Katzebue\Money\Exception\UnknownCurrencyException;
use PHPUnit\Framework\Attributes\DataProviderExternal;

/**
 * Unit tests for class Currency.
 */
final class CurrencyTest extends AbstractTestCase
{
    /**
     * @param string $currencyCode The currency code.
     * @param int $numericCode The currency's numeric code.
     * @param int $fractionDigits The currency's default fraction digits.
     * @param string $name The currency's name.
     */
    #[DataProviderExternal(CurrencyDataProvider::class, 'providerOf')]
    public function testOf(string $currencyCode, int $numericCode, int $fractionDigits, string $name): void
    {
        $currency = Currency::of($currencyCode);
        $this->assertCurrencyEquals($currencyCode, $numericCode, $name, $fractionDigits, $currency);

        $currency = Currency::of($numericCode);
        $this->assertCurrencyEquals($currencyCode, $numericCode, $name, $fractionDigits, $currency);
    }

    #[DataProviderExternal(CurrencyDataProvider::class, 'providerOfUnknownCurrencyCode')]
    public function testOfUnknownCurrencyCode(string|int $currencyCode): void
    {
        $this->expectException(UnknownCurrencyException::class);
        Currency::of($currencyCode);
    }

    public function testConstructor(): void
    {
        $bitCoin = new Currency('BTC', -1, 'BitCoin', 8);
        $this->assertCurrencyEquals('BTC', -1, 'BitCoin', 8, $bitCoin);
    }

    public function testOfReturnsSameInstance(): void
    {
        self::assertSame(Currency::of('EUR'), Currency::of('EUR'));
    }

    #[DataProviderExternal(CurrencyDataProvider::class, 'providerOfCountry')]
    public function testOfCountry(string $countryCode, string $expected): void
    {
        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = Currency::ofCountry($countryCode);

        if (!$this->isExceptionClass($expected)) {
            self::assertInstanceOf(Currency::class, $actual);
            self::assertSame($expected, $actual->getCurrencyCode());
        }
    }

    public function testCreateWithNegativeFractionDigits(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Currency('BTC', 0, 'BitCoin', -1);
    }

    public function testIs(): void
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

    public function testJsonSerialize(): void
    {
        $currency = Currency::of('EUR');
        $expected = 'EUR';
        self::assertSame($expected, $currency->jsonSerialize());
        self::assertJsonStringEqualsJsonString(
            json_encode($expected, JSON_THROW_ON_ERROR),
            json_encode($currency, JSON_THROW_ON_ERROR)
        );
    }
}
