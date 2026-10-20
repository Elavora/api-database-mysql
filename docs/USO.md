# Guia de uso

Extensão MySQL opcional para o framework Elavora, baseada em `elavora/api-database-pdo`.

Este pacote recebe host, porta, banco e credenciais, monta o DSN do MySQL e entrega tudo ao
`PdoConnectionFactory` do pacote pdo com a chave `dsn`.

## Versão e compatibilidade

| Item | Valor |
| --- | --- |
| Versão deste pacote | **1.0.0** (sai do PR com label `upgrade`, que sobe a major a partir da `v0.2.0`) |
| `elavora/api-database-pdo` exigido | `^1.0` |
| `elavora/api-framework` exigido | `^2.0` |
| PHP exigido | `>=8.3` (o mesmo mínimo do framework 2.0) |
| Extensão exigida | `ext-pdo_mysql` `*` |

O `composer.json` do pacote declara exatamente:

```json
"require": {
    "php": ">=8.3",
    "ext-pdo_mysql": "*",
    "elavora/api-database-pdo": "^1.0",
    "elavora/api-framework": "^2.0"
}
```

e o alias da `main` é `"dev-main": "1.0-dev"` em `extra.branch-alias`.
O `require-dev` continua `"phpunit/phpunit": "^10.5 || ^11.0 || ^12.0"`.

A versão 1.0.0 não aceita mais o framework `0.x`, o pdo `0.x` nem PHP 8.1/8.2.

## Instalação

```bash
composer require elavora/api-database-mysql:^1.0
```

## API pública

Namespace: `Elavora\Api\Extension\DatabaseMySql`.

### `MySqlExtension implements Extension`

Classe `final`. `Extension` é `Elavora\Api\Framework\Contracts\Extension`.

```php
public function __construct(array $config)
public function register(Application $application): void
public function pdoConfig(): array
```

- O construtor só guarda `$config`. Não valida e não lança nada.
- `pdoConfig()` devolve a configuração que vai para o `PdoConnectionFactory`, já com o `dsn` montado.
  É pública para a aplicação (e os testes) conferirem o DSN sem abrir conexão.
  Lança `InvalidArgumentException` nos casos da tabela de erros abaixo.
- `register()` chama `pdoConfig()` e registra no container da aplicação
  `DatabaseConnectionFactory::class` (de `Elavora\Api\Framework\Contracts`) com um
  `Elavora\Api\Extension\DatabasePdo\PdoConnectionFactory` criado com esse array.
  Por isso `register()` lança as mesmas exceções de `pdoConfig()`, já na hora do `extend()`.
- `register()` não abre conexão. A conexão só abre em `connection()` da factory.

### Formatos de configuração

Conexão única (sem a chave `connections`, ou com ela `null`):

```php
new MySqlExtension([
    'host' => 'mysql',        // obrigatório, string não vazia
    'port' => 3306,           // opcional, padrão 3306
    'database' => 'app',      // obrigatório, string não vazia
    'charset' => 'utf8mb4',   // opcional, padrão 'utf8mb4'
    'username' => 'app',      // opcional, repassado ao pdo como está
    'password' => 'segredo',  // opcional, repassado ao pdo como está
    'options' => [],          // opcional, repassado ao pdo como está
]);
```

Várias conexões nomeadas (chave `connections` com um mapa nome => configuração):

```php
new MySqlExtension([
    'connections' => [
        'default' => ['host' => 'mysql', 'database' => 'app'],
        'relatorios' => ['host' => 'replica', 'port' => 3307, 'database' => 'app'],
    ],
]);
```

Cada conexão segue as mesmas regras da conexão única.
As regras de nomes (`null` = `'default'`, conexão inexistente etc.) são do pdo; veja a doc dele.

### Montagem do DSN

Formato:

```
mysql:host=<host>;port=<port>;dbname=<database>;charset=<charset>
```

Regras:

1. `host` e `database` precisam ser strings não vazias. Não há `trim`.
2. `port` é convertida com `(int)`. Sem `port` (ou com `null`), vale `3306`.
   Inteiro e string numérica (`'3307'`) são o contrato; outros valores ficam fora dele.
3. `charset` é convertido com `(string)`. Sem `charset` (ou com `null`), vale `'utf8mb4'`.
4. O array devolvido é o de entrada com a chave `dsn` acrescentada.
   As outras chaves (`username`, `password`, `options`, e também `host`, `port`, `database`,
   `charset` e qualquer chave extra) continuam lá sem mudança.
