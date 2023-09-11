<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Money\ExchangeRateProvider;
use Brick\Money\ExchangeRateProvider\BaseCurrencyProvider;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\Tests\AbstractTestCase;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for class BaseCurrencyProvider.
 */
final class BaseCurrencyProviderTest extends AbstractTestCase
{
    private function getExchangeRateProvider(): ExchangeRateProvider
    {
        $provider = new ConfigurableProvider();

        $provider->setExchangeRate('USD', 'EUR', 0.9);
        $provider->setExchangeRate('USD', 'GBP', 0.8);
        $provider->setExchangeRate('USD', 'CAD', 1.1);

        return new BaseCurrencyProvider($provider, 'USD');
    }

    /**
     *
     * @param string $sourceCurrencyCode The code of the source currency.
     * @param string $targetCurrencyCode The code of the target currency.
     * @param string $exchangeRate       The expected exchange rate, rounded DOWN to 6 decimals.
     */
    #[DataProvider('providerGetExchangeRate')]
    public function testGetExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode, string $exchangeRate): void
    {
        $rate = $this->getExchangeRateProvider()->getExchangeRate($sourceCurrencyCode, $targetCurrencyCode);
        self::assertSame($exchangeRate, (string) $rate->toScale(6, RoundingMode::DOWN));
    }

    public static function providerGetExchangeRate(): Iterator
    {
        yield ['USD', 'EUR', '0.900000'];
        yield ['USD', 'GBP', '0.800000'];
        yield ['USD', 'CAD', '1.100000'];
        yield ['EUR', 'USD', '1.111111'];
        yield ['GBP', 'USD', '1.250000'];
        yield ['CAD', 'USD', '0.909090'];
        yield ['EUR', 'GBP', '0.888888'];
        yield ['EUR', 'CAD', '1.222222'];
        yield ['GBP', 'EUR', '1.125000'];
        yield ['GBP', 'CAD', '1.375000'];
        yield ['CAD', 'EUR', '0.818181'];
        yield ['CAD', 'GBP', '0.727272'];
    }

    #[DataProvider('providerReturnBigNumber')]
    public function testReturnBigNumber(BigNumber|float|int|string $rate): void
    {
        $configurableProvider = new ConfigurableProvider();
        $configurableProvider->setExchangeRate('USD', 'EUR', $rate);
        $baseProvider = new BaseCurrencyProvider($configurableProvider, 'USD');

        $rate = $baseProvider->getExchangeRate('USD', 'EUR');

        $this->assertInstanceOf(BigNumber::class, $rate);
    }

    public static function providerReturnBigNumber(): Iterator
    {
        yield [1];
        yield [1.1];
        yield ['1.0'];
        yield [BigNumber::of('1')];
    }
}
