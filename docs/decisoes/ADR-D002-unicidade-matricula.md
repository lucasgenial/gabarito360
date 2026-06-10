# ADR-D002 - Unicidade da matricula

- **Status:** aceita para o MVP
- **Data:** 2026-06-10
- **Responsaveis:** Produto + Backend
- **Prazo:** resolvida; aplicar na primeira migration de alunos

## Contexto

A mesma matricula pode existir em escolas diferentes. Exigir unicidade no nucleo criaria conflitos operacionais e acoplamento desnecessario entre escolas.

## Decisao

A matricula do aluno sera unica por escola, comparada de forma normalizada e sem diferenca entre maiusculas e minusculas.

No PostgreSQL, a regra recomendada e um indice unico parcial equivalente a:

```sql
UNIQUE (escola_id, lower(matricula)) WHERE deleted_at IS NULL
```

A matricula nao sera reutilizada para outro aluno enquanto o registro original existir. Transferencias preservam historico; transferencia entre escolas deve ser tratada como processo administrativo auditado.

## Justificativa

O escopo escolar atende a operacao real, evita colisoes entre escolas e mantem isolamento organizacional.

## Impactos

- Importacoes devem validar duplicidade dentro da escola.
- Buscas e validacoes devem normalizar espacos e caixa.
- Integracoes futuras que exigirem identificador global devem usar outro campo, nao alterar o significado da matricula.