5. Se a entrada já tiver `dsn`, ele é substituído pelo montado.
6. A ordem das chaves não faz parte do contrato. Nos testes, compare com `assertEquals`
   ou chave a chave.

Exemplos de `(new MySqlExtension($config))->pdoConfig()`:

| `$config` | `pdoConfig()` |
| --- | --- |
| `['host' => 'mysql', 'database' => 'app']` | `['host' => 'mysql', 'database' => 'app', 'dsn' => 'mysql:host=mysql;port=3306;dbname=app;charset=utf8mb4']` |
| `['host' => 'db', 'port' => 3307, 'database' => 'loja', 'charset' => 'latin1']` | mesmas chaves + `'dsn' => 'mysql:host=db;port=3307;dbname=loja;charset=latin1'` |
| `['host' => 'db', 'port' => '3310', 'database' => 'x']` | `dsn` = `'mysql:host=db;port=3310;dbname=x;charset=utf8mb4'` (a chave `port` continua `'3310'`) |
| `['host' => 'db', 'database' => 'x', 'username' => 'u', 'password' => 'p', 'options' => [1002 => 'SET NAMES utf8mb4']]` | as cinco chaves sem mudança + `dsn` = `'mysql:host=db;port=3306;dbname=x;charset=utf8mb4'` |
| `['host' => 'db', 'database' => 'x', 'dsn' => 'sqlite::memory:']` | `dsn` = `'mysql:host=db;port=3306;dbname=x;charset=utf8mb4'` |
| `['connections' => ['default' => ['host' => 'a', 'database' => 'x'], 'r' => ['host' => 'b', 'port' => 3307, 'database' => 'y']]]` | `['connections' => ['default' => [...+ 'dsn' => 'mysql:host=a;port=3306;dbname=x;charset=utf8mb4'], 'r' => [...+ 'dsn' => 'mysql:host=b;port=3307;dbname=y;charset=utf8mb4']]]` |
| `['connections' => []]` | `['connections' => []]` |

Com `connections`, o array devolvido tem **só** a chave `connections`; outras chaves da raiz
são descartadas.

### Erros

Todos são `InvalidArgumentException`, lançados por `pdoConfig()` e por `register()`.

| `$config` | Mensagem |
| --- | --- |
| `[]` | `A configuracao MySQL deve informar host e database.` |
| `['database' => 'app']` | `A configuracao MySQL deve informar host e database.` |
| `['host' => 'mysql']` | `A configuracao MySQL deve informar host e database.` |
| `['host' => '', 'database' => 'app']` | `A configuracao MySQL deve informar host e database.` |
| `['host' => 'mysql', 'database' => '']` | `A configuracao MySQL deve informar host e database.` |
| `['host' => 123, 'database' => 'app']` | `A configuracao MySQL deve informar host e database.` |
| `['connections' => ['r' => 'mysql://x']]` | `Configuracao MySQL 'r' deve ser um array.` |
| `['connections' => ['r' => ['host' => 'b']]]` | `A configuracao MySQL deve informar host e database.` |

Fora do contrato (não teste): `connections` com valor que não é array nem `null`.

### Uso com as variáveis `DB_*` do framework

O `Elavora\Api\Framework\Bootstrap\EnvironmentExtensions::load($basePath, $environment)` do
framework 2.0 cria a `MySqlExtension` quando `DB_DRIVER=mysql`. Ele passa este array:

| Chave | Origem | Padrão |
| --- | --- | --- |
| `host` | `DB_HOST` | `'mysql'` |
| `port` | `DB_PORT` convertido para int | `3306` |
| `database` | `DB_DATABASE` | `'app'` |
| `username` | `DB_USERNAME` | `'app'` |
| `password` | `DB_PASSWORD` | `''` |

Não há `DB_CHARSET`: o charset fica no padrão `utf8mb4`.

Exemplos (o segundo argumento de `load()` é o ambiente; o resultado é uma lista com a extensão):

| Ambiente | `pdoConfig()['dsn']` da `MySqlExtension` devolvida |
| --- | --- |
| `['DB_DRIVER' => 'mysql']` | `'mysql:host=mysql;port=3306;dbname=app;charset=utf8mb4'` |
| `['DB_DRIVER' => 'mysql', 'DB_HOST' => 'db', 'DB_PORT' => '3307', 'DB_DATABASE' => 'loja']` | `'mysql:host=db;port=3307;dbname=loja;charset=utf8mb4'` |

