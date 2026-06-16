# Conexao local do backend a MariaDB em VM

## Objetivo

Permitir que a execucao local do backend Laravel utilize uma instancia MariaDB
externa hospedada em maquina virtual, via configuracao de ambiente em
`backend/.env`.

## Regras

- credenciais nao devem ser versionadas em arquivos de documentacao;
- a troca de ambiente local deve acontecer apenas em `backend/.env`;
- a validacao minima apos a troca deve confirmar:
  - alcance TCP ao host/porta;
  - autenticacao PDO;
  - leitura do estado de migrations pelo Artisan.

## Escopo desta configuracao

- altera somente o ambiente local do backend;
- nao modifica `compose`, `.env.example` ou ambientes de testes;
- nao executa migrations automaticamente no banco remoto.

## Validacao recomendada

```powershell
cd backend
php artisan config:clear
php artisan migrate:status
```

Se a conexao responder, o proximo passo fica sob decisao operacional: usar o
schema ja existente da VM ou aplicar migrations de forma controlada.
