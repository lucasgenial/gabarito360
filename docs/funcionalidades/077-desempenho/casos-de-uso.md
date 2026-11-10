# 077 — Desempenho e carga — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** todos os perfis (efeito transversal); operador da homologação e agentes de execução nos casos de medição e capacidade (ver docs/03 (`docs/03-perfis-e-permissoes.md`))
**Atualizado em:** 2026-11-09

Os fluxos de negócio dos painéis, da turma, da correção e dos relatórios estão em UC-004, UC-006 e UC-007 de docs/07 (`docs/07-casos-de-uso.md`) e nas FNs donas. Aqui ficam as garantias de desempenho que esses fluxos recebem e os procedimentos de medição. Números citados são diagnóstico (métodos do docs/197 e docs/198), não resultados no padrão de 1000 usuários simultâneos.

---

## UC-077-01 — Abrir o painel do perfil com consultas proporcionais

**Ator:** Professor, Coordenador, Diretor Escolar, Diretor de Núcleo, Administrador Rede
**Permissão:** a do painel do perfil (revalidada a cada pedido)
**Escopo:** o do vínculo ativo
**Pré-condições:** vínculo ativo.
**Pós-condições:** painel com os mesmos dados, tipos e estados de acesso de antes, com número de consultas que não cresce por item.
**Tela/rota:** `/app/painel`; endpoints de painel da API.

**Fluxo principal:**
1. A Web pede o painel à API.
2. A API resolve contexto, perfil, hierarquia e escopos em SQL, sem guardar concessões em cache, e agrega os dados em lote.
3. A Web compõe a resposta.

**Fluxos alternativos e de erro:**
- 2a. Vínculo revogado ou alcance alterado: o pedido seguinte já reflete a revogação.

**Regras de negócio:** ADR-195 itens 3 e 4.

**Critérios de aceite:**
- [x] 12 casos de oito perfis sem divergência de conteúdo na comparação antes/depois (docs/197).
- [x] Consultas por percurso (CLI): professor 2.500 → 32; coordenador 1.445 → 38; direção escolar 1.507 → 41; núcleo 880 → 32.
- [ ] ⚠ Medição com 1000 usuários simultâneos — subfase 9.1 (X1).

**Situação:** Implementado — evidência: PR #230–#232; `apps/api/tests/Feature/F2ConsultaContratoTest.php`, `DashboardDesempenhoContratoTest.php`.

---

## UC-077-02 — Reaproveitar arquivos técnicos na navegação seguinte

**Ator:** qualquer usuário
**Permissão:** autenticação da homologação (Basic) e sessão; assets não concedem acesso
**Escopo:** —
**Pré-condições:** primeira navegação feita.
**Pós-condições:** CSS e JS versionados vêm do cache privado; nova release baixa os novos arquivos.
**Tela/rota:** todas as telas; diretiva `@ativo()`; nginx da VM 120.

**Fluxo principal:**
1. A view gera a URL do arquivo com `?v=` derivado do conteúdo.
2. O nginx responde `private, max-age=31536000, immutable`.
3. Na segunda navegação o navegador não baixa de novo.

**Fluxos alternativos e de erro:**
- 2a. Arquivo sem versão ou com versão inválida (inclusive sprites): `private, no-cache` com ETag (304 na revalidação).
- 2b. `/login` e manifesto: `no-store`.

**Critérios de aceite:**
- [x] Bytes transferidos na segunda navegação (coordenador): painel 15.693 B; lista de turmas 53.324 B; turma 39.688 B (docs/198).
- [x] Um único runtime de gráficos por página (`echarts-g360`).
- [ ] ⚠ PNG v1 e `echarts-6.1.0.common.min.js` ainda no diretório — subfase 5.2 (F3 limpeza).

**Situação:** Implementado — evidência: PR #233, #234; `tests/Feature/AtivosVersionadosTest.php`.

---

## UC-077-03 — Abrir a turma carregando só a visão geral

**Ator:** Professor, Coordenador, Diretor Escolar (conforme acesso à turma)
**Permissão:** `turma.ver`; abas conforme permissões próprias
**Escopo:** turma do vínculo
**Pré-condições:** acesso à turma.
**Pós-condições:** visão geral exibida primeiro; as demais abas carregam ao serem abertas.
**Tela/rota:** detalhe da turma; `turmas/_secao_adiada.blade.php`; `public/js/turma-abas.js`.

**Fluxo principal:**
1. A turma abre com a visão geral.
2. Na primeira abertura de Alunos, Disciplinas ou Acesso, a aba busca seus dados pela ponte UR4 (CSRF e revisão de contexto), com esqueleto regional (FN-076).
3. Reaberturas usam os dados já carregados.

**Fluxos alternativos e de erro:**
- 2a. Falha: erro na própria aba com "Tentar novamente"; o restante da turma continua utilizável.

**Critérios de aceite:**
- [x] Menos chamadas na abertura da turma.
- [x] Foco e teclado preservados.
- [x] Gráficos sem callbacks tardios e com redesenho correto ao voltar a uma aba oculta (F4-D).

**Situação:** Implementado — evidência: PR #236, #237; docs/199.

