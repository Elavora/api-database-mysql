<?php

declare(strict_types=1);

use Elavora\Api\Extension\DatabaseMySql\MySqlExtension;
use Elavora\Api\Framework\Application;
use Elavora\Api\Framework\Contracts\DatabaseConnectionFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
final class MySqlConnectionTest extends TestCase
{
    protected function setUp(): void
    {
        if (getenv('MYSQL_HOST') === false || getenv('MYSQL_HOST') === '') {
            self::markTestSkipped('MYSQL_HOST nao definido.');
        }

        if (!extension_loaded('pdo_mysql')) {
            self::markTestSkipped('Extensao pdo_mysql nao carregada.');
        }
    }

    public function testConnectsUsingMySqlExtension(): void
    {
        $application = Application::create()->extend(new MySqlExtension([
            'host' => (string) getenv('MYSQL_HOST'),
            'port' => (int) (getenv('MYSQL_PORT') ?: 3306),
            'database' => 'api',
            'username' => 'api',
            'password' => 'api',
        ]));
        $factory = $application->container()->get(DatabaseConnectionFactory::class);

        self::assertSame(1, (int) $factory->connection()->query('SELECT 1')->fetchColumn());
    }
}
