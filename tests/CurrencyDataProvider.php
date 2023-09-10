<?php

declare(strict_types=1);

namespace Brick\Money\Tests;

use Brick\Money\Exception\UnknownCurrencyException;
use Iterator;

final class CurrencyDataProvider
{
    public static function providerOf(): Iterator
    {
        yield ['USD', 840, 2, 'US Dollar'];
        yield ['EUR', 978, 2, 'Euro'];
        yield ['GBP', 826, 2, 'Pound Sterling'];
        yield ['JPY', 392, 0, 'Yen'];
        yield ['DZD', 12, 2, 'Algerian Dinar'];
    }

    public static function providerOfUnknownCurrencyCode(): Iterator
    {
        yield ['XXX'];
        yield [-1];
    }

    public static function providerOfCountry(): Iterator
    {
        yield ['CA', 'CAD'];
        yield ['CH', 'CHF'];
        yield ['DE', 'EUR'];
        yield ['ES', 'EUR'];
        yield ['FR', 'EUR'];
        yield ['GB', 'GBP'];
        yield ['IT', 'EUR'];
        yield ['US', 'USD'];
        yield ['AQ', UnknownCurrencyException::class];
        // no currency
        yield ['CU', UnknownCurrencyException::class];
        // 2 currencies
        yield ['XX', UnknownCurrencyException::class];
    }
}
