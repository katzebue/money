<?php

declare(strict_types=1);

namespace Katzebue\Money;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use InvalidArgumentException;
use Katzebue\Money\Exception\MoneyMismatchException;
use Katzebue\Money\Exception\UnknownCurrencyException;
use NumberFormatter;

/**
 * A monetary value in a given currency. This class is immutable.
 *
 * A Money has an amount, a currency, and a context. The context defines the scale of the amount, and an optional cash
 * rounding step, for monies that do not have coins or notes for their smallest units.
 *
 * All operations on a Money return another Money with the same context. The available contexts are:
 *
 * - DefaultContext handles monies with the default scale for the currency.
 * - CashContext is similar to DefaultContext, but supports a cash rounding step.
 * - CustomContext handles monies with a custom scale, and optionally step.
 * - AutoContext automatically adjusts the scale of the money to the minimum required.
 * @see \Katzebue\Money\Tests\MoneyTest
 */
interface MoneyInterface
{
    /**
     * Required by interface MoneyContainer.
     *
     * @psalm-return array<string, BigNumber>
     *
     * @return BigNumber[]
     */
    public function getAmounts(): array;

    /**
     * Returns the sign of this money.
     *
     * @return int -1 if the number is negative, 0 if zero, 1 if positive.
     */
    public function getSign(): int;

    /**
     * Returns whether this money has zero value.
     */
    public function isZero(): bool;

    /**
     * Returns whether this money has a negative value.
     */
    public function isNegative(): bool;

    /**
     * Returns whether this money has a negative or zero value.
     */
    public function isNegativeOrZero(): bool;

    /**
     * Returns whether this money has a positive value.
     */
    public function isPositive(): bool;

    /**
     * Returns whether this money has a positive or zero value.
     */
    public function isPositiveOrZero(): bool;

    /**
     * Compares this money to the given amount.
     *
     * @return int [-1, 0, 1] if `$this` is less than, equal to, or greater than `$that`.
     *
     * @throws MathException          If the argument is an invalid number.
     * @throws MoneyMismatchException If the argument is a money in a different currency.
     */
    public function compareTo(AbstractMoney|BigNumber|int|float|string $that): int;

    /**
     * Returns whether this money is equal to the given amount.
     *
     * @throws MathException          If the argument is an invalid number.
     * @throws MoneyMismatchException If the argument is a money in a different currency.
     */
    public function isEqualTo(AbstractMoney|BigNumber|int|float|string $that): bool;

    /**
     * Returns whether this money is less than the given amount.
     *
     * @throws MathException          If the argument is an invalid number.
     * @throws MoneyMismatchException If the argument is a money in a different currency.
     */
    public function isLessThan(AbstractMoney|BigNumber|int|float|string $that): bool;

    /**
     * Returns whether this money is less than or equal to the given amount.
     *
     * @throws MathException          If the argument is an invalid number.
     * @throws MoneyMismatchException If the argument is a money in a different currency.
     */
    public function isLessThanOrEqualTo(AbstractMoney|BigNumber|int|float|string $that): bool;

    /**
     * Returns whether this money is greater than the given amount.
     *
     * @throws MathException          If the argument is an invalid number.
     * @throws MoneyMismatchException If the argument is a money in a different currency.
     */
    public function isGreaterThan(AbstractMoney|BigNumber|int|float|string $that): bool;

    /**
     * Returns whether this money is greater than or equal to the given amount.
     *
     * @throws MathException          If the argument is an invalid number.
     * @throws MoneyMismatchException If the argument is a money in a different currency.
     */
    public function isGreaterThanOrEqualTo(AbstractMoney|BigNumber|int|float|string $that): bool;

    /**
     * Returns whether this money's amount and currency are equal to those of the given money.
     *
     * Unlike isEqualTo(), this method only accepts a money, and returns false if the given money is in another
     * currency, instead of throwing a MoneyMismatchException.
     */
    public function isAmountAndCurrencyEqualTo(AbstractMoney $that): bool;

