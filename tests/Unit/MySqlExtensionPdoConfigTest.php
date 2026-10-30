<?php

declare(strict_types=1);

use Elavora\Api\Extension\DatabaseMySql\MySqlExtension;
use Elavora\Api\Framework\Contracts\Extension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MySqlExtensionPdoConfigTest extends TestCase
{
    public function testIsFinalAndImplementsExtension(): void
    {
        $reflection = new ReflectionClass(MySqlExtension::class);

        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->implementsInterface(Extension::class));
    }

    public function testConstructorDoesNotValidate(): void
    {
        $extension = new MySqlExtension([]);

        self::assertInstanceOf(MySqlExtension::class, $extension);
    }

    /**
     * @return iterable<string, array{array<mixed>, array<mixed>}>
     */
    public static function validConfigs(): iterable
    {
        yield 'padroes de porta e charset' => [
            ['host' => 'mysql', 'database' => 'app'],
            ['host' => 'mysql', 'database' => 'app', 'dsn' => 'mysql:host=mysql;port=3306;dbname=app;charset=utf8mb4'],
        ];

        yield 'porta e charset informados' => [
            ['host' => 'db', 'port' => 3307, 'database' => 'loja', 'charset' => 'latin1'],
            ['host' => 'db', 'port' => 3307, 'database' => 'loja', 'charset' => 'latin1', 'dsn' => 'mysql:host=db;port=3307;dbname=loja;charset=latin1'],
        ];

        yield 'porta como string numerica' => [
            ['host' => 'db', 'port' => '3310', 'database' => 'x'],
            ['host' => 'db', 'port' => '3310', 'database' => 'x', 'dsn' => 'mysql:host=db;port=3310;dbname=x;charset=utf8mb4'],
        ];

        yield 'porta e charset null usam padrao' => [
            ['host' => 'db', 'port' => null, 'database' => 'x', 'charset' => null],
            ['host' => 'db', 'port' => null, 'database' => 'x', 'charset' => null, 'dsn' => 'mysql:host=db;port=3306;dbname=x;charset=utf8mb4'],
        ];

        yield 'credenciais e options repassadas' => [
            ['host' => 'db', 'database' => 'x', 'username' => 'u', 'password' => 'p', 'options' => [1002 => 'SET NAMES utf8mb4']],
            ['host' => 'db', 'database' => 'x', 'username' => 'u', 'password' => 'p', 'options' => [1002 => 'SET NAMES utf8mb4'], 'dsn' => 'mysql:host=db;port=3306;dbname=x;charset=utf8mb4'],
        ];

        yield 'chave extra preservada' => [
            ['host' => 'db', 'database' => 'x', 'extra' => 'valor'],
            ['host' => 'db', 'database' => 'x', 'extra' => 'valor', 'dsn' => 'mysql:host=db;port=3306;dbname=x;charset=utf8mb4'],
        ];

        yield 'dsn existente substituido' => [
            ['host' => 'db', 'database' => 'x', 'dsn' => 'sqlite::memory:'],
            ['host' => 'db', 'database' => 'x', 'dsn' => 'mysql:host=db;port=3306;dbname=x;charset=utf8mb4'],
        ];

        yield 'host sem trim' => [
            ['host' => ' db ', 'database' => 'x'],
            ['host' => ' db ', 'database' => 'x', 'dsn' => 'mysql:host= db ;port=3306;dbname=x;charset=utf8mb4'],
        ];

        yield 'connections nomeadas' => [
            ['connections' => [
                'default' => ['host' => 'a', 'database' => 'x'],
                'r' => ['host' => 'b', 'port' => 3307, 'database' => 'y'],
            ]],
            ['connections' => [
                'default' => ['host' => 'a', 'database' => 'x', 'dsn' => 'mysql:host=a;port=3306;dbname=x;charset=utf8mb4'],
                'r' => ['host' => 'b', 'port' => 3307, 'database' => 'y', 'dsn' => 'mysql:host=b;port=3307;dbname=y;charset=utf8mb4'],
            ]],
        ];

        yield 'connections vazio' => [
            ['connections' => []],
            ['connections' => []],
        ];

        yield 'connections descarta chaves da raiz' => [
            ['host' => 'raiz', 'database' => 'raiz', 'username' => 'u', 'connections' => [
                'default' => ['host' => 'a', 'database' => 'x', 'charset' => 'latin1'],
            ]],
            ['connections' => [
                'default' => ['host' => 'a', 'database' => 'x', 'charset' => 'latin1', 'dsn' => 'mysql:host=a;port=3306;dbname=x;charset=latin1'],
            ]],
        ];

        yield 'connections null usa conexao unica' => [
            ['host' => 'db', 'database' => 'x', 'connections' => null],
            ['host' => 'db', 'database' => 'x', 'connections' => null, 'dsn' => 'mysql:host=db;port=3306;dbname=x;charset=utf8mb4'],
        ];
    }

    /**
     * @param array<mixed> $config
     * @param array<mixed> $expected
     */
    #[DataProvider('validConfigs')]
    public function testBuildsPdoConfig(array $config, array $expected): void
    {
        self::assertEquals($expected, (new MySqlExtension($config))->pdoConfig());
    }

    /**
     * @return iterable<string, array{array<mixed>, string}>
     */
    public static function invalidConfigs(): iterable
    {
        $missing = 'A configuracao MySQL deve informar host e database.';

        yield 'vazio' => [[], $missing];
        yield 'sem host' => [['database' => 'app'], $missing];
        yield 'sem database' => [['host' => 'mysql'], $missing];
        yield 'host vazio' => [['host' => '', 'database' => 'app'], $missing];
        yield 'database vazio' => [['host' => 'mysql', 'database' => ''], $missing];
        yield 'host nao string' => [['host' => 123, 'database' => 'app'], $missing];
        yield 'conexao nomeada nao array' => [['connections' => ['r' => 'mysql://x']], "Configuracao MySQL 'r' deve ser um array."];
        yield 'conexao nomeada sem database' => [['connections' => ['r' => ['host' => 'b']]], $missing];
    }

    /**
     * @param array<mixed> $config
     */
    #[DataProvider('invalidConfigs')]
    public function testRejectsInvalidConfig(array $config, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        (new MySqlExtension($config))->pdoConfig();
    }
}
