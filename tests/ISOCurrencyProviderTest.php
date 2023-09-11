<?php

declare(strict_types=1);

namespace Brick\Money\Tests;

use Brick\Money\Currency;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\ISOCurrencyProvider;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;

/**
 * Tests for class ISOCurrencyProvider.
 */
final class ISOCurrencyProviderTest extends AbstractTestCase
{
    /**
     * Resets the singleton instance before running the tests.
     *
     * This is necessary for code coverage to "see" the actual instantiation happen, as it may happen indirectly from
     * another class internally resolving an ISO currency code using ISOCurrencyProvider, and this can originate from
     * code outside test methods (for example in data providers).
     */
    public static function setUpBeforeClass(): void
    {
        $reflection = new ReflectionProperty(ISOCurrencyProvider::class, 'instance');
        $reflection->setAccessible(true);
        $reflection->setValue(null);
    }

    #[DataProvider('providerGetCurrency')]
    public function testGetCurrency(string $currencyCode, int $numericCode, string $name, int $defaultFractionDigits): void
    {
        $provider = ISOCurrencyProvider::getInstance();

        $currency = $provider->getCurrency($currencyCode);
        $this->assertCurrencyEquals($currencyCode, $numericCode, $name, $defaultFractionDigits, $currency);

        $currency = $provider->getCurrency($numericCode);
        $this->assertCurrencyEquals($currencyCode, $numericCode, $name, $defaultFractionDigits, $currency);
    }

    public static function providerGetCurrency(): Iterator
    {
        yield ['EUR', 978, 'Euro', 2];
        yield ['GBP', 826, 'Pound Sterling', 2];
        yield ['USD', 840, 'US Dollar', 2];
        yield ['CAD', 124, 'Canadian Dollar', 2];
        yield ['AUD', 36, 'Australian Dollar', 2];
        yield ['NZD', 554, 'New Zealand Dollar', 2];
        yield ['JPY', 392, 'Yen', 0];
        yield ['TND', 788, 'Tunisian Dinar', 3];
        yield ['DZD', 12, 'Algerian Dinar', 2];
        yield ['ALL', 8, 'Lek', 2];
    }

    #[DataProvider('providerUnknownCurrency')]
    public function testGetUnknownCurrency(string|int $currencyCode): void
    {
        $this->expectException(UnknownCurrencyException::class);
        ISOCurrencyProvider::getInstance()->getCurrency($currencyCode);
    }

    public static function providerUnknownCurrency(): Iterator
    {
        yield ['XXX'];
        yield [-1];
    }

    public function testGetAvailableCurrencies(): void
    {
        $provider = ISOCurrencyProvider::getInstance();

        $eur = $provider->getCurrency('EUR');
        $gbp = $provider->getCurrency('GBP');
        $usd = $provider->getCurrency('USD');

        $availableCurrencies = $provider->getAvailableCurrencies();

        self::assertGreaterThan(100, count($availableCurrencies));

        self::assertContainsOnlyInstancesOf(Currency::class, $availableCurrencies);

        self::assertSame($eur, $availableCurrencies['EUR']);
        self::assertSame($gbp, $availableCurrencies['GBP']);
        self::assertSame($usd, $availableCurrencies['USD']);
    }
}