    /**
     * Returns the minimum of the given monies.
     *
     * If several monies are equal to the minimum value, the first one is returned.
     *
     * @param Money $money The first money.
     * @param Money ...$monies The subsequent monies.
     *
     * @throws MoneyMismatchException If all the monies are not in the same currency.
     */
    public static function min(Money $money, Money ...$monies): static;

    /**
     * Returns the maximum of the given monies.
     *
     * If several monies are equal to the maximum value, the first one is returned.
     *
     * @param Money $money The first money.
     * @param Money ...$monies The subsequent monies.
     *
     * @throws MoneyMismatchException If all the monies are not in the same currency.
     */
    public static function max(Money $money, Money ...$monies): static;

    /**
     * Returns the total of the given monies.
     *
     * The monies must share the same currency and context.
     *
     * @param Money $money The first money.
     * @param Money ...$monies The subsequent monies.
     *
     * @throws MoneyMismatchException If all the monies are not in the same currency and context.
     */
    public static function total(Money $money, Money ...$monies): static;

    /**
     * Returns a Money of the given amount and currency.
     *
     * By default, the money is created with a DefaultContext. This means that the amount is scaled to match the
     * currency's default fraction digits. For example, `Money::of('2.5', 'USD')` will yield `USD 2.50`.
     * If the amount cannot be safely converted to this scale, an exception is thrown.
     *
     * To override this behaviour, a Context instance can be provided.
     * Operations on this Money return a Money with the same context.
     *
     * @param BigNumber|int|float|string $amount The monetary amount.
     * @param Currency|string|int $currency The Currency instance, ISO currency code or ISO numeric currency code.
     * @param Context|null $context An optional Context.
     * @param RoundingMode $roundingMode An optional RoundingMode, if the amount does not fit the context.
     *
     * @throws NumberFormatException      If the amount is a string in a non-supported format.
     * @throws UnknownCurrencyException   If the currency is an unknown currency code.
     * @throws RoundingNecessaryException If the rounding mode is RoundingMode::UNNECESSARY, and rounding is necessary
     *                                    to represent the amount at the requested scale.
     */
    public static function of(
        BigNumber|int|float|string $amount,
        Currency|string|int $currency,
        ?Context $context = null,
        RoundingMode $roundingMode = RoundingMode::UNNECESSARY,
    ): static;

    /**
     * Returns a Money with zero value, in the given currency.
     *
     * By default, the money is created with a DefaultContext: it has the default scale for the currency.
     * A Context instance can be provided to override the default.
     *
     * @param Currency|string|int $currency The Currency instance, ISO currency code or ISO numeric currency code.
     * @param Context|null $context An optional context.
     */
    public static function zero(Currency|string|int $currency, ?Context $context = null): static;

    /**
     * Returns the amount of this Money, as a BigDecimal.
     */
    public function getAmount(): BigDecimal;

    /**
     * Returns the amount of this Money in minor units (cents) for the currency.
     *
     * The value is returned as a BigDecimal. If this Money has a scale greater than that of the currency, the result
     * will have a non-zero scale.
     *
     * For example, `USD 1.23` will return a BigDecimal of `123`, while `USD 1.2345` will return `123.45`.
     */
    public function getMinorAmount(): BigDecimal;

    /**
     * Returns a BigInteger containing the unscaled value (all digits) of this money.
     *
     * For example, `123.4567 USD` will return a BigInteger of `1234567`.
     */
    public function getUnscaledAmount(): BigInteger;

    /**
     * Returns the Currency of this Money.
     */
    public function getCurrency(): Currency;

    /**
     * Returns the Context of this Money.
     */
    public function getContext(): Context;

    /**
     * Returns the sum of this Money and the given amount.
     *
     * If the operand is a Money, it must have the same context as this Money, or an exception is thrown.
     * This is by design, to ensure that contexts are not mixed accidentally.
     * If you do need to add a Money in a different context, you can use `plus($money->toRational())`.
     *
     * The resulting Money has the same context as this Money. If the result needs rounding to fit this context, a
     * rounding mode can be provided. If a rounding mode is not provided and rounding is necessary, an exception is
     * thrown.
     *
     * @param AbstractMoney|BigNumber|int|float|string $that The money or amount to add.
     * @param RoundingMode $roundingMode An optional RoundingMode constant.
     *
     * @throws MathException          If the argument is an invalid number or rounding is necessary.
     * @throws MoneyMismatchException If the argument is a money in a different currency or in a different context.
     */
    public function plus(
        AbstractMoney|BigNumber|int|float|string $that,
        RoundingMode $roundingMode = RoundingMode::UNNECESSARY
    ): static;

