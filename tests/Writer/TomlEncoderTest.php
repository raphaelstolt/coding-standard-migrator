<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Tests\Writer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Stolt\CodingStandardMigrator\Tests\TestCase;
use Stolt\CodingStandardMigrator\Writer\TomlEncoder;
use UnexpectedValueException;

#[CoversClass(TomlEncoder::class)]
final class TomlEncoderTest extends TestCase
{
    private TomlEncoder $encoder;

    protected function setUp(): void
    {
        $this->encoder = new TomlEncoder();
    }

    #[Test]
    #[DataProvider('valueProvider')]
    public function encodesValues(mixed $value, string $expected): void
    {
        self::assertSame($expected, $this->encoder->value($value));
    }

    /**
     * @return iterable<string, array{0: mixed, 1: string}>
     */
    public static function valueProvider(): iterable
    {
        yield 'boolean' => [true, 'true'];
        yield 'integer' => [120, '120'];
        yield 'float' => [1.5, '1.5'];
        yield 'float without fraction' => [2.0, '2.0'];
        yield 'string' => ['psr-12', '"psr-12"'];
        yield 'string with quotes' => ['a "b"', '"a \"b\""'];
        yield 'list' => [['src', 'tests'], '["src", "tests"]'];
        yield 'inline table' => [['enabled' => true], '{ enabled = true }'];
        yield 'empty inline table' => [[], '[]'];
    }

    #[Test]
    public function encodesKeyValuePairs(): void
    {
        self::assertSame('print-width = 120', $this->encoder->keyValue('print-width', 120));
        self::assertSame('halstead = { effort-threshold = 7000 }', $this->encoder->keyValue('halstead', [
            'effort-threshold' => 7000,
        ]));
        self::assertSame('{}', $this->encoder->inlineTable([]));
    }

    #[Test]
    public function failsOnUnencodableValues(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->encoder->value(new \stdClass());
    }
}
