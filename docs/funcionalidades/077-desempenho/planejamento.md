# 077 — Desempenho e carga — Planejamento

**Implementação:** Parcial
**Etapa:** Em planejamento
**Conforme princípios:** versão 1
**Prioridade no plano mestre:** trilha contínua de qualidade (§5): medição com 1000 usuários simultâneos antes de qualquer abertura comercial; F6 restante quando houver uso real. A forma de obter as 1000 identidades está na Onda 0 (decisão §4 item 8)
**Natureza:** existente documentada a partir do código
**Origem:** tronco original (X1–X2 do docs/28 (`docs/28-plano-mestre-proximos-modulos.md`)); ST1 nasceu do incidente da turma após UR3 (docs/168 (`docs/168-estabilizacao-turmas-e-contratos.md`)); AP1 (docs/172 (`docs/172-ap1-plano-desempenho-e-contrato-api.md`), docs/173 (`docs/173-ap1-contrato-http-e-operacao-independente.md`)); série F0–F6 (docs/192 (`docs/192-fundamentos-desempenho.md`), docs/194 (`docs/194-planejamento-desempenho-integral.md`)–docs/200 (`docs/200-f5-f6-diagnostico-catalogo.md`)), surgida na integração do editor e aprovada pelo responsável como plano integral
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Manter o aplicativo rápido e estável para todos os perfis com consultas proporcionais ao volume, menos transferência repetida, carregamento regional e capacidade operacional ajustada, sem afrouxar autorização, revogação ou durabilidade. Toda melhoria é comprovada com o padrão de **1000 usuários simultâneos**.

## 2. Escopo
- **Inclui:**
  - ST1: estabilização da turma e dos formulários após UR3 (N+1 por aluno/prova, 503 sem métricas falsas, transporte BFF);
  - AP1-A..E: baseline, hierarquia via `EXISTS`, prova de contrato do painel, `API_BASE_URL` independente, medição na homologação;
  - F0: ADR-195, regras no AGENTS e template de PR;
  - F1: diagnóstico (oito perfis, 12 casos);
  - F2: autorização em SQL e agregações em lote nos painéis;
  - F3: versionamento de assets por conteúdo (`@ativo`), cache no nginx por versão, sprites WebP, runtime único de gráficos (`echarts-g360`); limpeza dos artefatos antigos;
  - F4-A..D: miniaturas por visibilidade, abas da turma sob demanda, transformação UR4 mais barata, ciclo de vida dos gráficos;
  - F6: capacidade e operação (F6-1 buffer pool aplicado; pools FPM, OPcache e gravação em lote das referências UR4 pendentes);
  - X1: medição com 1000 usuários simultâneos.
- **Não inclui:**
  - F5 busca nacional do catálogo: adiada em 09/11/2026 e transferida para [FN-032](../032-busca-catalogo-autocompletar/);
  - X3–X6, AP1-L (limites das 93 rotas), separação física da API e mutex distribuído: [FN-007](../007-escala-infraestrutura/);
  - motor de carregamento regional e estados: [FN-076](../076-carregamento-estados/) (F4 o usa);
  - paginação opcional da API: [FN-074](../074-tabelas-padrao/);
  - CI do MariaDB: [FN-008](../008-ci-integracao/).

