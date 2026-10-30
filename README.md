# elavora/api-database-mysql

[![Packagist Version](https://img.shields.io/packagist/v/elavora/api-database-mysql.svg?style=flat-square)](https://packagist.org/packages/elavora/api-database-mysql)
[![PHP Version](https://img.shields.io/packagist/php-v/elavora/api-database-mysql.svg?style=flat-square)](https://packagist.org/packages/elavora/api-database-mysql)
[![Qualidade](https://github.com/Elavora/api-database-mysql/actions/workflows/quality.yml/badge.svg?branch=main)](https://github.com/Elavora/api-database-mysql/actions/workflows/quality.yml)
[![CodeQL](https://github.com/Elavora/api-database-mysql/actions/workflows/codeql.yml/badge.svg?branch=main)](https://github.com/Elavora/api-database-mysql/actions/workflows/codeql.yml)
[![License](https://img.shields.io/packagist/l/elavora/api-database-mysql.svg?style=flat-square)](LICENSE)

Extensão MySQL opcional para o framework Elavora, baseada em `elavora/api-database-pdo`.

- Versão: **1.0.0**. Dependentes devem exigir `"elavora/api-database-mysql": "^1.0"`.
- Exige `elavora/api-framework` `^2.0`, `elavora/api-database-pdo` `^1.0`, PHP `>=8.3` e `ext-pdo_mysql`.

Registre `MySqlExtension` com `host`, `port`, `database`, `charset`, `username`, `password`
e `options`. O pacote monta o DSN `mysql:host=...;port=...;dbname=...;charset=...` e o entrega ao
pdo. A chave `connections` habilita conexões nomeadas. Com `DB_DRIVER=mysql`, o framework cria a
extensão sozinho a partir das variáveis `DB_*`.

A especificação completa, com regras e exemplos, está em [docs/USO.md](docs/USO.md).

## Instalação

```bash
composer require elavora/api-database-mysql
```

Requisitos: PHP 8.3 ou superior, `ext-pdo_mysql`, `elavora/api-framework` `^2.0` e `elavora/api-database-pdo` `^1.0`.

## Desenvolvimento e testes

Você só precisa de Docker. O ambiente é a imagem `ghcr.io/elavora/php-dev:8.3-latest`: abra o repositório no VS Code
com **Dev Containers: Reopen in Container**, ou rode no PowerShell (a imagem não traz `pdo_mysql`, por isso o
`--ignore-platform-req`):

```powershell
docker run --rm --mount "type=bind,source=$($PWD.Path),target=/workspace" -w /workspace ghcr.io/elavora/php-dev:8.3-latest sh -lc 'git config --global --add safe.directory /workspace && composer install --no-interaction --prefer-dist --ignore-platform-req=ext-pdo_mysql && composer check'
```

Scripts do Composer:

```bash
composer check     # lint, testes e PHPStan
composer test      # testes PHPUnit
composer lint      # sintaxe (elavora-standards lint)
composer analyse   # PHPStan, nível 8
```

O código segue o padrão PER-CS, com as regras do pacote `elavora/code-standards`. No devcontainer, o arquivo é
formatado ao salvar; no PR, o CI formata o que faltou e commita a correção (`style: organiza o código`).
Os testes rodam no CI, no workflow **Qualidade**, em todo PR. O passo a passo, o que roda no CI e como reproduzi-lo
estão em [Desenvolvimento e testes](docs/DESENVOLVIMENTO.md). Os testes não precisam de MySQL: só o teste de
integração (grupo `integration`) usa um servidor real, e ele é pulado quando não há `MYSQL_HOST` ou `pdo_mysql`.

## Como contribuir

1. Crie uma branch a partir da `main` (`feat/...`, `fix/...` ou `docs/...`).
2. Faça commits em português do Brasil, no padrão Conventional Commits (`feat: ...`, `fix: ...`, `docs: ...`).
3. Rode `composer check` antes de abrir o PR.
4. Abra o PR para a `main`, preencha o template e aplique as labels que definem a versão
   ([Versões e release](docs/AUTOMACAO.md#versões-e-release)).
5. Confira o comentário do pipeline de qualidade no PR. Ele não bloqueia o merge, mas quem revisa olha o resultado.

## Versões

Cada PR integrado na `main` gera uma versão automaticamente, conforme as labels do PR: sem a label `release` sai uma
beta (`1.1.0-beta.1`), com ela sai a estável (`1.1.0`). A `1.0.0` sai do PR de upgrade e adoção do pipeline
(`upgrade` mais `release`, a partir da `0.2.0`). As tags novas não têm o prefixo `v`, e as notas de cada
GitHub Release são geradas em português a partir dos PRs. Veja [Versões e release](docs/AUTOMACAO.md#versões-e-release).

## Atualizando da 0.x

A 1.0.0 muda os requisitos (PHP 8.3, framework 2 e pdo 1) e torna `MySqlExtension::pdoConfig()` pública; o resto da
API do pacote é o da `v0.2.0`. Veja
[O que muda para quem usa](docs/USO.md#versão-e-compatibilidade).

## Documentação

1. [Guia de uso](docs/USO.md): a especificação do pacote, com a API pública e exemplos.
2. [Desenvolvimento e testes](docs/DESENVOLVIMENTO.md): ambiente, como rodar os testes e o que roda no CI.
3. [Automação do repositório](docs/AUTOMACAO.md): workflows, Dependabot, estilo de código, labels e versões.

## Licença

MIT