---

## UC-077-04 — Acompanhar a correção com miniaturas por visibilidade

**Ator:** Professor, Coordenador (acompanhamento de correção, UC-004 do docs/07)
**Permissão:** `correcao.acompanhar`
**Escopo:** prova e turma do vínculo
**Pré-condições:** prova com cartões enviados.
**Pós-condições:** só as miniaturas próximas da área visível são pedidas.
**Tela/rota:** `correcao/show`; `public/js/cartoes-miniaturas.js`.

**Fluxo principal:**
1. Cada miniatura entra na fila de 4 cargas quando chega a 400 px da área visível (`IntersectionObserver`).
2. Estados de carga, erro e nova tentativa são preservados por miniatura.

**Fluxos alternativos e de erro:**
- 1a. Navegador sem `IntersectionObserver`: comportamento anterior.

**Critérios de aceite:**
- [x] Nenhum pedido de miniatura fora da área visível.

**Situação:** Implementado — evidência: PR #235; docs/199.

---

## UC-077-05 — Medir um percurso com 1000 usuários simultâneos

**Ator:** agente de execução ou operador da homologação
**Permissão:** autorização do responsável; identidades de carga definidas pela decisão §4 item 8
**Escopo:** homologação (VM 120); produção fora do escopo
**Pré-condições:** 1000 identidades autenticadas disponíveis; gerador fora da VM medida; tráfego sem Cloudflare.
**Pós-condições:** relatório comparável antes e depois de uma mudança.
**Tela/rota:** percursos de leitura (telas, regiões sob demanda, arquivos, buscas).

**Fluxo principal (proposto):**
1. Definir release, percurso, volume e janela fixa.
2. Rodar 1000 usuários virtuais repetindo o pedido continuamente pela janela.
3. Registrar média, p50, p95, p99, pedidos por segundo, taxa de erros (4xx, 5xx, conexões recusadas) e CPU/memória da VM.
4. Repetir o mesmo cenário depois da mudança e comparar.

**Fluxos alternativos e de erro:**
- 2a. Pedido que altere gabaritos, resultados ou dados escolares: proibido; a carga é só de leitura.
- 3a. Evidência com SQL literal, payload escolar, cookies ou tokens: proibida; relatório sanitizado fora do Git.

**Regras de negócio:** docs/194 "Padrão de carga: 1000 usuários simultâneos"; ADR-195 item 9.

**Critérios de aceite:**
- [ ] Relatório completo no padrão para os percursos de F2–F4.
- [ ] Medição antes de qualquer abertura comercial (plano mestre §5).

**Situação:** Não implementado — nenhuma medição nesse padrão; bloqueado pela decisão §4 item 8 (subfase 9.1).

---

## UC-077-06 — Ajustar a capacidade do servidor por experimento controlado

**Ator:** operador da homologação, com autorização expressa do responsável
**Permissão:** operacional (VM 120)
**Escopo:** homologação
**Pré-condições:** medição de memória, CPU, IO, buffer pool e OPcache; plano de reversão.
**Pós-condições:** um parâmetro alterado por vez, com benefício medido e sem perda de durabilidade.
**Tela/rota:** `infra/homologacao/mariadb/90-g360-perf-f6.cnf`; pools FPM da web e da API.

**Fluxo principal:**
1. Pausar worker e timers que gravam.
2. Instalar a configuração versionada e conferir (`my_print_defaults`).
3. Reiniciar o serviço, conferir tamanho, durabilidade e tabelas, restaurar as unidades.

**Fluxos alternativos e de erro:**
- 3a. Falha: remover a configuração e reiniciar (reversão).

**Critérios de aceite:**
- [x] F6-1: buffer pool 128 MB → 1 GB; `innodb_flush_log_at_trx_commit` = 1; 130 tabelas antes e depois; leituras do disco de ~4,5% para ~0,04% das requisições (docs/200).
- [ ] ⚠ Pools FPM, OPcache e gravação em lote das referências UR4 — subfase 8.2, aguarda uso real.

**Situação:** Parcial — evidência: docs/200; recibo operacional na VM 120.

---

## UC-077-07 — Aceitar uma nova funcionalidade pelo checklist de desempenho

**Ator:** agente de execução e revisor
**Permissão:** —
**Escopo:** toda entrega com código
**Pré-condições:** funcionalidade com `planejamento.md` aprovado.
**Pós-condições:** PR com contrato, volume, autorização, consulta, cache, ciclo de vida e evidência declarados proporcionalmente ao risco.
**Tela/rota:** template de PR; ADR-195 "Checklist proporcional de entrada e aceite".

**Fluxo principal:**
1. Na entrada, declarar consumidor, DTO, universo, revogação, filtros/ordem/página, decisão de cache e regiões sob demanda.
2. No aceite, apresentar a evidência de cada item aplicável ("não aplicável" exige razão concreta).

**Critérios de aceite:**
- [x] Regras vigentes no AGENTS.md e no template de PR (F0).
- [ ] ⚠ Evidência de desempenho no padrão de 1000 usuários quando aplicável — depende de X1.

**Situação:** Implementado (processo) — evidência: PR #229; docs/195.
