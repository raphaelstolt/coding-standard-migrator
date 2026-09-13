<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Tests\Reader;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Stolt\CodingStandardMigrator\Exception\InvalidSourceConfiguration;
use Stolt\CodingStandardMigrator\Exception\SourceConfigurationNotFound;
use Stolt\CodingStandardMigrator\Reader\PhpCsFixerConfigurationReader;
use Stolt\CodingStandardMigrator\Standard\Standard;
use Stolt\CodingStandardMigrator\Tests\TestCase;

#[CoversClass(PhpCsFixerConfigurationReader::class)]
final class PhpCsFixerConfigurationReaderTest extends TestCase
{
    private PhpCsFixerConfigurationReader $reader;

    protected function setUp(): void
    {
        $this->reader = new PhpCsFixerConfigurationReader();
    }

    #[Test]
    public function readsAConfigurationObject(): void
    {
        $source = $this->reader->read($this->fixture('php-cs-fixer', '.php-cs-fixer.dist.php'));

        self::assertSame(Standard::PhpCsFixer, $source->standard);
        self::assertTrue($source->ruleset->has('@PSR12'));
        self::assertTrue($source->riskyAllowed);
        self::assertSame('    ', $source->indent);
        self::assertSame("\n", $source->lineEnding);
        self::assertFalse($source->usesTabs());
        self::assertSame(4, $source->indentWidth());
    }

    #[Test]
    public function readsAPlainRulesArray(): void
    {
        $source = $this->reader->read($this->fixture('php-cs-fixer-array', '.php-cs-fixer.php'));

        self::assertCount(2, $source->ruleset);
        self::assertNull($source->indent);
        self::assertFalse($source->riskyAllowed);
    }

    #[Test]
    public function looksForTheKnownConfigurationFileNames(): void
    {
        self::assertSame(
            ['.php-cs-fixer.php', '.php-cs-fixer.dist.php', '.php_cs', '.php_cs.dist'],
            $this->reader->configurationFileNames(),
        );
    }

    #[Test]
    public function failsOnAMissingConfigurationFile(): void
    {
        $this->expectException(SourceConfigurationNotFound::class);

        $this->reader->read($this->fixture('php-cs-fixer', 'does-not-exist.php'));
    }

    #[Test]
    public function failsOnAnUnexpectedReturnValue(): void
    {
        $this->expectException(InvalidSourceConfiguration::class);
        $this->expectExceptionMessage('getRules()');

        $this->reader->read($this->fixture('php-cs-fixer-invalid', '.php-cs-fixer.php'));
    }
}
