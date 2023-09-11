<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

use Brick\Math\BigNumber;
use Brick\Money\ExchangeRateProvider;

/**
 * Caches the results of another exchange rate provider.
 * @see \Brick\Money\Tests\ExchangeRateProvider\CachedProviderTest
 */
final class CachedProvider implements ExchangeRateProvider
{
    /**
     * The cached exchange rates.
     *
     * @psalm-var array<string, array<string, BigNumber|int|float|string>>
     */
    private array $exchangeRates = [];

    /**
     * Class constructor.
     */
    public function __construct(
        /**
         * The underlying exchange rate provider.
         */
        private readonly ExchangeRateProvider $provider
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode): BigNumber|int|float|string
    {
        if (isset($this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode])) {
            return $this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode];
        }

        $exchangeRate = $this->provider->getExchangeRate($sourceCurrencyCode, $targetCurrencyCode);

        $this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode] = $exchangeRate;

        return $exchangeRate;
    }

    /**
     * Invalidates the cache.
     *
     * This forces the exchange rates to be fetched again from the underlying provider.
     *
     * @return void
     */
    public function invalidate(): void
    {
        $this->exchangeRates = [];
    }
}
