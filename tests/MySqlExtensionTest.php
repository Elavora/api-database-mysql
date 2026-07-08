<?php

declare(strict_types=1);

use Elavora\Api\Extension\DatabaseMySql\MySqlExtension;
use Elavora\Api\Framework\Application;
use Elavora\Api\Framework\Contracts\DatabaseConnectionFactory;
use PHPUnit\Framework\TestCase;

final class MySqlExtensionTest extends TestCase
{
    public function testConnectsUsingMySqlExtension(): void
    {
        $application = Application::create()->extend(new MySqlExtension([
            'host' => getenv('MYSQL_HOST') ?: 'mysql',
            'port' => (int) (getenv('MYSQL_PORT') ?: 3306),
            'database' => 'api',
            'username' => 'api',
            'password' => 'api',
        ]));
        $factory = $application->container()->get(DatabaseConnectionFactory::class);

        self::assertSame(1, (int) $factory->connection()->query('SELECT 1')->fetchColumn());
    }
}
