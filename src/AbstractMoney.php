<?php

declare(strict_types=1);

namespace Katzebue\Money;

use Brick\Math\BigNumber;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use JsonSerializable;
use Katzebue\Money\Exception\MoneyMismatchException;
use Stringable;

/**
 * Base class for Money and RationalMoney.
 *
 * Please consider this class sealed: extending this class yourself is not supported, and breaking changes (such as
 * adding new abstract methods) can happen at any time, even in a minor version.
 */
abstract class AbstractMoney implements MoneyContainer, Stringable, JsonSerializable
{
    abstract public function getAmount(): BigNumber;

    abstract public function getCurrency(): Currency;

    /**
     * Converts this money to a Money in the given Context.
     *
     * @param Context $context The context.
     * @param RoundingMode $roundingMode The rounding mode, if necessary.
     *
     * @throws RoundingNecessaryException If RoundingMode::UNNECESSARY is used but rounding is necessary.
     */
    final public function to(Context $context, RoundingMode $roundingMode = RoundingMode::UNNECESSARY): Money
    {
        return Money::create($this->getAmount(), $this->getCurrency(), $context, $roundingMode);
    }

    final public function getAmounts(): array
    {
        return [
            $this->getCurrency()->getCurrencyCode() => $this->getAmount(),
        ];
    }

    final public function getSign(): int
    {
        return $this->getAmount()->getSign();
    }

    final public function isZero(): bool
    {
        return $this->getAmount()->isZero();
    }

    final public function isNegative(): bool
    {
        return $this->getAmount()->isNegative();
    }

    final public function isNegativeOrZero(): bool
    {
        return $this->getAmount()->isNegativeOrZero();
    }

    final public function isPositive(): bool
    {
        return $this->getAmount()->isPositive();
    }

    final public function isPositiveOrZero(): bool
    {
        return $this->getAmount()->isPositiveOrZero();
    }

    final public function compareTo(AbstractMoney|BigNumber|int|float|string $that): int
    {
        return $this->getAmount()->compareTo($this->getAmountOf($that));
    }

    final public function isEqualTo(AbstractMoney|BigNumber|int|float|string $that): bool
    {
        return $this->getAmount()->isEqualTo($this->getAmountOf($that));
    }

    final public function isLessThan(AbstractMoney|BigNumber|int|float|string $that): bool
    {
        return $this->getAmount()->isLessThan($this->getAmountOf($that));
    }

    final public function isLessThanOrEqualTo(AbstractMoney|BigNumber|int|float|string $that): bool
    {
        return $this->getAmount()->isLessThanOrEqualTo($this->getAmountOf($that));
    }

    final public function isGreaterThan(AbstractMoney|BigNumber|int|float|string $that): bool
    {
        return $this->getAmount()->isGreaterThan($this->getAmountOf($that));
    }

    final public function isGreaterThanOrEqualTo(AbstractMoney|BigNumber|int|float|string $that): bool
    {
        return $this->getAmount()->isGreaterThanOrEqualTo($this->getAmountOf($that));
    }

    final public function isAmountAndCurrencyEqualTo(AbstractMoney $that): bool
    {
        return $this->getAmount()->isEqualTo($that->getAmount())
            && $this->getCurrency()->is($that->getCurrency());
    }

    /**
     * Returns the amount of the given parameter.
     *
     * If the parameter is a money, its currency is checked against this money's currency.
     *
     * @param AbstractMoney|BigNumber|int|float|string $that A money or amount.
     *
     * @throws MoneyMismatchException If currencies don't match.
     */
    final protected function getAmountOf(AbstractMoney|BigNumber|int|float|string $that): BigNumber|int|float|string
    {
        if ($that instanceof AbstractMoney) {
            if (! $that->getCurrency()->is($this->getCurrency())) {
                throw MoneyMismatchException::currencyMismatch($this->getCurrency(), $that->getCurrency());
            }

            return $that->getAmount();
        }

        return $that;
    }

    final public function jsonSerialize(): array
    {
        return [
            'amount' => $this->getAmount()->jsonSerialize(),
            'currency' => $this->getCurrency()->jsonSerialize(),
        ];
    }
}