No segundo caso, `pdoConfig()['username']` é `'app'` e `pdoConfig()['password']` é `''`.

## Exemplo rápido

```php
use Elavora\Api\Extension\DatabaseMySql\MySqlExtension;
use Elavora\Api\Framework\Application;
use Elavora\Api\Framework\Contracts\DatabaseConnectionFactory;

$application = Application::create()->extend(new MySqlExtension([
    'host' => getenv('DB_HOST') ?: 'mysql',
    'port' => (int) (getenv('DB_PORT') ?: 3306),
    'database' => getenv('DB_DATABASE') ?: 'app',
    'username' => getenv('DB_USERNAME') ?: 'app',
    'password' => getenv('DB_PASSWORD') ?: '',
]));

$pdo = $application->container()->get(DatabaseConnectionFactory::class)->connection();
```

## Desenvolvimento e testes

### Testes unitários (sem MySQL)

Cobrem tudo o que está em "API pública" sem abrir conexão:

- `pdoConfig()`: DSN, padrões, chaves repassadas, `connections` e erros.
- `register()`: depois de `Application::create()->extend(new MySqlExtension([...]))`,
  `container()->get(DatabaseConnectionFactory::class)` é instância de `PdoConnectionFactory`.
- `register()` com configuração inválida lança a `InvalidArgumentException` da tabela de erros.
- `EnvironmentExtensions::load()` com `DB_DRIVER=mysql`, conforme a tabela acima.

### Teste de integração (precisa de MySQL real)

Abrir conexão e rodar `SELECT 1` precisa de um servidor MySQL. Esse teste fica no grupo
PHPUnit `integration` e é pulado (`markTestSkipped`) quando a variável `MYSQL_HOST` não está definida
ou quando a extensão `pdo_mysql` não está carregada.
Ele usa `MYSQL_HOST`, `MYSQL_PORT` (padrão `3306`), banco `api`, usuário `api` e senha `api`.

### Como rodar

Pelo PowerShell, no container da organização, montando o repo em `/origem`:

```powershell
docker run --rm -v "${PWD}:/origem:ro" ghcr.io/elavora/php-dev:8.3-latest sh -c "cp -r /origem /tmp/w && cd /tmp/w && composer install --no-interaction --ignore-platform-req=ext-pdo_mysql && composer validate --strict --no-check-publish && vendor/bin/phpunit"
```

A imagem `php-dev:8.3-latest` não tem a extensão `pdo_mysql`. Por isso o `composer install`
usa `--ignore-platform-req=ext-pdo_mysql`. Os testes unitários não precisam dela,
e o teste de integração é pulado.

### Antes do pdo 1.0.0 sair no Packagist

Enquanto `elavora/api-database-pdo` 1.0.0 não estiver publicado, o `composer install` acima falha.
Nesse meio-tempo, valide com o pdo da branch `feat/framework-2` do clone local, por um repositório
`path` adicionado só dentro do container (nada disso é commitado).
Rode a partir de `D:\Felipe\Dev\Elavora`:

```powershell
docker run --rm -v "${PWD}\api-database-mysql:/origem:ro" -v "${PWD}\api-database-pdo:/pdo:ro" ghcr.io/elavora/php-dev:8.3-latest sh -c "git config --global --add safe.directory '*' && git clone -q --branch feat/framework-2 /pdo /tmp/pdo && cp -r /origem /tmp/w && cd /tmp/w && composer config repositories.pdo '{\"type\":\"path\",\"url\":\"/tmp/pdo\",\"options\":{\"symlink\":false,\"versions\":{\"elavora/api-database-pdo\":\"1.0.0\"}}}' && composer install --no-interaction --ignore-platform-req=ext-pdo_mysql && vendor/bin/phpunit"
```

- O clone usa o que está **commitado** na branch `feat/framework-2` do pdo, não a cópia de trabalho.
- O `composer validate` fica de fora aqui, porque o `composer.json` da cópia ganhou o repositório `path`.
- O `composer.json` commitado neste repo não tem `repositories`.

### Ordem de merge

1. PR do `api-database-pdo` (label `upgrade`) é aceito e a tag `v1.0.0` sai no Packagist.
2. Rodar de novo a validação normal (sem `path`) nesta branch.
3. Só então aceitar o PR deste pacote, com label `upgrade` (sai a `v1.0.0`).

## Observações

- Mantenha regras de produto fora deste pacote.
- Prefira configurar extensões no bootstrap da aplicação.
- Instale apenas os módulos que a aplicação realmente usa.
