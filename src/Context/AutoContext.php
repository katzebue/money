<?php

declare(strict_types=1);

namespace Katzebue\Money\Context;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use InvalidArgumentException;
use Katzebue\Money\Context;
use Katzebue\Money\Currency;

/**
 * Automatically adjusts the scale of a number to the strict minimum.
 * @see \Katzebue\Money\Tests\Context\AutoContextTest
 */
final class AutoContext implements Context
{
    /**
     * {@inheritdoc}
     */
    public function applyTo(BigNumber $amount, Currency $currency, RoundingMode $roundingMode): BigDecimal
    {
        if ($roundingMode !== RoundingMode::UNNECESSARY) {
            throw new InvalidArgumentException('AutoContext only supports RoundingMode::UNNECESSARY');
        }

        return $amount->toBigDecimal()->stripTrailingZeros();
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
        return false;
    }
}
