<?php

declare(strict_types=1);

use Elavora\Api\Extension\DatabaseMySql\MySqlExtension;
use Elavora\Api\Extension\DatabasePdo\PdoConnectionFactory;
use Elavora\Api\Framework\Application;
use Elavora\Api\Framework\Contracts\DatabaseConnectionFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MySqlExtensionRegisterTest extends TestCase
{
    public function testRegistersPdoConnectionFactoryWithoutConnecting(): void
    {
        // Host inexistente: register() nao abre conexao, entao nada falha aqui.
        $application = Application::create()->extend(new MySqlExtension([
            'host' => 'host-inexistente.invalid',
            'database' => 'app',
            'username' => 'app',
            'password' => 'app',
        ]));

        self::assertInstanceOf(
            PdoConnectionFactory::class,
            $application->container()->get(DatabaseConnectionFactory::class),
        );
    }

    public function testRegistersPdoConnectionFactoryWithNamedConnections(): void
    {
        $application = Application::create()->extend(new MySqlExtension([
            'connections' => [
                'default' => ['host' => 'a.invalid', 'database' => 'x'],
                'relatorios' => ['host' => 'b.invalid', 'port' => 3307, 'database' => 'y'],
            ],
        ]));

        self::assertInstanceOf(
            PdoConnectionFactory::class,
            $application->container()->get(DatabaseConnectionFactory::class),
        );
    }

    /**
     * @return iterable<string, array{array<mixed>, string}>
     */
    public static function invalidConfigs(): iterable
    {
        $missing = 'A configuracao MySQL deve informar host e database.';

        yield 'vazio' => [[], $missing];
        yield 'host vazio' => [['host' => '', 'database' => 'app'], $missing];
        yield 'conexao nomeada nao array' => [['connections' => ['r' => 'mysql://x']], "Configuracao MySQL 'r' deve ser um array."];
        yield 'conexao nomeada sem database' => [['connections' => ['r' => ['host' => 'b']]], $missing];
    }

    /**
     * @param array<mixed> $config
     */
    #[DataProvider('invalidConfigs')]
    public function testExtendThrowsOnInvalidConfig(array $config, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        Application::create()->extend(new MySqlExtension($config));
    }
}
