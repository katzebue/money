<?php

declare(strict_types=1);

namespace Katzebue\Money;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use InvalidArgumentException;
use Katzebue\Money\Context\DefaultContext;
use Katzebue\Money\Exception\MoneyMismatchException;
use Katzebue\Money\Exception\UnknownCurrencyException;
use NumberFormatter;

/**
 * @see \Katzebue\Money\Tests\MoneyTest
 */
class Money extends AbstractMoney implements MoneyInterface
{
    protected function __construct(
        /**
         * The amount.
         */
        private readonly BigDecimal $amount,
        /**
         * The currency.
         */
        private readonly Currency $currency,
        /**
         * The context that defines the capability of this Money.
         */
        private readonly Context $context
    ) {
    }

    public static function min(Money $money, Money ...$monies): static
    {
        $min = $money;

        foreach ($monies as $currentMoney) {
            if ($currentMoney->isLessThan($min)) {
                $min = $currentMoney;
            }
        }

        return $min;
    }

    public static function max(Money $money, Money ...$monies): static
    {
        $max = $money;

        foreach ($monies as $currentMoney) {
            if ($currentMoney->isGreaterThan($max)) {
                $max = $currentMoney;
            }
        }

        return $max;
    }

    public static function total(Money $money, Money ...$monies): static
    {
        $total = $money;

        foreach ($monies as $currentMoney) {
            $total = $total->plus($currentMoney);
        }

        return $total;
    }

    /**
     * Creates a Money from a rational amount, a currency, and a context.
     *
     * @param BigNumber    $amount       The amount.
     * @param Currency     $currency     The currency.
     * @param Context      $context      The context.
     * @param RoundingMode $roundingMode An optional rounding mode if the amount does not fit the context.
     *
     * @throws RoundingNecessaryException If RoundingMode::UNNECESSARY is used but rounding is necessary.
     */
    public static function create(BigNumber $amount, Currency $currency, Context $context, RoundingMode $roundingMode = RoundingMode::UNNECESSARY): static
    {
        $amount = $context->applyTo($amount, $currency, $roundingMode);

        return new static($amount, $currency, $context);
    }

    public static function of(
        BigNumber|int|float|string $amount,
        Currency|string|int $currency,
        ?Context $context = null,
        RoundingMode $roundingMode = RoundingMode::UNNECESSARY,
    ): static {
        if (! $currency instanceof Currency) {
            $currency = Currency::of($currency);
        }

        if ($context === null) {
            $context = new DefaultContext();
        }

        $amount = BigNumber::of($amount);

        return self::create($amount, $currency, $context, $roundingMode);
    }

    /**
     * Returns a Money from a number of minor units.
     *
     * By default, the money is created with a DefaultContext. This means that the amount is scaled to match the
     * currency's default fraction digits. For example, `Money::ofMinor(1234, 'USD')` will yield `USD 12.34`.
     * If the amount cannot be safely converted to this scale, an exception is thrown.
     *
     * @param BigNumber|int|float|string $minorAmount  The amount, in minor currency units.
     * @param Currency|string|int        $currency     The Currency instance, ISO currency code or ISO numeric currency code.
     * @param Context|null               $context      An optional Context.
     * @param RoundingMode               $roundingMode An optional RoundingMode, if the amount does not fit the context.
     *
     * @throws NumberFormatException      If the amount is a string in a non-supported format.
     * @throws UnknownCurrencyException   If the currency is an unknown currency code.
     * @throws RoundingNecessaryException If the rounding mode is RoundingMode::UNNECESSARY, and rounding is necessary
     *                                    to represent the amount at the requested scale.
     */
    public static function ofMinor(
        BigNumber|int|float|string $minorAmount,
        Currency|string|int $currency,
        ?Context $context = null,
        RoundingMode $roundingMode = RoundingMode::UNNECESSARY,
    ): static {
        if (! $currency instanceof Currency) {
            $currency = Currency::of($currency);
        }

        if ($context === null) {
            $context = new DefaultContext();
        }

        $amount = BigRational::of($minorAmount)->dividedBy(10 ** $currency->getDefaultFractionDigits());

        return self::create($amount, $currency, $context, $roundingMode);
    }

    public static function zero(Currency|string|int $currency, ?Context $context = null): static
    {
        if (! $currency instanceof Currency) {
            $currency = Currency::of($currency);
        }

        if ($context === null) {
            $context = new DefaultContext();
        }

        $amount = BigDecimal::zero();

        return self::create($amount, $currency, $context);
    }