    /**
     * Returns the difference of this Money and the given amount.
     *
     * If the operand is a Money, it must have the same context as this Money, or an exception is thrown.
     * This is by design, to ensure that contexts are not mixed accidentally.
     * If you do need to subtract a Money in a different context, you can use `minus($money->toRational())`.
     *
     * The resulting Money has the same context as this Money. If the result needs rounding to fit this context, a
     * rounding mode can be provided. If a rounding mode is not provided and rounding is necessary, an exception is
     * thrown.
     *
     * @param AbstractMoney|BigNumber|int|float|string $that The money or amount to subtract.
     * @param RoundingMode $roundingMode An optional RoundingMode constant.
     *
     * @throws MathException          If the argument is an invalid number or rounding is necessary.
     * @throws MoneyMismatchException If the argument is a money in a different currency or in a different context.
     */
    public function minus(
        AbstractMoney|BigNumber|int|float|string $that,
        RoundingMode $roundingMode = RoundingMode::UNNECESSARY
    ): static;

    /**
     * Returns the product of this Money and the given number.
     *
     * The resulting Money has the same context as this Money. If the result needs rounding to fit this context, a
     * rounding mode can be provided. If a rounding mode is not provided and rounding is necessary, an exception is
     * thrown.
     *
     * @param BigNumber|int|float|string $that The multiplier.
     * @param RoundingMode $roundingMode An optional RoundingMode constant.
     *
     * @throws MathException If the argument is an invalid number or rounding is necessary.
     */
    public function multipliedBy(
        BigNumber|int|float|string $that,
        RoundingMode $roundingMode = RoundingMode::UNNECESSARY
    ): static;

    /**
     * Returns the result of the division of this Money by the given number.
     *
     * The resulting Money has the same context as this Money. If the result needs rounding to fit this context, a
     * rounding mode can be provided. If a rounding mode is not provided and rounding is necessary, an exception is
     * thrown.
     *
     * @param BigNumber|int|float|string $that The divisor.
     * @param RoundingMode $roundingMode An optional RoundingMode constant.
     *
     * @throws MathException If the argument is an invalid number or is zero, or rounding is necessary.
     */
    public function dividedBy(
        BigNumber|int|float|string $that,
        RoundingMode $roundingMode = RoundingMode::UNNECESSARY
    ): static;

    /**
     * Returns the quotient of the division of this Money by the given number.
     *
     * The given number must be a integer value. The resulting Money has the same context as this Money.
     * This method can serve as a basis for a money allocation algorithm.
     *
     * @param BigNumber|int|float|string $that The divisor. Must be convertible to a BigInteger.
     *
     * @throws MathException If the divisor cannot be converted to a BigInteger.
     */
    public function quotient(BigNumber|int|float|string $that): static;

    /**
     * Returns the quotient and the remainder of the division of this Money by the given number.
     *
     * The given number must be an integer value. The resulting monies have the same context as this Money.
     * This method can serve as a basis for a money allocation algorithm.
     *
     * @param BigNumber|int|float|string $that The divisor. Must be convertible to a BigInteger.
     *
     * @return static[] The quotient and the remainder.
     *
     * @psalm-return array{static, static}
     *
     * @throws MathException If the divisor cannot be converted to a BigInteger.
     */
    public function quotientAndRemainder(BigNumber|int|float|string $that): array;

    /**
     * Allocates this Money according to a list of ratios.
     *
     * If the allocation yields a remainder, its amount is split over the first monies in the list,
     * so that the total of the resulting monies is always equal to this Money.
     *
     * For example, given a `USD 49.99` money in the default context,
     * `allocate(1, 2, 3, 4)` returns [`USD 5.00`, `USD 10.00`, `USD 15.00`, `USD 19.99`]
     *
     * The resulting monies have the same context as this Money.
     *
     * @param int[] $ratios The ratios.
     *
     * @return static[]
     *
     * @throws InvalidArgumentException If called with invalid parameters.
     */
    public function allocate(int ...$ratios): array;