## 3. Fontes de verdade
- Documentos de origem: docs/168 (`docs/168-estabilizacao-turmas-e-contratos.md`), docs/172 (`docs/172-ap1-plano-desempenho-e-contrato-api.md`), docs/173 (`docs/173-ap1-contrato-http-e-operacao-independente.md`), docs/192 (`docs/192-fundamentos-desempenho.md`), docs/194 (`docs/194-planejamento-desempenho-integral.md`) (plano integral e padrão de carga), docs/195 (`docs/195-adr-fundamentos-desempenho.md`) (ADR), docs/196 (`docs/196-diagnostico-desempenho-f1.md`), docs/197 (`docs/197-f2-consultas-paineis.md`), docs/198 (`docs/198-f3-arquivos-cache.md`), docs/199 (`docs/199-f4-carregamento-interacao.md`), docs/200 (`docs/200-f5-f6-diagnostico-catalogo.md`); X1–X2 em docs/28 (`docs/28-plano-mestre-proximos-modulos.md`).
- Regras permanentes em `AGENTS.md` ("Desempenho — plano integral e critérios de novas funcionalidades").
- Casos de uso: [casos-de-uso.md](casos-de-uso.md); relacionados UC-004, UC-006 e UC-007 de docs/07 (`docs/07-casos-de-uso.md`). Perfis: docs/03 (`docs/03-perfis-e-permissoes.md`).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| Decisão §4 item 8 (identidades para a carga) | Sem 1000 identidades autenticadas não há medição no padrão | Pendente |
| FN-018 (UR1–UR4) | F4-C otimiza a transformação UR4; ST1 nasceu após UR3 | Em homologação |
| FN-076 carregamento-estados | F4-A/F4-B usam o motor regional | Parcial |
| FN-003 publicação | Publicação na homologação com backup e rollback | Concluída |

**Funcionalidades que dependem desta:** FN-032 (retoma a F5 com o padrão de 1000 usuários), FN-007 (X3–X6 usam a medição de X1), FN-021 (índices e particionamento remetidos a 077/X1), FN-053 (P3.4 usa F2/F4), FN-068 e a abertura comercial (medição exigida antes).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (ST1-A..E) | Estabilização da turma: N+1 eliminado (26.195 → 1.367 consultas), 503 sem métricas falsas, transporte BFF | Homologação sem 500 na turma | Entregue (homologação) | PR #196 |
| 1.2 (ST1, custo de construção de escopos) | Reduzir o custo de construir escopos | — | Pendente | plano mestre §3 |
| 2.1 (AP1-A..D) | Baseline; hierarquia via `EXISTS`; contrato do painel; `API_BASE_URL` | Testes de contrato | Entregue (homologação) | PR #200; `DashboardDesempenhoContratoTest` |
| 2.2 (AP1-E) | Publicar e medir na homologação | Recibo de medição | Incerto | sem recibo |
| 3.1 (F0) | ADR-195, AGENTS, template de PR | Regras aplicáveis às novas entregas | Entregue | PR #229 |
| 3.2 (F1) | Diagnóstico de oito perfis e 12 casos | Gargalos por camada e protocolo repetível | Entregue | PR #229; docs/196 |
| 4.1 (F2) | Autorização em SQL e agregações em lote | 12 casos sem divergência; testes SQLite/MariaDB | Entregue (homologação) | PR #230–#232; docs/197 |
| 5.1 (F3) | Assets por versão, cache nginx, sprites WebP, runtime ECharts único | Navegação fria/quente correta; bytes reduzidos | Entregue (homologação) | PR #233, #234; docs/198 |
| 5.2 (F3 limpeza) | Remover PNG v1 dos sprites e `echarts-6.1.0.common.min.js` | Home exportada e prévia sem dependência dos antigos | Pendente | docs/198 |
| 6.1 (F4-A..D) | Miniaturas por visibilidade, abas sob demanda, UR4 deduplicada, ciclo de vida dos gráficos; conferência no Chrome | Revisão independente; sem respostas tardias aplicadas | Entregue (homologação; conferência feita em 09/11) | PR #235–#237; docs/199 |
| 7.1 (F5) | Busca nacional do catálogo | — | Adiada → FN-032 | decisão do responsável de 09/11/2026 (docs/200) |
| 8.1 (F6-1) | `innodb_buffer_pool_size` 128 MB → 1 GB na VM 120 | Sem perda de durabilidade; reversão documentada | Entregue (homologação) | docs/200; `infra/homologacao/mariadb/90-g360-perf-f6.cnf` |
| 8.2 (F6 restante) | Pools FPM, OPcache, gravação em lote das referências UR4 | Um experimento por vez, com reversão; memória medida com buffer pool cheio | Pendente (aguarda uso real) | docs/199, docs/200 |
| 9.1 (X1) | Medição com 1000 usuários simultâneos | Relatório no padrão do docs/194 | Pendente | nenhuma medição nesse padrão; depende da decisão §4 item 8 |
| 9.2 (X2) | Banco e consultas | — | Substituída em parte por F2–F4 | — |