    public function getAmount(): BigDecimal
    {
        return $this->amount;
    }

    public function getMinorAmount(): BigDecimal
    {
        return $this->amount->withPointMovedRight($this->currency->getDefaultFractionDigits());
    }

    public function getUnscaledAmount(): BigInteger
    {
        return $this->amount->getUnscaledValue();
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function plus(AbstractMoney|BigNumber|int|float|string $that, RoundingMode $roundingMode = RoundingMode::UNNECESSARY): static
    {
        $amount = $this->getAmountOf($that);

        if ($that instanceof Money) {
            $this->checkContext($that->getContext(), __FUNCTION__);

            if ($this->context->isFixedScale()) {
                return new static($this->amount->plus($that->amount), $this->currency, $this->context);
            }
        }

        $amount = $this->amount->toBigRational()->plus($amount);

        return self::create($amount, $this->currency, $this->context, $roundingMode);
    }

    public function minus(AbstractMoney|BigNumber|int|float|string $that, RoundingMode $roundingMode = RoundingMode::UNNECESSARY): static
    {
        $amount = $this->getAmountOf($that);

        if ($that instanceof Money) {
            $this->checkContext($that->getContext(), __FUNCTION__);

            if ($this->context->isFixedScale()) {
                return new static($this->amount->minus($that->amount), $this->currency, $this->context);
            }
        }

        $amount = $this->amount->toBigRational()->minus($amount);

        return self::create($amount, $this->currency, $this->context, $roundingMode);
    }

    public function multipliedBy(BigNumber|int|float|string $that, RoundingMode $roundingMode = RoundingMode::UNNECESSARY): static
    {
        $amount = $this->amount->toBigRational()->multipliedBy($that);

        return self::create($amount, $this->currency, $this->context, $roundingMode);
    }

    public function dividedBy(BigNumber|int|float|string $that, RoundingMode $roundingMode = RoundingMode::UNNECESSARY): static
    {
        $amount = $this->amount->toBigRational()->dividedBy($that);

        return self::create($amount, $this->currency, $this->context, $roundingMode);
    }

    public function quotient(BigNumber|int|float|string $that): static
    {
        $that = BigInteger::of($that);
        $step = $this->context->getStep();

        $scale  = $this->amount->getScale();
        $amount = $this->amount->withPointMovedRight($scale)->dividedBy($step);

        $q = $amount->quotient($that);
        $q = $q->multipliedBy($step)->withPointMovedLeft($scale);

        return new static($q, $this->currency, $this->context);
    }

    public function quotientAndRemainder(BigNumber|int|float|string $that): array
    {
        $that = BigInteger::of($that);
        $step = $this->context->getStep();

        $scale  = $this->amount->getScale();
        $amount = $this->amount->withPointMovedRight($scale)->dividedBy($step);

        [$q, $r] = $amount->quotientAndRemainder($that);

        $q = $q->multipliedBy($step)->withPointMovedLeft($scale);
        $r = $r->multipliedBy($step)->withPointMovedLeft($scale);

        $quotient  = new static($q, $this->currency, $this->context);
        $remainder = new static($r, $this->currency, $this->context);

        return [$quotient, $remainder];
    }

    public function allocate(int ...$ratios): array
    {
        if (! $ratios) {
            throw new InvalidArgumentException('Cannot allocate() an empty list of ratios.');
        }

        foreach ($ratios as $ratio) {
            if ($ratio < 0) {
                throw new InvalidArgumentException('Cannot allocate() negative ratios.');
            }
        }

        $total = array_sum($ratios);

        if ($total === 0) {
            throw new InvalidArgumentException('Cannot allocate() to zero ratios only.');
        }

        $step = $this->context->getStep();

        $monies = [];

        $unit = BigDecimal::ofUnscaledValue($step, $this->amount->getScale());
        $unit = new static($unit, $this->currency, $this->context);

        if ($this->isNegative()) {
            $unit = $unit->negated();
        }

        $remainder = $this;

        foreach ($ratios as $ratio) {
            $money = $this->multipliedBy($ratio)->quotient($total);
            $remainder = $remainder->minus($money);
            $monies[] = $money;
        }

        foreach ($monies as $key => $money) {
            if ($remainder->isZero()) {
                break;
            }

            $monies[$key] = $money->plus($unit);
            $remainder = $remainder->minus($unit);
        }

        return $monies;
    }

    public function allocateWithRemainder(int ...$ratios): array
    {
        if (! $ratios) {
            throw new InvalidArgumentException('Cannot allocateWithRemainder() an empty list of ratios.');
        }

        foreach ($ratios as $ratio) {
            if ($ratio < 0) {
                throw new InvalidArgumentException('Cannot allocateWithRemainder() negative ratios.');
            }
        }

        $total = array_sum($ratios);

        if ($total === 0) {
            throw new InvalidArgumentException('Cannot allocateWithRemainder() to zero ratios only.');
        }

        $ratios = $this->simplifyRatios(array_values($ratios));
        $total = array_sum($ratios);

        [, $remainder] = $this->quotientAndRemainder($total);

        $toAllocate = $this->minus($remainder);

        $monies = array_map(
            static fn (int $ratio) => $toAllocate->multipliedBy($ratio)->dividedBy($total),
            $ratios,
        );

        $monies[] = $remainder;

        return $monies;
    }

    /**
     * @param int[] $ratios
     * @psalm-param non-empty-list<int> $ratios
     *
     * @return int[]
     * @psalm-return non-empty-list<int>
     */
    private function simplifyRatios(array $ratios): array
    {
        $gcd = $this->gcdOfMultipleInt($ratios);

        return array_map(static fn (int $ratio) => intdiv($ratio, $gcd), $ratios);
    }

    /**
     * @param int[] $values
     *
     * @psalm-param non-empty-list<int> $values
     */
    private function gcdOfMultipleInt(array $values): int
    {
        $values = array_map(fn (int $value) => BigInteger::of($value), $values);

        return BigInteger::gcdMultiple(...$values)->toInt();
    }

    public function split(int $parts): array
    {
        if ($parts < 1) {
            throw new InvalidArgumentException('Cannot split() into less than 1 part.');
        }

        return $this->allocate(...array_fill(0, $parts, 1));
    }

    public function splitWithRemainder(int $parts): array
    {
        if ($parts < 1) {
            throw new InvalidArgumentException('Cannot splitWithRemainder() into less than 1 part.');
        }

        return $this->allocateWithRemainder(...array_fill(0, $parts, 1));
    }

    public function abs(): static
    {
        return new static($this->amount->abs(), $this->currency, $this->context);
    }

    public function negated(): static
    {
        return new static($this->amount->negated(), $this->currency, $this->context);
    }

    public function convertedTo(
        Currency|string|int $currency,
        BigNumber|int|float|string $exchangeRate,
        ?Context $context = null,
        RoundingMode $roundingMode = RoundingMode::UNNECESSARY,
    ): static {
        if (! $currency instanceof Currency) {
            $currency = Currency::of($currency);
        }

        if ($context === null) {
            $context = $this->context;
        }

        $amount = $this->amount->toBigRational()->multipliedBy($exchangeRate);

        return self::create($amount, $currency, $context, $roundingMode);
    }

    public function formatWith(NumberFormatter $formatter): string
    {
        return $formatter->formatCurrency(
            $this->amount->toFloat(),
            $this->currency->getCurrencyCode()
        );
    }

    public function formatTo(string $locale, bool $allowWholeNumber = false): string
    {
        /** @var NumberFormatter|null $lastFormatter */
        static $lastFormatter = null;
        static $lastFormatterLocale;
        static $lastFormatterScale;

        if ($allowWholeNumber && ! $this->amount->hasNonZeroFractionalPart()) {
            $scale = 0;
        } else {
            $scale = $this->amount->getScale();
        }

        if ($lastFormatter !== null && $lastFormatterLocale === $locale) {
            $formatter = $lastFormatter;

            if ($lastFormatterScale !== $scale) {
                $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $scale);
                $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $scale);

                $lastFormatterScale = $scale;
            }
        } else {
            $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);

            $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $scale);
            $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $scale);

            $lastFormatter = $formatter;
            $lastFormatterLocale = $locale;
            $lastFormatterScale = $scale;
        }

        return $this->formatWith($formatter);
    }

    public function toRational(): RationalMoney
    {
        return new RationalMoney($this->amount->toBigRational(), $this->currency);
    }

    /**
     * Returns a non-localized string representation of this Money, e.g. "EUR 23.00".
     */
    public function __toString(): string
    {
        return $this->currency . ' ' . $this->amount;
    }

    /**
     * @param Context $context The Context to check against this Money.
     * @param string  $method  The invoked method name.
     *
     * @throws MoneyMismatchException If monies don't match.
     */
    protected function checkContext(Context $context, string $method): void
    {
        if ($this->context != $context) { // non-strict equality on purpose
            throw MoneyMismatchException::contextMismatch($method);
        }
    }
}