    /**
     * Allocates this Money according to a list of ratios.
     *
     * The remainder is also present, appended at the end of the list.
     *
     * For example, given a `USD 49.99` money in the default context,
     * `allocateWithRemainder(1, 2, 3, 4)` returns [`USD 4.99`, `USD 9.99`, `USD 14.99`, `USD 19.99`, `USD 0.03`]
     *
     * The resulting monies have the same context as this Money.
     *
     * @param int[] $ratios The ratios.
     *
     * @return static[]
     *
     * @throws InvalidArgumentException If called with invalid parameters.
     */
    public function allocateWithRemainder(int ...$ratios): array;

    /**
     * Splits this Money into a number of parts.
     *
     * If the division of this Money by the number of parts yields a remainder, its amount is split over the first
     * monies in the list, so that the total of the resulting monies is always equal to this Money.
     *
     * For example, given a `USD 100.00` money in the default context,
     * `split(3)` returns [`USD 33.34`, `USD 33.33`, `USD 33.33`]
     *
     * The resulting monies have the same context as this Money.
     *
     * @param int $parts The number of parts.
     *
     * @return static[]
     *
     * @throws InvalidArgumentException If called with invalid parameters.
     */
    public function split(int $parts): array;

    /**
     * Splits this Money into a number of parts and a remainder.
     *
     * For example, given a `USD 100.00` money in the default context,
     * `splitWithRemainder(3)` returns [`USD 33.33`, `USD 33.33`, `USD 33.33`, `USD 0.01`]
     *
     * The resulting monies have the same context as this Money.
     *
     * @param int $parts The number of parts
     *
     * @return static[]
     *
     * @throws InvalidArgumentException If called with invalid parameters.
     */
    public function splitWithRemainder(int $parts): array;

    /**
     * Returns a Money whose value is the absolute value of this Money.
     *
     * The resulting Money has the same context as this Money.
     */
    public function abs(): static;

    /**
     * Returns a Money whose value is the negated value of this Money.
     *
     * The resulting Money has the same context as this Money.
     */
    public function negated(): static;

    /**
     * Converts this Money to another currency, using an exchange rate.
     *
     * By default, the resulting Money has the same context as this Money.
     * This can be overridden by providing a Context.
     *
     * For example, converting a default money of `USD 1.23` to `EUR` with an exchange rate of `0.91` and
     * RoundingMode::UP will yield `EUR 1.12`.
     *
     * @param Currency|string|int $currency The Currency instance, ISO currency code or ISO numeric currency code.
     * @param BigNumber|int|float|string $exchangeRate The exchange rate to multiply by.
     * @param Context|null $context An optional context.
     * @param RoundingMode $roundingMode An optional rounding mode.
     *
     * @throws UnknownCurrencyException If an unknown currency code is given.
     * @throws MathException            If the exchange rate or rounding mode is invalid, or rounding is necessary.
     */
    public function convertedTo(
        Currency|string|int $currency,
        BigNumber|int|float|string $exchangeRate,
        ?Context $context = null,
        RoundingMode $roundingMode = RoundingMode::UNNECESSARY,
    ): static;

    /**
     * Formats this Money with the given NumberFormatter.
     *
     * Note that NumberFormatter internally represents values using floating point arithmetic,
     * so discrepancies can appear when formatting very large monetary values.
     *
     * @param NumberFormatter $formatter The formatter to format with.
     */
    public function formatWith(NumberFormatter $formatter): string;

    /**
     * Formats this Money to the given locale.
     *
     * Note that this method uses NumberFormatter, which internally represents values using floating point arithmetic,
     * so discrepancies can appear when formatting very large monetary values.
     *
     * @param string $locale The locale to format to.
     * @param bool $allowWholeNumber Whether to allow formatting as a whole number if the amount has no fraction.
     */
    public function formatTo(string $locale, bool $allowWholeNumber = false): string;

    public function toRational(): RationalMoney;
}
