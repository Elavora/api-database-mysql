<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ComposerJsonTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private static function composer(): array
    {
        $json = file_get_contents(dirname(__DIR__, 2) . '/composer.json');
        self::assertIsString($json);

        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($data);

        return $data;
    }

    public function testRequire(): void
    {
        self::assertEquals([
            'php' => '>=8.3',
            'ext-pdo_mysql' => '*',
            'elavora/api-database-pdo' => '^1.0',
            'elavora/api-framework' => '^2.0',
        ], self::composer()['require'] ?? null);
    }

    public function testRequireDev(): void
    {
        self::assertSame(
            '^10.5 || ^11.0 || ^12.0',
            self::composer()['require-dev']['phpunit/phpunit'] ?? null,
        );
    }

    public function testBranchAlias(): void
    {
        self::assertSame('1.0-dev', self::composer()['extra']['branch-alias']['dev-main'] ?? null);
    }
}