## 6. Contrato técnico
- **Padrão de carga (docs/194, direção do responsável de 09/11/2026):** cada percurso medido roda com 1000 usuários virtuais repetindo o pedido por uma janela fixa; relatório com média, p50, p95, p99, pedidos por segundo, taxa de erros (4xx/5xx e conexões recusadas) e CPU/memória da VM; gerador fora da VM medida, sem passar pela Cloudflare; mesma carga antes e depois; só leitura, sem alterar gabaritos, resultados ou dados escolares. A medição isolada continua útil para diagnóstico, mas não basta como resultado.
- **Nenhum resultado F2–F6 seguiu esse padrão:** F2 usou três requisições HTTP locais sequenciais por caso; F3, amostra única por percurso e um perfil. Os números servem como diagnóstico, não como capacidade.
- **ADR-195:** API dona do banco; Web só por HTTP; autorização revalidada sem guardar concessões em cache; leitura proporcional; cache por classe de dado (assets técnicos imutáveis por hash; HTML/JSON/fotos sem cache genérico); nada de dado escolar, token ou permissão em cache persistente; medição sanitizada fora do Git.
- **Assets:** diretiva `@ativo()` (`App\Support\Ativos::url`) com `?v=` de 16 hexadecimais; nginx da VM 120: `private, max-age=31536000, immutable` para versionados, `private, no-cache` para os demais, `no-store` para login e manifesto. Teste: `apps/web/tests/Feature/AtivosVersionadosTest.php`.
- **Banco:** F6-1 só na VM 120; `innodb_flush_log_at_trx_commit` inalterado (1). Reversão: remover o drop-in e reiniciar o MariaDB.
- Sem migrations nas etapas F2–F4.

## 7. Interface
- F4 mudou só o momento das cargas: miniaturas da correção entram na fila de 4 quando chegam a 400 px da área visível; Alunos, Disciplinas e Acesso da turma carregam na primeira abertura da aba, com estados de carga e erro por aba.
- Gráficos da turma redesenham uma vez por quadro de animação e só quando tamanho ou tokens mudam.
- Conferência registrada por texto, sem prints. As "8 capturas Chrome" citadas no docs/196 não estão versionadas (nenhuma imagem fora das pastas de assets e de design no Git); falta confirmar que não estão em serviços externos (plano mestre §6).

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 8 — identidades para a carga de 1000 usuários simultâneos | Contas sintéticas por comando só na VM 120 / outra forma | Contas sintéticas geradas por comando, só na VM 120, e gerador fora da VM | Pendente |
| §4 item 2 — gate de produção | — | Bloco "Estabilização e desempenho" do §7 (PR #196, #200, #229–#237); F6-1 só na VM 120 | Pendente |
| Semântica da busca nacional (substring × palavras × UF obrigatória) | — | Decidir quando FN-032 voltar | Adiada (FN-032) |
| F6 restante sem uso real | Executar agora / aguardar | Aguardar uso real e medir memória com o buffer pool cheio | Aguarda uso real |
| Publicação de F4-A e F4-B separadas (desvio da regra "publicar por etapa" do docs/194) | — | Registrado no docs/199; sem ação | Registro |
| Confirmar que as capturas do F1 não estão em serviços externos (plano mestre §6) | — | Conferir e registrar por texto | Pendente |

## 9. Ramos desta entrega
- Medições e recibos operacionais ficam fora do Git (`operacao-20261102/`, recibo do F6-1 na VM 120).
- Desenho preferido do autocompletar do catálogo (docs/200) pertence a FN-032.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
