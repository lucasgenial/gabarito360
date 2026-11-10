# 004 — Auditoria de requisições, expurgo e tela de consulta — Casos de uso

**Natureza:** existente documentada a partir do código (AU1); proposta para expurgo e AU2
**Perfis envolvidos:** todos os usuários da API (captura); operador técnico (comandos); Desenvolvedor e Suporte (AU2, proposta). Ver docs/03 (`docs/03-perfis-e-permissoes.md`), extensão A1.
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve.

O docs/07 (`docs/07-casos-de-uso.md`) não tem caso de uso de auditoria.

---

## UC-004-01 — Registrar um evento de auditoria por requisição da API

**Ator:** sistema (middleware `RegistrarAuditoria` e job `GravarAuditoria`)
**Permissão:** não se aplica; vale para toda requisição `api/v1/*`
**Escopo:** plataforma
**Pré-condições:** `AUDITORIA_ATIVA=true`; fila assíncrona; conexão dedicada com credencial própria.
**Pós-condições:** um evento em `auditoria_eventos`, sem payload nem credenciais.
**Tela/rota:** todas as rotas `api/v1/*`, exceto `GET api/v1/health` e `GET api/v1/planos`.

**Fluxo principal:**
1. Ao validar a autenticação, o sistema guarda o contexto do vínculo ou do acesso interno (antes de possíveis revogações).
2. Depois da resposta, monta o evento: modelo da rota, método, IDs de referência, status, resultado, rede do IP e contexto.
3. Enfileira o job cifrado na fila `baixa`.
4. O worker grava pela conexão dedicada.

**Fluxos alternativos e de erro:**
- 1a. Requisição recusada antes da validação: o evento é gravado sem contexto.
- 3a. Fila indisponível ou síncrona: a resposta não muda; o log recebe só `auditoria_enfileiramento_indisponivel`.
- 4a. Conexão dedicada ausente, igual à padrão ou sem usuário: o job falha ("Conexão dedicada de auditoria obrigatória." / "Credencial de auditoria obrigatória.").
- 4b. Reentrega do mesmo UUID: ignorada se idêntica; recusada se o conteúdo divergir.

**Regras de negócio:** docs/28 AU1 (nunca gravar senhas, tokens ou códigos; gravar pela fila `baixa`; sem edição nem exclusão pelo sistema).

**Critérios de aceite:**
- [x] Alteração, leitura e recusa são registradas sem payload nem credenciais.
- [x] Falha da fila não altera a resposta nem vaza exceção.
- [x] Flag desligada e rota de saúde não enfileiram.
- [x] Logout preserva o contexto histórico após revogar o token.
- [ ] ⚠ `usuario_real_id` e `usuario_visto_id` são sempre iguais; a distinção depende do "ver como". Resolve: FN-005 (S1).

**Situação:** Implementado — evidência: `App\Support\Auditoria`, `App\Http\Middleware\RegistrarAuditoria`, `App\Jobs\GravarAuditoria`, `AuditoriaTest`.

---

## UC-004-02 — Conferir inventário, retenção e integridade da auditoria

**Ator:** operador técnico
**Permissão:** acesso ao servidor (CLI)
**Escopo:** plataforma
**Pré-condições:** tabela `auditoria_eventos` existente.
**Pós-condições:** relatório JSON; nenhuma escrita.
**Tela/rota:** `php artisan g360:conferir-auditoria [--dias=N] [--comparar=MANIFESTO]`.

**Fluxo principal:**
1. O comando inventaria os eventos por mês, calcula digests e o corte de retenção.
2. Com `--dias`, simula outro prazo sem aprovar política nem excluir.
3. Com `--comparar`, confere uma restauração contra um manifesto anterior (até 1 MB).

**Fluxos alternativos e de erro:**
- 1a. Esquema, configuração ou manifesto inválido: "Conferência indisponível: confira esquema, configuração e manifesto. Nenhuma escrita executada." (falha).
- 3a. Restauração diverge do manifesto: termina com falha.

**Critérios de aceite:**
- [x] Nenhuma escrita no banco.

**Situação:** Implementado — evidência: `ConferirAuditoria`, `App\Services\ConferenciaAuditoria`, `ConferenciaAuditoriaTest`.

---

## UC-004-03 — Arquivar um mês de auditoria em pacote cifrado

**Ator:** operador técnico
**Permissão:** acesso ao servidor (CLI)
**Escopo:** plataforma
**Pré-condições:** mês UTC fechado; diretório privado novo.
**Pós-condições:** pacote mensal cifrado e verificável; nenhum evento excluído.
**Tela/rota:** `php artisan g360:arquivar-auditoria AAAA-MM DESTINO [--verificar]`.

