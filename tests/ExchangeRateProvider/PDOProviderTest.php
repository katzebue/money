<?php

declare(strict_types=1);

namespace Katzebue\Money\Tests\ExchangeRateProvider;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Closure;
use InvalidArgumentException;
use Iterator;
use Katzebue\Money\Exception\CurrencyConversionException;
use Katzebue\Money\ExchangeRateProvider\PDOProvider;
use Katzebue\Money\ExchangeRateProvider\PDOProviderConfiguration;
use Katzebue\Money\Tests\AbstractTestCase;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for class PDOProvider.
 */
#[RequiresPhpExtension('pdo_sqlite')]
final class PDOProviderTest extends AbstractTestCase
{
    /**
     * @param Closure(): PDOProviderConfiguration $getConfiguration
     */
    #[DataProvider('providerConstructorWithInvalidConfiguration')]
    public function testConfigurationConstructorThrows(Closure $getConfiguration, string $exceptionMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $getConfiguration();
    }

    public static function providerConstructorWithInvalidConfiguration(): Iterator
    {
        yield [fn () => new PDOProviderConfiguration(
            tableName: 'exchange_rate',
            exchangeRateColumnName: 'exchange_rate',
            targetCurrencyCode: 'EUR',
        ), 'Invalid configuration: one of $sourceCurrencyCode or $sourceCurrencyColumnName must be set.'];
        yield [fn () => new PDOProviderConfiguration(
            tableName: 'exchange_rate',
            exchangeRateColumnName: 'exchange_rate',
            sourceCurrencyCode: 'EUR',
            sourceCurrencyColumnName: 'source_currency_code',
            targetCurrencyCode: 'EUR',
        ), 'Invalid configuration: $sourceCurrencyCode and $sourceCurrencyColumnName cannot be both set.'];
        yield [fn () => new PDOProviderConfiguration(
            tableName: 'exchange_rate',
            exchangeRateColumnName: 'exchange_rate',
            sourceCurrencyCode: 'EUR',
        ), 'Invalid configuration: one of $targetCurrencyCode or $targetCurrencyColumnName must be set.'];
        yield [fn () => new PDOProviderConfiguration(
            tableName: 'exchange_rate',
            exchangeRateColumnName: 'exchange_rate',
            sourceCurrencyCode: 'EUR',
            targetCurrencyCode: 'EUR',
            targetCurrencyColumnName: 'target_currency_code',
        ), 'Invalid configuration: $targetCurrencyCode and $targetCurrencyColumnName cannot be both set.'];
        yield [fn () => new PDOProviderConfiguration(
            tableName: 'exchange_rate',
            exchangeRateColumnName: 'exchange_rate',
            sourceCurrencyCode: 'EUR',
            targetCurrencyCode: 'EUR',
        ), 'Invalid configuration: $sourceCurrencyCode and $targetCurrencyCode cannot be both set.'];
    }

