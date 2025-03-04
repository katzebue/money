<?php

declare(strict_types=1);

namespace Katzebue\Money\ExchangeRateProvider;

use Brick\Math\BigNumber;
use Katzebue\Money\Exception\CurrencyConversionException;
use Katzebue\Money\ExchangeRateProvider;

/**
 * A configurable exchange rate provider.
 * @see \Katzebue\Money\Tests\ExchangeRateProvider\ConfigurableProviderTest
 */
final class ConfigurableProvider implements ExchangeRateProvider
{
    /**
     * @psalm-var array<string, array<string, BigNumber|int|float|string>>
     */
    private array $exchangeRates = [];

    /**
     * @return ConfigurableProvider This instance, for chaining.
     */
    public function setExchangeRate(
        string $sourceCurrencyCode,
        string $targetCurrencyCode,
        BigNumber|int|float|string $exchangeRate
    ): self {
        $this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode] = $exchangeRate;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode): BigNumber|int|float|string
    {
        if (isset($this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode])) {
            return $this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode];
        }

        throw CurrencyConversionException::exchangeRateNotAvailable($sourceCurrencyCode, $targetCurrencyCode);
    }
}
