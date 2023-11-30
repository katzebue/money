<?php

declare(strict_types=1);

namespace Katzebue\Money\Tests;

use Brick\Math\BigRational;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Iterator;
use Katzebue\Money\Context;
use Katzebue\Money\Context\AutoContext;
use Katzebue\Money\Context\CashContext;
use Katzebue\Money\Context\CustomContext;
use Katzebue\Money\Context\DefaultContext;
use Katzebue\Money\Currency;
use Katzebue\Money\Exception\MoneyMismatchException;
use Katzebue\Money\Money;
use Katzebue\Money\RationalMoney;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for class RationalMoney.
 */
final class RationalMoneyTest extends AbstractTestCase
{
    public function testGetters(): void
    {
        $amount = BigRational::of('123/456');
        $currency = Currency::of('EUR');

        $money = new RationalMoney($amount, $currency);

        self::assertSame($amount, $money->getAmount());
        self::assertSame($currency, $money->getCurrency());
    }

    #[DataProvider('providerPlus')]
    public function testPlus(array $rationalMoney, mixed $amount, string $expected): void
    {
        $rationalMoney = RationalMoney::of(...$rationalMoney);

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $rationalMoney->plus($amount);

        if (!$this->isExceptionClass($expected)) {
            $this->assertRationalMoneyEquals($expected, $actual);
        }
    }

    public static function providerPlus(): Iterator
    {
        yield [['1.1234', 'USD'], '987.65', 'USD 988773400/1000000'];
        yield [['123/456', 'GBP'], '14.99', 'GBP 695844/45600'];
        yield [['123/456', 'GBP'], '567/890', 'GBP 368022/405840'];
        yield [['1.123', 'CHF'], RationalMoney::of('0.1', 'CHF'), 'CHF 12230/10000'];
        yield [['1.123', 'CHF'], RationalMoney::of('0.1', 'CAD'), MoneyMismatchException::class];
        yield [['9.876', 'CAD'], Money::of(3, 'CAD'), 'CAD 1287600/100000'];
        yield [['9.876', 'CAD'], Money::of(3, 'USD'), MoneyMismatchException::class];
    }

    #[DataProvider('providerMinus')]
    public function testMinus(array $rationalMoney, mixed $amount, string $expected): void
    {
        $rationalMoney = RationalMoney::of(...$rationalMoney);

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $rationalMoney->minus($amount);

        if (!$this->isExceptionClass($expected)) {
            $this->assertRationalMoneyEquals($expected, $actual);
        }
    }

    public static function providerMinus(): Iterator
    {
        yield [['987.65', 'USD'], '1.1234', 'USD 986526600/1000000'];
        yield [['123/456', 'GBP'], '14.99', 'GBP -671244/45600'];
        yield [['123/456', 'GBP'], '567/890', 'GBP -149082/405840'];
        yield [['1.123', 'CHF'], RationalMoney::of('0.1', 'CHF'), 'CHF 10230/10000'];
        yield [['1.123', 'CHF'], RationalMoney::of('0.1', 'CAD'), MoneyMismatchException::class];
        yield [['9.876', 'CAD'], Money::of(3, 'CAD'), 'CAD 687600/100000'];
        yield [['9.876', 'CAD'], Money::of(3, 'USD'), MoneyMismatchException::class];
    }

    #[DataProvider('providerMultipliedBy')]
    public function testMultipliedBy(array $rationalMoney, mixed $operand, string $expected): void
    {
        $rationalMoney = RationalMoney::of(...$rationalMoney);

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $rationalMoney->multipliedBy($operand);

        if (!$this->isExceptionClass($expected)) {
            $this->assertRationalMoneyEquals($expected, $actual);
        }
    }

    public static function providerMultipliedBy(): Iterator
    {
        yield [['987.65', 'USD'], '1.123456', 'USD 110958131840/100000000'];
        yield [['123/456', 'GBP'], '14.99', 'GBP 184377/45600'];
        yield [['123/456', 'GBP'], '567/890', 'GBP 69741/405840'];
    }

    #[DataProvider('providerDividedBy')]
    public function testDividedBy(array $rationalMoney, mixed $operand, string $expected): void
    {
        $rationalMoney = RationalMoney::of(...$rationalMoney);

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $rationalMoney->dividedBy($operand);

        if (!$this->isExceptionClass($expected)) {
            $this->assertRationalMoneyEquals($expected, $actual);
        }
    }

    public static function providerDividedBy(): Iterator
    {
        yield [['987.65', 'USD'], '1.123456', 'USD 98765000000/112345600'];
        yield [['987.65', 'USD'], '5', 'USD 98765/500'];
        yield [['123/456', 'GBP'], '14.99', 'GBP 12300/683544'];
        yield [['123/456', 'GBP'], '567/890', 'GBP 109470/258552'];
    }

    #[DataProvider('providerSimplified')]
    public function testSimplified(array $rationalMoney, string $expected): void
    {
        $rationalMoney = RationalMoney::of(...$rationalMoney);

        $actual = $rationalMoney->simplified();
        $this->assertRationalMoneyEquals($expected, $actual);
    }

    public static function providerSimplified(): Iterator
    {
        yield [['123456/10000', 'USD'], 'USD 7716/625'];
        yield [['695844/45600', 'CAD'], 'CAD 57987/3800'];
        yield [['368022/405840', 'EUR'], 'EUR 61337/67640'];
        yield [['-671244/45600', 'GBP'], 'GBP -55937/3800'];
    }

    #[DataProvider('providerTo')]
    public function testTo(array $rationalMoney, Context $context, RoundingMode $roundingMode, string $expected): void
    {
        $rationalMoney = RationalMoney::of(...$rationalMoney);

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $rationalMoney->to($context, $roundingMode);

        if (!$this->isExceptionClass($expected)) {
            $this->assertMoneyIs($expected, $actual);
        }
    }

    public static function providerTo(): Iterator
    {
        yield [['987.65', 'USD'], new DefaultContext(), RoundingMode::UNNECESSARY, 'USD 987.65'];
        yield [['246/200', 'USD'], new DefaultContext(), RoundingMode::UNNECESSARY, 'USD 1.23'];
        yield [['987.65', 'CZK'], new CashContext(100), RoundingMode::UP, 'CZK 988.00'];
        yield [['123/456', 'GBP'], new CustomContext(4), RoundingMode::UP, 'GBP 0.2698'];
        yield [['123/456', 'GBP'], new AutoContext(), RoundingMode::UNNECESSARY, RoundingNecessaryException::class];
        yield [['123456789/256', 'CHF'], new AutoContext(), RoundingMode::UNNECESSARY, 'CHF 482253.08203125'];
    }

    #[DataProvider('providerJsonSerialize')]
    public function testJsonSerialize(RationalMoney $money, array $expected): void
    {
        self::assertSame($expected, $money->jsonSerialize());
        self::assertSame(json_encode($expected, JSON_THROW_ON_ERROR), json_encode($money, JSON_THROW_ON_ERROR));
    }

    public static function providerJsonSerialize(): Iterator
    {
        yield [RationalMoney::of('3.5', 'EUR'), ['amount' => '35/10', 'currency' => 'EUR']];
        yield [RationalMoney::of('3.888923', 'GBP'), ['amount' => '3888923/1000000', 'currency' => 'GBP']];
    }
}