    /**
     *
     * @param string       $sourceCurrencyCode The code of the source currency.
     * @param string       $targetCurrencyCode The code of the target currency.
     * @param float|string $expectedResult     The expected exchange rate, or an exception class if expected.
     */
    #[DataProvider('providerGetExchangeRate')]
    public function testGetExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode, float|string $expectedResult): void
    {
        $pdo = new PDO('sqlite::memory:');

        $pdo->query('
            CREATE TABLE exchange_rate (
                source_currency TEXT NOT NULL,
                target_currency TEXT NOT NULL,
                exchange_rate REAL NOT NULL
            )
        ');

        $statement = $pdo->prepare('INSERT INTO exchange_rate VALUES (?, ?, ?)');

        $statement->execute(['EUR', 'USD', 1.1]);
        $statement->execute(['USD', 'EUR', 0.9]);
        $statement->execute(['USD', 'CAD', 1.2]);

        $configuration = new PDOProviderConfiguration(
            tableName: 'exchange_rate',
            exchangeRateColumnName: 'exchange_rate',
            sourceCurrencyColumnName: 'source_currency',
            targetCurrencyColumnName: 'target_currency',
        );

        $provider = new PDOProvider($pdo, $configuration);

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualRate = $provider->getExchangeRate($sourceCurrencyCode, $targetCurrencyCode);

        if (!$this->isExceptionClass($expectedResult)) {
            self::assertEquals($expectedResult, $actualRate);
        }
    }

    public static function providerGetExchangeRate(): Iterator
    {
        yield ['USD', 'EUR', 0.9];
        yield ['EUR', 'USD', 1.1];
        yield ['USD', 'CAD', 1.2];
        yield ['CAD', 'USD', CurrencyConversionException::class];
        yield ['EUR', 'CAD', CurrencyConversionException::class];
    }

    /**
     *
     * @param string       $sourceCurrencyCode The code of the source currency.
     * @param string       $targetCurrencyCode The code of the target currency.
     * @param float|string $expectedResult     The expected exchange rate, or an exception class if expected.
     */
    #[DataProvider('providerWithFixedSourceCurrency')]
    public function testWithFixedSourceCurrency(string $sourceCurrencyCode, string $targetCurrencyCode, float|string $expectedResult): void
    {
        $pdo = new PDO('sqlite::memory:');

        $pdo->query('
            CREATE TABLE exchange_rate (
                target_currency TEXT NOT NULL,
                exchange_rate REAL NOT NULL
            )
        ');

        $statement = $pdo->prepare('INSERT INTO exchange_rate VALUES (?, ?)');

        $statement->execute(['USD', 1.1]);
        $statement->execute(['CAD', 1.2]);

        $configuration = new PDOProviderConfiguration(
            tableName: 'exchange_rate',
            exchangeRateColumnName: 'exchange_rate',
            sourceCurrencyCode: 'EUR',
            targetCurrencyColumnName: 'target_currency',
        );

        $provider = new PDOProvider($pdo, $configuration);

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualRate = $provider->getExchangeRate($sourceCurrencyCode, $targetCurrencyCode);

        if (!$this->isExceptionClass($expectedResult)) {
            self::assertEquals($expectedResult, $actualRate);
        }
    }

    public static function providerWithFixedSourceCurrency(): Iterator
    {
        yield ['EUR', 'USD', 1.1];
        yield ['EUR', 'CAD', 1.2];
        yield ['EUR', 'GBP', CurrencyConversionException::class];
        yield ['USD', 'EUR', CurrencyConversionException::class];
        yield ['CAD', 'EUR', CurrencyConversionException::class];
    }

    /**
     *
     * @param string       $sourceCurrencyCode The code of the source currency.
     * @param string       $targetCurrencyCode The code of the target currency.
     * @param float|string $expectedResult     The expected exchange rate, or an exception class if expected.
     */
    #[DataProvider('providerWithFixedTargetCurrency')]
    public function testWithFixedTargetCurrency(string $sourceCurrencyCode, string $targetCurrencyCode, float|string $expectedResult): void
    {
        $pdo = new PDO('sqlite::memory:');

        $pdo->query('
            CREATE TABLE exchange_rate (
                source_currency TEXT NOT NULL,
                exchange_rate REAL NOT NULL
            )
        ');

        $statement = $pdo->prepare('INSERT INTO exchange_rate VALUES (?, ?)');

        $statement->execute(['USD', 0.9]);
        $statement->execute(['CAD', 0.8]);

        $configuration = new PDOProviderConfiguration(
            tableName: 'exchange_rate',
            exchangeRateColumnName: 'exchange_rate',
            sourceCurrencyColumnName: 'source_currency',
            targetCurrencyCode: 'EUR',
        );

        $provider = new PDOProvider($pdo, $configuration);

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualRate = $provider->getExchangeRate($sourceCurrencyCode, $targetCurrencyCode);

        if (!$this->isExceptionClass($expectedResult)) {
            self::assertEquals($expectedResult, $actualRate);
        }
    }

    public static function providerWithFixedTargetCurrency(): Iterator
    {
        yield ['USD', 'EUR', 0.9];
        yield ['CAD', 'EUR', 0.8];
        yield ['GBP', 'EUR', CurrencyConversionException::class];
        yield ['EUR', 'USD', CurrencyConversionException::class];
        yield ['EUR', 'CAD', CurrencyConversionException::class];
    }

    /**
     *
     * @param string       $sourceCurrencyCode The code of the source currency.
     * @param string       $targetCurrencyCode The code of the target currency.
     * @param array        $parameters         The parameters to resolve the extra query placeholders.
     * @param float|string $expectedResult     The expected exchange rate, or an exception class if expected.
     */
    #[DataProvider('providerWithParameters')]
    public function testWithParameters(string $sourceCurrencyCode, string $targetCurrencyCode, array $parameters, float|string $expectedResult): void
    {
        $pdo = new PDO('sqlite::memory:');

        $pdo->query('
            CREATE TABLE exchange_rate (
                year INTEGER NOT NULL,
                month INTEGER NOT NULL,
                source_currency TEXT NOT NULL,
                target_currency TEXT NOT NULL,
                exchange_rate REAL NOT NULL
            )
        ');

        $statement = $pdo->prepare('INSERT INTO exchange_rate VALUES (?, ?, ?, ?, ?)');

        $statement->execute([2017, 8, 'EUR', 'USD', 1.1]);
        $statement->execute([2017, 8, 'EUR', 'CAD', 1.2]);
        $statement->execute([2017, 9, 'EUR', 'USD', 1.15]);
        $statement->execute([2017, 9, 'EUR', 'CAD', 1.25]);

        $configuration = new PDOProviderConfiguration(
            tableName: 'exchange_rate',
            exchangeRateColumnName: 'exchange_rate',
            sourceCurrencyColumnName: 'source_currency',
            targetCurrencyColumnName: 'target_currency',
            whereConditions: 'year = ? AND month = ?',
        );

        $provider = new PDOProvider($pdo, $configuration);
        $provider->setParameters(...$parameters);

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualRate = $provider->getExchangeRate($sourceCurrencyCode, $targetCurrencyCode);

        if (!$this->isExceptionClass($expectedResult)) {
            self::assertEquals($expectedResult, $actualRate);
        }
    }

    public static function providerWithParameters(): Iterator
    {
        yield ['EUR', 'USD', [2017, 8], 1.1];
        yield ['EUR', 'CAD', [2017, 8], 1.2];
        yield ['EUR', 'GBP', [2017, 8], CurrencyConversionException::class];
        yield ['EUR', 'USD', [2017, 9], 1.15];
        yield ['EUR', 'CAD', [2017, 9], 1.25];
        yield ['EUR', 'GBP', [2017, 9], CurrencyConversionException::class];
        yield ['EUR', 'USD', [2017, 10], CurrencyConversionException::class];
        yield ['EUR', 'CAD', [2017, 10], CurrencyConversionException::class];
    }
}
