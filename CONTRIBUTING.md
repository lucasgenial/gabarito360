# Contribuindo com o Gabarito360

Este guia define as convencoes minimas para desenvolver, revisar e versionar o Gabarito360. Ele deve ser aplicado junto do `AGENTS.md`, da documentacao em `docs/` e do micropasso em execucao.

## 1. Principios de trabalho

- Trabalhar em um micropasso por vez, com alteracoes pequenas e demonstraveis.
- Ler a documentacao relacionada antes de alterar comportamento ou contrato.
- Nao ampliar o escopo do MVP sem atualizar a decisao correspondente.
- Nao apagar arquivos, dados ou historico sem necessidade e autorizacao explicitas.
- Implementar validacao, autorizacao, auditoria e testes junto da funcionalidade.
- Manter dados pessoais fora de logs, fixtures e mensagens de erro.
- Publicar eventos e disparar efeitos externos somente depois do commit da transacao.

## 2. Fluxo de contribuicao

1. Confirmar o micropasso, dependencias e restricoes aplicaveis.
2. Verificar o estado atual da documentacao, codigo e testes.
3. Implementar somente o menor incremento aceito pelo micropasso.
4. Executar formatacao, testes direcionados e verificacoes aplicaveis.
5. Revisar o diff para remover mudancas acidentais e dados sensiveis.
6. Executar a suite completa antes de abrir o pull request.
7. Criar um commit tematico e descrever riscos, verificacoes e pendencias no pull request.

## 3. Branches e commits

Branches devem usar nomes curtos em minusculas:

```text
tipo/descricao-curta
```

Tipos recomendados: `backend`, `painel`, `mobile`, `omr`, `documentacao`, `qualidade`, `seguranca`, `infraestrutura` e `correcao`.

Cada commit deve:

- representar uma mudanca coerente e revisavel;
- incluir os testes e a documentacao exigidos pela mudanca;
- evitar formatacao ou refatoracao sem relacao com o objetivo;
- usar mensagem em portugues no formato `tipo: descricao objetiva`.

Exemplos:

```text
backend: implementar gestao de nucleos
documentacao: definir convencoes de desenvolvimento
correcao: impedir confirmacao duplicada de cartao
```

## 4. Arquitetura Laravel

Fluxo recomendado para uma entrada HTTP:

```text
Route -> Form Request -> Policy -> Controller -> Action
      -> DTO / Model / Service -> Resource -> Response
```

Web e API devem reutilizar Actions, Policies e Services. Controllers e componentes Livewire nao podem ser a unica barreira de autorizacao nem duplicar regras de negocio.

### 4.1 Criterio para escolher a camada

| Camada | Usar quando | Nao deve |
|---|---|---|
| Controller | Adaptar HTTP, receber Request, invocar Policy/Action e devolver Resource ou resposta | Conter regra de negocio, consulta complexa ou transacao |
| Form Request | Validar formato, tipos, limites e coerencia dos dados de entrada | Persistir dados ou substituir Policy |
| Policy | Decidir se o usuario pode executar uma acao sobre um recurso e escopo | Alterar estado ou conter fluxo de caso de uso |
| Action | Executar um caso de uso unico e nomeado, coordenando regras, persistencia e transacao | Virar utilitario generico ou agrupar casos de uso independentes |
| Service | Oferecer capacidade reutilizavel, algoritmo ou integracao usada por mais de um caso de uso | Orquestrar endpoint completo ou esconder autorizacao |
| DTO | Transportar dados tipados entre fronteiras quando arrays perderiam clareza ou seguranca | Consultar banco, autorizar ou executar efeitos |
| Model | Representar persistencia, relacionamentos, casts e escopos locais simples | Orquestrar caso de uso ou chamar API externa |
| Resource | Definir e minimizar a representacao de saida da API | Consultar banco, autorizar ou alterar estado |
| Job | Executar trabalho assincrono idempotente, preferencialmente recebendo IDs | Depender de estado HTTP ou executar antes do commit |
| Observer | Reagir a evento de persistencia simples e nao critico | Esconder regra critica, auditoria obrigatoria ou efeito externo antes do commit |
| Support | Hospedar utilitario tecnico pequeno, estavel e sem regra de dominio | Virar deposito generico de regras |

### 4.2 Regras de implementacao

