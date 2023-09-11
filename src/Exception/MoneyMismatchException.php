<?php

declare(strict_types=1);

namespace Katzebue\Money\Exception;

use Katzebue\Money\Currency;

/**
 * Exception thrown when a money is not in the expected currency or context.
 */
class MoneyMismatchException extends MoneyException
{
    /**
     *
     * @return MoneyMismatchException
     */
    public static function currencyMismatch(Currency $expected, Currency $actual): self
    {
        return new self(sprintf(
            'The monies do not share the same currency: expected %s, got %s.',
            $expected->getCurrencyCode(),
            $actual->getCurrencyCode()
        ));
    }

    /**
     * @return MoneyMismatchException
     */
    public static function contextMismatch(string $method): self
    {
        return new self(sprintf(
            'The monies do not share the same context. ' .
            'If this is intended, use %s($money->toRational()) instead of %s($money).',
            $method,
            $method
        ));
    }
}
