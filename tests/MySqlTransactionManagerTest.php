<?php

declare(strict_types=1);

use Elavora\Api\Extension\DatabaseMySql\MySqlExtension;
use Elavora\Api\Extension\DatabasePdo\PdoDatabase;
use Elavora\Api\Framework\Application;
use Elavora\Api\Framework\Contracts\DatabaseConnectionFactory;
use Elavora\Api\Framework\Contracts\TransactionManager;
use PHPUnit\Framework\TestCase;

final class MySqlTransactionManagerTest extends TestCase
{
    public function testUsesDefaultConnectionAndRollsBackRealTransaction(): void
    {
        $application = Application::create()->extend(new MySqlExtension([
            'connections' => [
                'default' => $this->connectionConfig(),
                'analytics' => $this->connectionConfig(),
            ],
        ]));

        $factory = $application->container()->get(DatabaseConnectionFactory::class);
        $database = $application->container()->get(PdoDatabase::class);
        $transactionManager = $application->container()->get(TransactionManager::class);

        self::assertInstanceOf(DatabaseConnectionFactory::class, $factory);
        self::assertInstanceOf(PdoDatabase::class, $database);
        self::assertInstanceOf(TransactionManager::class, $transactionManager);
        self::assertSame($factory->connection(), $database->connection());
        self::assertSame($database, $transactionManager);
        self::assertNotSame($factory->connection(), $factory->connection('analytics'));

        $database->execute(
            'CREATE TEMPORARY TABLE transaction_probe (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL)'
        );
        self::assertTrue($transactionManager->begin());
        self::assertTrue($database->connection()->inTransaction());
        self::assertFalse($factory->connection('analytics')->inTransaction());
        $database->insert('transaction_probe', ['name' => 'rollback']);
        self::assertTrue($transactionManager->rollback());
        self::assertSame(0, (int) $database->value('SELECT COUNT(*) FROM transaction_probe'));
    }

    /**
     * @return array<string, mixed>
     */
    private function connectionConfig(): array
    {
        return [
            'host' => getenv('MYSQL_HOST') ?: 'mysql',
            'port' => (int) (getenv('MYSQL_PORT') ?: 3306),
            'database' => getenv('MYSQL_DATABASE') ?: 'api',
            'charset' => 'utf8mb4',
            'username' => getenv('MYSQL_USER') ?: 'api',
            'password' => getenv('MYSQL_PASSWORD') ?: 'api',
            'options' => [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        ];
    }
}
