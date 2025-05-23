<?php

declare(strict_types=1);

namespace Katzebue\Money\Tests\ExchangeRateProvider;

use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use Iterator;
use Katzebue\Money\Exception\CurrencyConversionException;
use Katzebue\Money\ExchangeRateProvider;
use Katzebue\Money\ExchangeRateProvider\ConfigurableProvider;
use Katzebue\Money\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for class ConfigurableProvider.
 */
final class ConfigurableProviderTest extends AbstractTestCase
{
    private function getExchangeRateProvider(): ExchangeRateProvider
    {
        $provider = new ConfigurableProvider();

        $provider->setExchangeRate('USD', 'EUR', 0.8);
        $provider->setExchangeRate('USD', 'GBP', 0.6);
        $provider->setExchangeRate('USD', 'CAD', 1.2);

        return $provider;
    }

    /**
     *
     * @param string $sourceCurrencyCode The code of the source currency.
     * @param string $targetCurrencyCode The code of the target currency.
     * @param string $exchangeRate       The expected exchange rate, rounded DOWN to 3 decimals.
     */
    #[DataProvider('providerGetExchangeRate')]
    public function testGetExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode, string $exchangeRate): void
    {
        $rate = $this->getExchangeRateProvider()->getExchangeRate($sourceCurrencyCode, $targetCurrencyCode);
        self::assertSame($exchangeRate, (string) BigRational::of($rate)->toScale(3, RoundingMode::DOWN));
    }

    public static function providerGetExchangeRate(): Iterator
    {
        yield ['USD', 'EUR', '0.800'];
        yield ['USD', 'GBP', '0.600'];
        yield ['USD', 'CAD', '1.200'];
    }

    public function testUnknownCurrencyPair(): void
    {
        try {
            $this->getExchangeRateProvider()->getExchangeRate('EUR', 'USD');
        } catch (CurrencyConversionException $e) {
            self::assertSame('EUR', $e->getSourceCurrencyCode());
            self::assertSame('USD', $e->getTargetCurrencyCode());

            return;
        }

        self::fail('Expected CurrencyConversionException');
    }
}
