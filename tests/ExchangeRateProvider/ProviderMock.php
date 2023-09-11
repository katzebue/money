<?php

declare(strict_types=1);

namespace Katzebue\Money\Tests\ExchangeRateProvider;

use Katzebue\Money\Exception\CurrencyConversionException;
use Katzebue\Money\ExchangeRateProvider;

/**
 * A mock implementation of ExchangeRateProvider for tests.
 */
final class ProviderMock implements ExchangeRateProvider
{
    /**
     * @var array<string, array<string, float>>
     */
    private array $exchangeRates = [
        'EUR' => [
            'USD' => 1.1,
            'GBP' => 0.9,
        ],
    ];

    /**
     * The number of calls to getExchangeRate().
     */
    private int $calls = 0;

    public function getCalls(): int
    {
        return $this->calls;
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode): float
    {
        $this->calls++;

        if (isset($this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode])) {
            return $this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode];
        }

        throw CurrencyConversionException::exchangeRateNotAvailable($sourceCurrencyCode, $targetCurrencyCode);
    }
}
