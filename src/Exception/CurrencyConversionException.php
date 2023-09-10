<?php

declare(strict_types=1);

namespace Brick\Money\Exception;

/**
 * Exception thrown when an exchange rate is not available.
 */
class CurrencyConversionException extends MoneyException
{
    /**
     * CurrencyConversionException constructor.
     */
    public function __construct(
        string $message,
        private readonly string $sourceCurrencyCode,
        private readonly string $targetCurrencyCode
    ) {
        parent::__construct($message);
    }

    /**
     * @param string|null $info
     *
     * @return CurrencyConversionException
     */
    public static function exchangeRateNotAvailable(
        string $sourceCurrencyCode,
        string $targetCurrencyCode,
        ?string $info = null
    ): self {
        $message = sprintf(
            'No exchange rate available to convert %s to %s',
            $sourceCurrencyCode,
            $targetCurrencyCode
        );

        if ($info !== null) {
            $message .= ' (' . $info . ')';
        }

        return new self($message, $sourceCurrencyCode, $targetCurrencyCode);
    }

    final public function getSourceCurrencyCode(): string
    {
        return $this->sourceCurrencyCode;
    }

    final public function getTargetCurrencyCode(): string
    {
        return $this->targetCurrencyCode;
    }
}
