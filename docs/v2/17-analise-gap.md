# Análise de GAP V2 — Mockup como contrato integral (sem legado)

> Fonte de verdade: `style-system/` (30 telas, `DESIGN-HANDOFF.md`, `gov.css`,
> `app.js`). Sob a restrição de **produto único, sem legado**
> ([ADR-D016](../decisoes/ADR-D016-v2-sem-legado.md)), esta análise descreve o
> que a V2 deve **construir** e o que do legado deve ser **removido**. Não há
> dimensão de "reaproveitamento": nada herdado é mantido por compatibilidade.
> Decisões aplicadas: MariaDB ([ADR-D012]), React Native
> ([ADR-D015](../decisoes/ADR-D015-mobile-react-native.md)), sem `/api/v1`.

## 1. O que o mockup exige (alvo único da V2)

O `DESIGN-HANDOFF.md` confirma **30 telas HTML**, 1 folha de estilo (`gov.css`),
1 script (`app.js`) e a matriz de 9 viewports (360×800 a 1920×1080).
Capacidades obrigatórias por módulo (ver `02-inventario-funcional-mockup.md`):

- **Acesso**: entrar, manter conectado, esqueci senha, cadastrar/criar conta.
- **Dashboards por ator**: admin, aluno, coordenador, diretor escolar, diretor
  de núcleo, professor e genérico — KPIs, gráficos, alertas, agenda, ações
  rápidas.
- **Escolas / Equipe / Turmas / Alunos**: listas, detalhes, formulários,
  filtros, importação, permissões, responsáveis, histórico, ficha PDF e
  indicadores reais.
- **Provas / Correção / Resultados / Relatórios**: rascunho, padrões, editor de
  gabarito, publicação, acompanhamento geral e por turma, resultado individual,
  KPIs, distribuição, comparativos e exportações PDF/CSV/XLSX.
- **Conta**: perfil, senha, notificações, sessões, aparência, idioma/região,
  acessibilidade, importação/exportação, plano/uso, integrações, privacidade e
  zona de perigo.
- **Transversais**: tema claro padrão + escuro persistido, breadcrumbs, tabs,
  modais, gráficos com alternativa acessível e os estados `default`, `hover`,
  `focus`, `active`, `disabled`, `loading`, `empty`, `error`, `success` e
  `access_denied`.

## 2. O que a V2 deve construir (do zero)

Tudo é construído como V2, sem partir de implementação herdada:

| Camada | Construção V2 |
|---|---|
| Backend | Laravel 12 com domínio completo do mockup e **API exclusivamente `/api/v2`** |
| Banco | Esquema **único MariaDB V2** (migrations consolidadas; `migrate:fresh` baseline) |
| Web | Camada visual reconstruída fiel ao mockup, tokens oficiais, tema claro padrão |
| Mobile | App **React Native** novo (sem base Flutter) |
| OMR | Pipeline OpenCV implementado e homologado em cartões/dispositivos reais |
| Infra | Docker/Nginx/Redis/Reverb/CI montados para servir somente a V2 |
| Tempo real | Eventos e canais privados escopados, snapshot recarregável |

## 3. O que está faltando hoje (GAP de construção)

| Lacuna | Severidade | Origem no mockup |
|---|---|---|
| Onboarding/cadastro, recuperação de senha e perfil de aluno autenticado | Alta | `login.html`, `dashboard-aluno.html` |
| Dashboards específicos por ator com dados reais | Alta | `dashboard-*.html` (7 variantes) |
| Agenda, reuniões, visitas, notificações e central de atividades | Média | dashboards e conta |
| Configurações completas: integrações, plano/uso, privacidade, zona de perigo | Alta | `configuracoes.html` |
| Relatórios e exportações integrais (PDF/CSV/XLSX) | Alta | `relatorio-*.html`, `resultado-*.html` |
| Paridade visual automatizada nos 9 viewports | Alta | contrato de responsividade |
| App Android real (câmera, revisão, fila offline, sincronização) | Alta | jornada do aplicador |
| OMR OpenCV homologado em cartões/dispositivos reais | Alta | `11-omr-opencv.md` |
| Editor de gabarito fiel ao mockup | Média | `gabarito.html`, `criar-prova.html` |

## 4. O que deve ser removido (legado a descontinuar)

Sob a restrição de não manter legado, os artefatos abaixo são **removidos**, não
reaproveitados (detalhe em [`18-analise-reaproveitamento.md`](18-analise-reaproveitamento.md)):

| Artefato legado | Ação |
|---|---|
| Endpoints e contrato `/api/v1` | Remover; a API é só `/api/v2` |
| Páginas Blade/componentes web anteriores | Remover; reconstruir fiel ao mockup |
| App Flutter (`mobile/` atual) | Remover; novo app React Native |
| Migrations/seeders específicos da V1 não consolidados no esquema V2 | Remover |
| Dados e JavaScript estáticos do protótipo como regra | Remover; usar consultas reais |
| Matrizes/planos/rotas R1–R7 como definição de escopo | Arquivar como histórico |
| Documentos numerados em `docs/` (V1) | Arquivar; não governam a V2 |

## 5. Divergências resolvidas

| Item | Antes | Decisão V2 |
|---|---|---|
| Banco | Pedido citou PostgreSQL | **MariaDB** (ADR-D012) |
| Mobile | Flutter | **React Native** (ADR-D015) |
| Estratégia | Reaproveitar fundação R7 e manter V1 | **Sem legado**, produto único (ADR-D016) |
| API | `/api/v1` em transição | Apenas `/api/v2` |

## 6. Critérios de fechamento do GAP

Uma tela ou recurso só fecha o GAP quando possui dados reais, autorização por
perfil/escopo, todos os estados visuais, testes proporcionais ao risco,
responsividade nos 9 viewports e comparação visual aprovada contra o mockup —
e quando nenhum artefato legado correspondente permanece no produto.
