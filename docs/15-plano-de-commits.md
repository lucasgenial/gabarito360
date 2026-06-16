# 15 — Plano de Commits

---

## Convenção de Commits

```
tipo(escopo): descrição curta em pt-BR

[corpo opcional]

[rodapé opcional]
```

### Tipos
| Tipo     | Quando Usar                                       |
|----------|---------------------------------------------------|
| feat     | Nova funcionalidade                               |
| fix      | Correção de bug                                   |
| docs     | Somente documentação                              |
| refactor | Refatoração sem mudança de comportamento         |
| test     | Adição ou correção de testes                      |
| chore    | Tarefas de manutenção (deps, config, build, infra)|
| db       | Migrations e seeders                              |
| style    | Mudanças de formatação/estilo sem lógica         |

### Escopos
| Escopo    | Quando Usar                               |
|-----------|-------------------------------------------|
| api       | Código em apps/api                        |
| web       | Código em apps/web                        |
| android   | Código em apps/android                    |
| ios       | Código em apps/ios                        |
| docs      | Arquivos em docs/                         |
| db        | Migrations e seeders                      |
| infra     | Docker, CI/CD, scripts de ambiente        |
| omr       | Motor e processamento OMR                 |
| relatorio | Módulo de relatórios                      |

---

## Commits Esperados por MP

### MP-001 — Estrutura do Repositório
```
chore: inicializa estrutura do monorepo com apps API, WEB, Android e iOS
chore(infra): configura .gitignore do monorepo
docs: adiciona README.md com instruções de setup
```

### MP-002 — Documentação Consolidada
```
docs: cria CLAUDE.md com arquitetura e convenções do projeto
docs: cria inventário completo dos mockups (01-inventario-dos-mockups.md)
docs: cria visão geral e perfis do produto
docs: cria requisitos funcionais e não-funcionais
docs: cria regras de negócio e casos de uso
docs: cria documentação OMR e modelo de dados
docs: cria rotas API e WEB, arquitetura e roadmap
docs: cria plano de execução, commits e GAP analysis
```

### MP-003 — Banco de Dados
```
db: cria migrations para entidades da estrutura hierárquica (rede, nucleo, escola, turma, aluno)
db: cria migrations para usuarios e escopos de acesso
db: cria migrations para provas, gabaritos e questoes
db: cria migrations para cartoes, respostas e notas
db: cria migrations para visitas e sincronizacoes
db: cria seeders com dados de desenvolvimento para todos os perfis
```

### MP-004 — API Base
```
feat(api): instala e configura Laravel Sanctum
feat(api): implementa AuthController com login, logout e me
feat(api): cria estrutura de rotas versionadas /api/v1
feat(api): cria middleware de autorização por perfil e escopo
feat(api): implementa padrão de resposta ApiResponse
feat(api): cria Policies base para todas as entidades
test(api): adiciona testes de autenticação e autorização
```

### MP-005 — WEB Base
```
feat(web): importa tokens CSS do gov.br Design System
feat(web): cria layout base com GovBar, Header e Breadcrumb
feat(web): implementa AuthController consumindo API de login
feat(web): implementa toggle de tema claro/escuro
feat(web): cria middleware de redirecionamento por perfil
feat(web): implementa logout e redirecionamento
```

### MP-006 — Dashboard Admin
```
feat(api): implementa endpoint GET /api/v1/dashboard/admin
feat(web): cria view dashboard-admin fiel ao mockup
feat(web): implementa KPIs, gráfico de escolas e alertas críticos
feat(web): implementa tabela de últimos acessos e ações rápidas
test(api): adiciona testes do endpoint dashboard/admin
```

### MP-015 — Escolas
```
feat(api): implementa CRUD de escolas com autorização por escopo
feat(api): implementa endpoints ativar/desativar escola
feat(api): implementa endpoint de KPIs da escola
feat(web): cria grid de escolas com cards fiel ao mockup
feat(web): implementa modal de cadastro/edição de escola
feat(web): implementa busca em tempo real e filtros
feat(web): implementa ativação/desativação com toast de feedback
test(api): adiciona testes CRUD de escolas
```

### MP-019 — Avaliações
```
feat(api): implementa CRUD de provas com ciclo de vida por status
feat(api): implementa endpoint de publicação de gabarito
feat(web): cria listagem de provas com filtros e ação contextual
feat(web): implementa stepper de criação de prova (3 etapas)
test(api): adiciona testes do ciclo de vida da prova
```

### MP-020 — Gabaritos
```
feat(api): implementa CRUD de gabarito e questoes
feat(api): implementa validação de gabarito completo para publicação
feat(web): implementa editor de bolhas interativo
feat(web): implementa contador e barra de progresso do gabarito
feat(web): cria view de gabarito publicado (somente leitura)
feat(web): implementa exportação do gabarito em PDF
test(api): adiciona testes de publicação e imutabilidade do gabarito
```

### MP-021 — OMR
```
feat(api): cria interface OmrDriverInterface
feat(api): implementa upload de imagem de cartão
feat(api): cria job ProcessarCartaoOmr com fila
feat(api): implementa OmrService com driver inicial
feat(api): implementa lógica de confiança e classificação de ambíguos
feat(api): implementa endpoint de resolução de ambiguidade
feat(api): implementa cálculo de nota após leitura completa
feat(web): cria view acompanhar-correcao com polling
feat(web): implementa resolução inline de ambíguos
feat(web): implementa estado "Leitura concluída"
test(api): adiciona testes do fluxo OMR completo
```

### MP-022 — Relatórios
```
feat(api): implementa endpoints de resultado individual
feat(api): implementa endpoints de relatório da prova e turma
feat(web): cria view resultado com folha de respostas corrigida
feat(web): cria view relatorio-prova com KPIs e tabela
feat(web): implementa breadcrumb dinâmico por origem
feat(web): implementa exportação de resultado em PDF
test(api): adiciona testes de cálculo de resultado e relatório
```

---

## Regras de Commit

1. **Idioma:** Português Brasileiro
2. **Tamanho:** Até 72 caracteres na linha do título
3. **Corpo:** Quando necessário, separado por linha em branco, explicando o "por quê"
4. **Atomic commits:** Um commit por funcionalidade atômica, não por arquivo
5. **Nunca commitar:** .env, credentials, arquivos de senha, binários grandes
6. **Branch:** Trabalhar em branches de feature, não direto na main

### Exemplos Corretos
```
feat(api): implementa autenticação com email institucional

feat(web): cria dashboard do coordenador com KPIs de provas

fix(api): corrige cálculo de média quando há questão anulada

docs: atualiza inventário dos mockups com tela de resultado dinâmico

db: adiciona índice em provas.escola_id para otimizar listagem

test(api): cobre edge cases de resolução de ambíguos
```

### Exemplos Incorretos
```
Update files           → sem tipo, sem escopo, em inglês
feat: atualiza coisas  → muito vago
fix(api): fixed bug    → em inglês
```
