<?php

declare(strict_types=1);

use Elavora\Api\Extension\DatabaseMySql\MySqlExtension;
use Elavora\Api\Framework\Bootstrap\EnvironmentExtensions;
use PHPUnit\Framework\TestCase;

final class EnvironmentExtensionsMySqlTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/elavora-mysql-env-' . bin2hex(random_bytes(6));
        mkdir($this->basePath);
    }

    protected function tearDown(): void
    {
        @rmdir($this->basePath);
    }

    /**
     * @param array<string, string> $environment
     */
    private function loadMySqlExtension(array $environment): MySqlExtension
    {
        $extensions = EnvironmentExtensions::load($this->basePath, $environment);
        self::assertIsArray($extensions);

        $mysql = array_values(array_filter(
            $extensions,
            static fn (mixed $extension): bool => $extension instanceof MySqlExtension,
        ));
        self::assertCount(1, $mysql);

        return $mysql[0];
    }

    public function testDefaults(): void
    {
        $config = $this->loadMySqlExtension(['DB_DRIVER' => 'mysql'])->pdoConfig();

        self::assertSame('mysql:host=mysql;port=3306;dbname=app;charset=utf8mb4', $config['dsn']);
        self::assertSame('mysql', $config['host']);
        self::assertSame(3306, $config['port']);
        self::assertSame('app', $config['database']);
        self::assertSame('app', $config['username']);
        self::assertSame('', $config['password']);
    }

    public function testUsesDbVariables(): void
    {
        $config = $this->loadMySqlExtension([
            'DB_DRIVER' => 'mysql',
            'DB_HOST' => 'db',
            'DB_PORT' => '3307',
            'DB_DATABASE' => 'loja',
        ])->pdoConfig();

        self::assertSame('mysql:host=db;port=3307;dbname=loja;charset=utf8mb4', $config['dsn']);
        self::assertSame(3307, $config['port']);
        self::assertSame('app', $config['username']);
        self::assertSame('', $config['password']);
    }

    public function testUsesCredentialVariables(): void
    {
        $config = $this->loadMySqlExtension([
            'DB_DRIVER' => 'mysql',
            'DB_USERNAME' => 'usuario',
            'DB_PASSWORD' => 'segredo',
        ])->pdoConfig();

        self::assertSame('usuario', $config['username']);
        self::assertSame('segredo', $config['password']);
    }
}
