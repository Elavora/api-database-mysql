# elavora/api-database-mysql

Extensão MySQL opcional para o framework Elavora, baseada em `elavora/api-database-pdo`.

A versão 1.0.0 exige `elavora/api-framework` `^2.0`, `elavora/api-database-pdo` `^1.0` e PHP `>=8.3`.

Registre `MySqlExtension` com `host`, `port`, `database`, `charset`, `username`, `password`
e `options`. O pacote monta o DSN `mysql:host=...;port=...;dbname=...;charset=...` e o entrega ao
pdo. A chave `connections` habilita conexões nomeadas. Com `DB_DRIVER=mysql`, o framework cria a
extensão sozinho a partir das variáveis `DB_*`.

Detalhes, exemplos e como testar: [docs/USO.md](docs/USO.md).
