<?php

declare(strict_types=1);

namespace Katzebue\Money\Context;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Katzebue\Money\Context;
use Katzebue\Money\Currency;

/**
 * Adjusts a number to the default scale for the currency.
 * @see \Katzebue\Money\Tests\Context\DefaultContextTest
 */
final class DefaultContext implements Context
{
    /**
     * @inheritdoc
     */
    public function applyTo(BigNumber $amount, Currency $currency, RoundingMode $roundingMode): BigDecimal
    {
        return $amount->toScale($currency->getDefaultFractionDigits(), $roundingMode);
    }

    /**
     * {@inheritdoc}
     */
    public function getStep(): int
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function isFixedScale(): bool
    {
        return true;
    }
}