**Fluxo principal:**
1. O comando exporta os eventos do mês para o destino, cifrados.
2. Com `--verificar`, confere um pacote existente sem consultar nem escrever no banco.

**Critérios de aceite:**
- [x] O arquivo mantém os UUIDs globais; o banco continua autoritativo.
- [x] Nenhuma exclusão de eventos.

**Situação:** Implementado — evidência: `ArquivarAuditoria`, `App\Services\ArquivoMensalAuditoria`, `ArquivoMensalAuditoriaTest`, docs/75 (`docs/75-au1-b3-arquivos-e-restauracao.md`).

---

## UC-004-04 — Restaurar a auditoria em ambiente novo

**Ator:** operador técnico; CI (job `auditoria-recuperacao`, FN-008)
**Permissão:** acesso ao ambiente de ensaio
**Escopo:** clones ou contêineres novos
**Pré-condições:** pacote mensal e manifesto.
**Pós-condições:** banco e fila restaurados e conferidos contra o manifesto.
**Tela/rota:** `scripts/auditoria/ensaio-restauracao.sh`, `scripts/ensaios/au1-equivalente.py`; job `auditoria-recuperacao` em `.github/workflows/verificacao.yml`.

**Fluxo principal:**
1. Restaurar banco e fila em contêineres novos.
2. Conferir com `g360:conferir-auditoria --comparar`.

**Critérios de aceite:**
- [x] Recuperação completa com 1,8 M eventos sintéticos registrada (tempos não são SLA).

**Situação:** Implementado — evidência: PR #110, docs/76 (`docs/76-au1-ensaio-equivalente.md`).

---

## UC-004-05 — Expurgar eventos de auditoria vencidos

**Ator:** agendador
**Permissão:** não se aplica (credencial de exclusão a definir)
**Escopo:** plataforma
**Pré-condições:** política de expurgo aprovada (decisão §4 item 11); arquivo mensal verificado do mês.
**Pós-condições:** eventos de meses vencidos excluídos, exceto meses preservados.
**Tela/rota:** a definir (comando agendado na API).

**Fluxo principal (proposto):**
1. Identificar meses fechados com mais de 180 dias.
2. Ignorar meses em `AUDITORIA_MESES_PRESERVADOS`.
3. Conferir o arquivo mensal e o digest; excluir os eventos do mês; registrar o expurgo.

**Fluxos alternativos e de erro:**
- 3a. Arquivo mensal ausente ou divergente: não excluir.

**Critérios de aceite:**
- [ ] Nenhum evento dentro do prazo é excluído.
- [ ] Mês preservado não é excluído.
- [ ] O expurgo fica registrado.

**Situação:** Não implementado — resolve: subfase 4.7 (Onda 3).

---

## UC-004-06 — Consultar a auditoria na tela

**Ator:** Desenvolvedor, Suporte
**Permissão:** a definir (contexto interno; sem chave no catálogo atual)
**Escopo:** plataforma
**Pré-condições:** contexto interno selecionado; mockup aprovado.
**Pós-condições:** a própria consulta gera evento de auditoria.
**Tela/rota:** a definir.

**Fluxo principal (proposto):**
1. O usuário abre a tela de auditoria.
2. Filtra por pessoa, ação, período e escola.
3. Vê a lista paginada de eventos.

**Fluxos alternativos e de erro:**
- 1a. Outro perfil ou contexto pedagógico: acesso negado.
- 3a. Suporte: eventos de Desenvolvedores ocultos também em filtros e contagens.

**Regras de negócio:** docs/28 AU2, passos 1 a 4.

**Critérios de aceite:**
- [ ] Testes cobrem Desenvolvedor, Suporte sem ver Desenvolvedor e negação aos demais perfis.
- [ ] Consulta registrada na auditoria.

**Situação:** Não implementado — resolve: subfase 4.10 (Onda 4).

---

## UC-004-07 — Exportar a auditoria em CSV

**Ator:** Desenvolvedor, Suporte
**Permissão:** a definir (a mesma de UC-004-06 ou específica de exportação)
**Escopo:** plataforma
**Pré-condições:** filtro aplicado em UC-004-06.
**Pós-condições:** arquivo CSV gerado; exportação auditada.
**Tela/rota:** a definir.

**Fluxo principal (proposto):**
1. O usuário pede a exportação do resultado filtrado.
2. O sistema gera o CSV respeitando as mesmas ocultações.

**Fluxos alternativos e de erro:**
- 2a. Suporte: eventos de Desenvolvedores não entram no arquivo.

**Critérios de aceite:**
- [ ] O CSV respeita filtros e ocultações.
- [ ] Prazo de retenção do CSV definido (decisão §4 item 11 lista "CSV" entre os prazos abertos).

**Situação:** Não implementado — resolve: subfase 4.10 (Onda 4).
