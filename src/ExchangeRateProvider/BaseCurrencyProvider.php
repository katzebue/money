<?php

declare(strict_types=1);

namespace Katzebue\Money\ExchangeRateProvider;

use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Katzebue\Money\ExchangeRateProvider;

/**
 * Calculates exchange rates relative to a base currency.
 *
 * This provider is useful when your exchange rates source only provides exchange rates relative to a single currency.
 *
 * For example, if your source only has exchange rates from USD to EUR and USD to GBP,
 * using this provider on top of it would allow you to get an exchange rate from EUR to USD, GBP to USD,
 * or even EUR to GBP and GBP to EUR.
 * @see \Katzebue\Money\Tests\ExchangeRateProvider\BaseCurrencyProviderTest
 */
final readonly class BaseCurrencyProvider implements ExchangeRateProvider
{
    /**
     * @param ExchangeRateProvider $provider         The provider for rates relative to the base currency.
     * @param string               $baseCurrencyCode The code of the currency all the exchanges rates are based on.
     */
    public function __construct(
        /**
         * The provider for rates relative to the base currency.
         */
        private ExchangeRateProvider $provider,
        /**
         * The code of the currency all the exchanges rates are based on.
         */
        private string $baseCurrencyCode
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode): BigNumber
    {
        if ($sourceCurrencyCode === $this->baseCurrencyCode) {
            return BigNumber::of($this->provider->getExchangeRate($sourceCurrencyCode, $targetCurrencyCode));
        }

        if ($targetCurrencyCode === $this->baseCurrencyCode) {
            $exchangeRate = $this->provider->getExchangeRate($targetCurrencyCode, $sourceCurrencyCode);

            return BigRational::of($exchangeRate)->reciprocal();
        }

        $baseToSource = $this->provider->getExchangeRate($this->baseCurrencyCode, $sourceCurrencyCode);
        $baseToTarget = $this->provider->getExchangeRate($this->baseCurrencyCode, $targetCurrencyCode);

        return BigRational::of($baseToTarget)->dividedBy($baseToSource);
    }
}
