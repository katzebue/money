<?php

declare(strict_types=1);

namespace Katzebue\Money\Tests;

use Katzebue\Money\Context\AutoContext;
use Katzebue\Money\Currency;
use Katzebue\Money\Money;
use Katzebue\Money\MoneyBag;
use Katzebue\Money\RationalMoney;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;

#[CoversClass(MoneyBag::class)]
final class MoneyBagTest extends AbstractTestCase
{
    public function testEmptyMoneyBag(): void
    {
        $moneyBag = new MoneyBag();

        $this->assertMoneyBagContains([], $moneyBag);

        foreach (['USD', 'EUR', 'GBP', 'JPY'] as $currencyCode) {
            self::assertTrue($moneyBag->getAmount($currencyCode)->isZero());
        }
        foreach ([643, '124'] as $numericCurrencyCode) {
            self::assertTrue($moneyBag->getAmount($numericCurrencyCode)->isZero());
        }
    }

    public function testAddSubtractMoney(): MoneyBag
    {
        $moneyBag = new MoneyBag();

        $moneyBag->add(Money::of('123', 'EUR'));
        $this->assertMoneyBagContains(['EUR' => '123.00'], $moneyBag);

        $moneyBag->add(Money::of('234.99', 'EUR'));
        $this->assertMoneyBagContains(['EUR' => '357.99'], $moneyBag);

        $moneyBag->add(Money::of(3, 'JPY'));
        $this->assertMoneyBagContains(['EUR' => '357.99', 'JPY' => '3'], $moneyBag);

        $moneyBag->add(Money::of('1.1234', 'JPY', new AutoContext()));
        $this->assertMoneyBagContains(['EUR' => '357.99', 'JPY' => '4.1234'], $moneyBag);

        $moneyBag->subtract(Money::of('3.589950', 'EUR', new AutoContext()));
        $this->assertMoneyBagContains(['EUR' => '354.400050', 'JPY' => '4.1234'], $moneyBag);

        $moneyBag->add(RationalMoney::of('1/3', 'EUR'));
        $this->assertMoneyBagContains(['EUR' => '21284003/60000', 'JPY' => '4.1234'], $moneyBag);

        return $moneyBag;
    }

    #[Depends('testAddSubtractMoney')]
    public function testAddCustomCurrency(MoneyBag $moneyBag): void
    {
        $moneyBag->add(Money::of('0.1234', new Currency('BTC', 0, 'Bitcoin', 8)));
        $this->assertMoneyBagContains(['EUR' => '21284003/60000', 'JPY' => '4.1234', 'BTC' => '0.1234'], $moneyBag);
    }
}
