<?php

declare(strict_types=1);

namespace Stolt\CodingStandardMigrator\Tests;

use PHPUnit\Framework\TestCase as PhpUnitTestCase;

abstract class TestCase extends PhpUnitTestCase
{
    protected function fixtureDirectory(string $fixture): string
    {
        return __DIR__ . '/fixtures/' . $fixture;
    }

    protected function fixture(string $fixture, string $file): string
    {
        return $this->fixtureDirectory($fixture) . '/' . $file;
    }

    protected function createTemporaryDirectory(): string
    {
        $directory = \sys_get_temp_dir() . '/coding-standard-migrator-' . \bin2hex(\random_bytes(6));
        \mkdir($directory);

        return $directory;
    }

    protected function removeDirectory(string $directory): void
    {
        if (!\is_dir($directory)) {
            return;
        }

        $entries = \scandir($directory);

        if ($entries === false) {
            return;
        }

        foreach (\array_diff($entries, ['.', '..']) as $entry) {
            $file = $directory . \DIRECTORY_SEPARATOR . $entry;
            \is_dir($file) ? $this->removeDirectory($file) : \unlink($file);
        }

        \rmdir($directory);
    }
}
