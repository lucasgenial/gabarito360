# 00 — Regras do Projeto (Constituição)

Este documento é a constituição do projeto.
Nenhuma decisão técnica ou funcional pode contradizê-lo sem aprovação explícita.

---

## O Que Este Projeto É

O Gabarito360 é uma plataforma de gestão de avaliações educacionais para redes públicas de ensino.

Sua missão cobre o ciclo completo de avaliação:
cadastro de questões → montagem de provas → aplicação → leitura OMR → correção → relatórios pedagógicos.

---

## O Que Este Projeto Não É

- Não é uma evolução do Gabarito360 V1
- Não é uma evolução do Gabarito360 V2
- Não é uma refatoração do legado
- Não é um sistema de gestão escolar genérico

É uma **nova versão consolidada**, construída com base nos mockups e nesta documentação.

---

## Prioridade das Fontes de Verdade

```
1. Mockups HTML          (mockups/)
2. Style System          (style-system/)
3. Documentação oficial  (docs/)
4. Regras do legado      (somente regras de negócio validadas)
5. Código legado         (consulta histórica)
```

Em qualquer conflito, a fonte de maior prioridade prevalece, sem exceção.

---

## O Que Pode Ser Feito

- Criar código a partir de mockups inventariados em `docs/01-inventario-dos-mockups.md`
- Criar código a partir de funcionalidades aprovadas posteriormente e documentadas
- Consultar o legado para recuperar regras de negócio e fluxos validados
- Propor novas funcionalidades mediante documentação e aprovação

---

## O Que Não Pode Ser Feito

- Criar funcionalidade sem correspondência nos mockups ou aprovação documentada
- Reutilizar código do legado sem justificativa formal no documento de arquitetura
- Reutilizar arquitetura, estrutura de banco, modelos ou rotas do legado
- Misturar responsabilidades entre API e WEB
- Criar endpoints que retornem HTML (a API é exclusivamente JSON)
- Implementar funcionalidades fora do roadmap aprovado sem novo MP

---

## Arquitetura Oficial

### Banco de Dados
**MariaDB** — único banco de dados aprovado.

### Aplicações

| App     | Tecnologia   | Diretório    |
|---------|--------------|--------------|
| API     | Laravel      | apps/api     |
| WEB     | Laravel      | apps/web     |
| Android | React Native | apps/android |
| iOS     | React Native | apps/ios     |

A API é a única fonte de dados para WEB, Android e iOS.
A WEB nunca acessa o banco diretamente.

---

## Perfis Oficiais

Os seguintes perfis são os únicos reconhecidos pelo sistema:

1. **Administrador da Rede** — visão total da rede, todas as permissões
2. **Diretor de Núcleo** — gestão das escolas sob supervisão do núcleo
3. **Diretor Escolar** — gestão de uma escola específica
4. **Coordenador** — gestão pedagógica da escola
5. **Professor** — gestão das próprias turmas e provas
6. **Aluno** — visualização das próprias avaliações e notas

Nenhuma funcionalidade pode ser criada para perfis fora desta lista sem aprovação.

---

## Política de Reutilização do Legado

O legado pode ser consultado apenas para:

| Permitido                              | Proibido                        |
|----------------------------------------|---------------------------------|
| Regras de negócio validadas            | Arquitetura de código           |
| Fluxos funcionais aprovados            | Estrutura de banco de dados     |
| Decisões históricas documentadas       | Modelos Eloquent/ORM            |
|                                        | Rotas e controllers             |
|                                        | Código frontend (views/blades)  |
|                                        | Código backend                  |

Toda reutilização de regra de negócio deve ser documentada no arquivo correspondente em `docs/`.

---

## Política de Documentação

- Toda funcionalidade deve ser inventariada antes de ser implementada
- Todo endpoint da API deve estar documentado em `docs/10-rotas-api.md` antes de ser criado
- Toda rota do WEB deve estar documentada em `docs/11-rotas-web.md` antes de ser criada
- Toda entidade do banco deve estar no modelo de dados em `docs/09-modelo-de-dados.md`
- Decisões de arquitetura devem ser registradas em `docs/12-arquitetura.md`

---

## Política de Commits

```
tipo(escopo): descrição curta em pt-BR

Tipos obrigatórios:
  feat     → nova funcionalidade
  fix      → correção de bug
  docs     → documentação
  refactor → refatoração sem mudança de comportamento
  test     → testes
  chore    → tarefas de manutenção (deps, config, build)
  db       → migrations e seeds

Escopos:
  api, web, android, ios, docs, db, infra, omr, relatorio
```

Exemplos corretos:
```
feat(api): implementa autenticação com email institucional
feat(web): cria painel do coordenador com KPIs de provas
fix(api): corrige cálculo de média quando há questão anulada
docs: adiciona inventário completo dos mockups
db: migration para tabela de avaliações com suporte a versões
```

---

## Política de Testes

- Toda regra de negócio deve ter teste unitário
- Toda integração entre API e banco deve ter teste de integração
- Testes de UI devem cobrir o caminho principal de cada perfil
- Nenhum PR pode ser aprovado com cobertura abaixo do estabelecido no plano de execução

---

## Escopo Futuro Obrigatório

A arquitetura **deve prever**, mesmo que não implemente no MVP:

- Banco de Questões com organização por disciplina, habilidade e série
- Geração de Provas com múltiplas versões
- Embaralhamento de questões e alternativas
- Gabaritos individualizados por versão de prova
- Correção híbrida (OMR automático + revisão manual)
- OMR nativo (cartão gerado pelo sistema) e externo (cartão de terceiros)
- Relatórios pedagógicos avançados por aluno, turma, escola e rede
- Suporte a aplicativo mobile offline

---

## Violações

Qualquer violação das regras acima deve ser:
1. Documentada com justificativa técnica
2. Aprovada antes da implementação
3. Registrada em `docs/16-gap-analise.md`
