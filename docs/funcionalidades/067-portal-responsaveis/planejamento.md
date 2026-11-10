# 067 — Portal dos responsáveis — Planejamento

**Implementação:** Pendente
**Etapa:** Em planejamento
**Prioridade no plano mestre:** Onda 4 (proposta): "067 portal-responsaveis — depende de 035, 061, 062, 069, D10"
**Natureza:** nova
**Origem:** tronco original — docs/28 Fase 8, pacotes P1–P8; termo do docs/33 §4; RIPD preliminar do docs/38
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Permitir que o responsável pelo aluno consulte as notas das provas, sem conta de usuário, com matrícula e código de acesso, em um ambiente separado, mínimo e auditado. É o módulo de maior risco, por tratar dados de menores; disponível só para escolas e perfis superiores.

## 2. Escopo
- **Inclui:** P1 modelo de segurança; P2 consentimento (termo do docs/33 §4, versão e aceite, autorização opcional de comunicações separada); P3 código de acesso e primeiro acesso (`portal_acessos_aluno`, código de 10 caracteres sem ambiguidade, Turnstile, resposta única para erro, limites por IP e matrícula, sessão de 15 minutos, auditoria); P4 código no cadastro do aluno para a secretaria; P5 tela de resultados; P6 impressão e PDF com selo e marca-d'água; P7 revisão final de segurança e piloto em uma escola; P8 código por e-mail (segunda versão).
- **Não inclui:** dados dos responsáveis (FN-035, A6); termos gerais e aviso de privacidade (FN-061); selo dos documentos (FN-069); notificações (FN-057); login individual do aluno (FN-020); portal para o professor autônomo, que não tem portal dos pais (RL18); CPF e código por e-mail na primeira versão (decisão do docs/28 §4).

## 3. Fontes de verdade
docs/28 Fase 8 e §4 (`docs/28-plano-mestre-proximos-modulos.md`), docs/33 §4 e §6 (`docs/33-lgpd-privacidade-e-consentimento.md`) (RL8, RL13, RL14, RL18), docs/34 (`docs/34-inventario-de-dados.md`) (O18), docs/38 (`docs/38-ripd-preliminar-portal.md`) (R01–R11 e condições de liberação), docs/03 (`docs/03-perfis-e-permissoes.md`) (Secretário Escolar: código do responsável depende de P3/P4). Código: nenhum (não existem `portal_acessos_aluno` nem `Api/Portal/`; o portal do ramo legado/v2 está fora da main e não conta). Mockups: nenhum no inventário docs/01 (`docs/01-inventario-dos-mockups.md`); o layout próprio do P5 exigirá mockup aprovado. `docs/31-seguranca-portal.md`, previsto em P1, não existe. Casos de uso: [casos-de-uso.md](casos-de-uso.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| D10 (código gerado pelo sistema) | Modelo de primeiro acesso (P1, P3) | Decidido no texto do docs/28, aberto na tabela; confirmar (§4 item 9) |
| D14 (revisão jurídica) | Termo do responsável (P2) | Pendente |
| FN-035 responsáveis dos alunos (A6) | P1 e P3 exigem dados dos responsáveis | Pendente |
| FN-061 termos e privacidade (L1) | P2 depende de L1 | Parcial |
| FN-062 inventário e retenção (L2) | Nenhum pacote começa antes de L2; RIPD validado pelo controlador | Parcial |
| FN-069 selo dos documentos (R1) | P6 usa o selo | Pendente |
| FN-004 auditoria (AU1) | P3 registra todo acesso | AU1 entregue |
| FN-007 escala (X3) | P3 depende de limites por rota e borda | Pendente |
| FN-065 plano de incidentes | P7 inclui resposta a incidentes | Pendente |
| FN-057 notificações (N3) e FN-060 e-mail (E4) | P8 | Pendentes / Parcial |
| FN-063 proteção dos dados | Código do portal cifrado (RL8) | Concluída |

**Funcionalidades que dependem desta:** FN-020 acesso do aluno (decisão §4 item 10 avaliada junto com o portal); FN-062 (validação residual do RIPD após os controles).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 7.1 (P1) | Modelo de segurança: ameaças, dados mínimos, retenção, revisão | Documento aprovado pelo responsável | Pendente | doc inexistente |
| 7.2 (P2) | Consentimento do responsável com versão e aceite; comunicações opcionais separadas | Recusar comunicações não bloqueia o portal (R11) | Pendente | — |
| 7.3 (P3) | Código de acesso e primeiro acesso | Testes de repetição do primeiro acesso, bloqueio, enumeração e sessão vencida | Pendente | — |
| 7.4 (P4) | Código no cadastro do aluno, nova geração, impressão por turma | Outros perfis não recebem o código na API | Pendente | — |
| 7.5 (P5) | Tela de resultados com layout próprio | Auditoria de telas e teste de isolamento entre alunos | Pendente | — |
| 7.6 (P6) | PDF com logos, selo e marca-d'água | Selo segue a modalidade e não aparece na tela | Pendente | — |
| 7.7 (P7) | Revisão final de segurança e piloto em uma escola | Teste de intrusão, logs revisados, plano de incidentes, publicação gradual | Pendente | — |
| 7.8 (P8) | Código por e-mail | Contatos confirmados; segunda versão | Pendente | — |

## 6. Contrato técnico (proporcional ao risco)
- Rotas públicas próprias, sem `auth`, em `apps/api/app/Http/Controllers/Api/Portal/`; a web só renderiza.
- `portal_acessos_aluno` com código cifrado, situação, primeiro acesso e geração; consulta do código auditada.
- Mesma resposta para qualquer dado incorreto; limites por IP e por matrícula com bloqueio progressivo; Turnstile; sessão de 15 minutos sem token na URL.
- P5 com `noindex` e `no-store`, sem menu do sistema, sem propaganda nem rastreamento (RL14).
- Meta inicial de capacidade do docs/28: 1.000 responsáveis; medir com o padrão de 1000 usuários simultâneos antes do piloto (FN-077).

## 7. Interface
Layout próprio, limpo, celular primeiro; primeiro acesso, entrada, resultados e impressão. Exige mockup e aprovação antes da implementação. Estados de carga, erro genérico e bloqueio temporário. Conferência registrada por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 9 — D10 | Confirmar "sistema gera o código" | D10 já está decidido no texto do docs/28; só confirmar | Pendente |
| §4 item 9 — D14 | Revisão jurídica do termo | Responder D13 e D14 primeiro | Pendente |
| §4 item 10 — login do aluno | Decidir junto com o portal | Decidir junto (acesso individual de menores) | Pendente |
| Assunção do RIPD pelo controlador (§3) | Validar R01–R11 após controles | Antes do piloto (P7) | Pendente |
| R01 crítico (docs/38) | Entrega do código em papel antes do primeiro acesso, registro do primeiro acesso, nova geração | Tratar em P1/P3/P4/P7 | Pendente |

## 9. Ramos desta entrega
Nenhum.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