- Actions usam nomes de verbo e objeto, como `CreateNucleo`, `PublishProva` e `ConfirmReading`.
- Services usam nomes de capacidade, como `StudentCsvImporter` e `OmrProcessor`.
- Uma Action abre a transacao quando o caso de uso exige atomicidade.
- Restricoes do PostgreSQL sao a ultima barreira de integridade; a API converte conflitos esperados em erros estaveis.
- Jobs, eventos e notificacoes dependentes da persistencia devem ser enviados apos o commit.
- Queries de listagem devem declarar filtros, ordenacoes, paginacao e escopo permitidos.
- Resources devem expor somente os campos necessarios ao perfil e ao caso de uso.
- Evitar classes `Manager`, `Helper` ou `Utils` sem responsabilidade objetiva.

## 5. Convencoes da API

- O contrato funcional usa o prefixo `/api/v1` e segue [docs/07-api.md](docs/07-api.md).
- O endpoint tecnico `/api/health` permanece fora do versionamento enquanto a base da API e preparada.
- Novos endpoints nao devem criar envelopes JSON proprios.
- Codigos de erro estaveis usam `UPPER_SNAKE_CASE`.
- Datas usam ISO 8601 com fuso e IDs de dominio usam UUID.
- Operacoes repetiveis ou sensiveis a duplicidade devem aceitar idempotencia conforme o contrato.
- Autorizacao e escopo sao obrigatorios no backend, inclusive quando a interface oculta a acao.
- Mudanca incompativel de contrato exige nova versao ou estrategia explicita de compatibilidade.

## 6. Testes

### 6.1 Organizacao

- `tests/Unit`: regras puras, DTOs, Enums, algoritmos e Services sem infraestrutura real.
- `tests/Feature`: rotas, Requests, Policies, Actions com persistencia, Jobs e fluxos integrados.
- Testes de autorizacao devem cobrir permissao concedida, negada e tentativa fora do escopo.
- Regras de integridade devem cobrir caminho feliz, validacao, conflito e repeticao idempotente quando aplicavel.
- Toda correcao de defeito deve incluir teste que falharia antes da correcao.

### 6.2 Verificacao durante o desenvolvimento

Execute a partir de `backend/`:

```bash
vendor/bin/pint --test
php artisan test --filter=NomeDoTeste
```

Use `vendor/bin/pint` sem `--test` para aplicar formatacao quando necessario.

No PowerShell, use `vendor\bin\pint.bat --test` para verificar e `vendor\bin\pint.bat` para formatar.

### 6.3 Gate antes de commit

- Revisar `git diff --check` e o diff completo.
- Executar `vendor/bin/pint --test`.
- Executar os testes direcionados da alteracao.
- Executar `composer validate --strict` quando `composer.json` ou `composer.lock` mudar.
- Executar `php artisan route:list --except-vendor` quando rotas mudarem.
- Executar `npm run build` quando assets do painel mudarem.

### 6.4 Gate antes de pull request

Execute a partir de `backend/`:

```bash
composer validate --strict
vendor/bin/pint --test
php artisan test
```

No estado atual, `composer validate --strict`, Pint e a suite de testes formam a analise automatizada minima disponivel. Analise estatica com PHPStan ou ferramenta equivalente ainda nao esta configurada; ela somente se torna obrigatoria depois de ser adicionada em micropasso proprio e no pipeline de CI.

## 7. Checklist de revisao

- A alteracao atende somente ao micropasso e aos criterios de aceite?
- A camada escolhida respeita a tabela deste guia?
- Validacao, Policy e filtro de escopo existem onde aplicaveis?
- Transacoes, idempotencia e efeitos apos commit foram tratados?
- Resources, logs e erros minimizam dados pessoais?
- Testes cobrem sucesso, negacao e falhas relevantes?
- O contrato da API e a documentacao foram atualizados quando necessario?
- O diff esta livre de segredos, artefatos locais e alteracoes sem relacao?

## 8. Pull requests

O pull request deve informar:

- micropasso e objetivo atendidos;
- arquivos e contratos relevantes alterados;
- comandos de verificacao executados e resultados;
- riscos, decisoes e pendencias conhecidas;
- evidencias manuais quando houver mudanca visual ou operacional.

Um pull request nao deve misturar funcionalidades independentes, refatoracoes amplas ou preparacoes para etapas futuras sem justificativa documentada.
