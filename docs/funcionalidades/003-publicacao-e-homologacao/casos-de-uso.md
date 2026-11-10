# 003 — Publicação, deploy e homologação isolada — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** operador técnico com root nos servidores; responsável do produto (decisão de produção). Nenhum perfil da matriz do [docs/03](../../03-perfis-e-permissoes.md).
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve.

---

## UC-003-01 — Conferir uma publicação sem alterar a produção

**Ator:** operador técnico
**Permissão:** root no CT 111
**Escopo:** produção
**Pré-condições:** SHA alvo aprovado e SHA publicado conhecidos (40 caracteres, minúsculas).
**Pós-condições:** nenhuma mudança de código, banco ou serviços; só lock e temporários.
**Tela/rota:** `bash scripts/operacao/deploy.sh --commit ALVO --from ATUAL --check`.

**Fluxo principal:**
1. O roteiro busca as referências Git.
2. Confere SHAs, árvore rastreada limpa, alvo contido na `main`, serviços e configuração da fila.
3. Encerra informando o resultado.

**Fluxos alternativos e de erro:**
- 1a. SHA inválido: "Informe os dois SHAs completos em minúsculas." (código 2).
- 1b. Outro deploy em andamento: "Outro deploy está em andamento.".
- 2a. Ferramenta ausente (`git`, `php`, `composer`, `flock`...): interrompe com a ferramenta nomeada.

**Critérios de aceite:**
- [x] `--check` não coloca aplicações em manutenção, não instala dependências e não migra.

**Situação:** Implementado — evidência: `scripts/operacao/deploy.sh`, `scripts/tests/test_deploy.py`, [docs/operacao/deploy.md](../../operacao/deploy.md).

---

## UC-003-02 — Publicar um commit aprovado na produção

**Ator:** operador técnico, após decisão expressa do responsável
**Permissão:** root no CT 111
**Escopo:** produção
**Pré-condições:** CI verde no SHA (FN-008), revisão concluída, decisão de produção registrada.
**Pós-condições:** código no SHA alvo, migrations da API aplicadas, serviços ativos e HTTP 200 conferido.
**Tela/rota:** `bash scripts/operacao/deploy.sh --commit ALVO --from ATUAL [--wait N]`.

**Fluxo principal:**
1. Coloca a WEB em manutenção e suspende o agendador.
2. Espera as filas esvaziarem (inclui jobs reservados e atrasados); coloca a API em manutenção e confere de novo.
3. Para os workers, atualiza o Git por fast-forward, instala dependências, executa `migrate --force` só na API e recria caches.
4. Recarrega PHP-FPM, inicia workers, retira a manutenção e exige HTTP 200 no início, no login e em `/api/v1/health`, local e público; reativa o timer.

**Fluxos alternativos e de erro:**
- 2a. Fila não esvazia no prazo: cancela antes de trocar código.
- 3a. Falha antes de mudar código: retira só a manutenção que iniciou e restaura serviços parados.
- 3b. Falha depois da atualização: mantém API e WEB em manutenção, imprime a etapa e preserva o banco; sem rollback automático de migrations.
- 4a. HTTP diferente de 200: a publicação não é considerada concluída.

**Regras de negócio:** produção só por decisão expressa (ADR-H1, docs/127; governança §5).

**Critérios de aceite:**
- [x] Dois deploys aprovados consecutivos sem passo manual (aceite de B5).
- [x] Código publicado legível pelo PHP mesmo com `umask` restritivo do chamador (`umask 022`).
- [ ] Leitura real de um cartão conferida após a publicação (validação funcional específica, FN-002/FN-045).

**Situação:** Implementado — evidência: `scripts/operacao/deploy.sh`, docs/37, PR #66/#67, #86.

---

## UC-003-03 — Preparar uma janela de publicação com backup final

**Ator:** operador técnico
**Permissão:** root no CT 111
**Escopo:** produção
**Pré-condições:** hook de backup revisado em `/root`, de root, sem escrita por grupo ou outros.
**Pós-condições:** com `--manter-manutencao`, aplicações fechadas e workers e agendador parados ao fim; com `--backup-final`, nenhuma DDL antes do recibo do backup.
**Tela/rota:** `deploy.sh ... --manter-manutencao`, `deploy.sh ... --backup-final /root/g360-operacao/pausa-backup-final.sh`; confirmação por `confirmar-backup-final.py`.

**Fluxo principal:**
1. O roteiro pausa antes da primeira DDL.
2. O operador gera o backup final e confirma o recibo.
3. O roteiro continua; com `--manter-manutencao`, termina "PREPARADO EM MANUTENÇÃO", sem teste HTTP nem retomada.

**Fluxos alternativos e de erro:**
- 1a. Hook com permissão indevida: "Hook de backup permite escrita indevida.".
- 1b. Hook fora de `/root`: "Instale o hook revisado dentro de /root.".
- 3a. Reabertura com esquema incompleto: a guarda de esquema não reabre (docs/50).

**Critérios de aceite:**
- [x] Nenhuma DDL antes do recibo do backup final.
- [ ] ⚠ Reserva de 45 min da janela revista incluindo cerca de 15 min do backup final. Resolve: subfase 3.8.

**Situação:** Implementado — evidência: PR #81, #82, #84; docs/49, docs/50, docs/53; `scripts/tests/test_pausa_backup.py`, `test_confirmar_backup.py`.

---

## UC-003-04 — Publicar uma fase na produção

**Ator:** operador técnico
**Permissão:** root no CT 111
**Escopo:** produção
**Pré-condições:** CI do SHA conferido; marcador `primeira-ativacao-pendente` ausente.
**Pós-condições:** pacote e registro da publicação guardados em `/root/g360-releases/<sha>`.
**Tela/rota:** `preparar-release.sh SHA FASE`; depois `publicar-fase.sh SHA ATUAL FASE`.

**Fluxo principal:**
1. `preparar-release.sh` confere que o SHA está na `main`, gera o pacote com SHA-256, a conferência da release, o estado dos serviços e um patch privado das alterações locais, sem publicar nem migrar.
2. `publicar-fase.sh` confere que o pacote foi preparado para o SHA publicado e para a fase e chama o roteiro com backup final obrigatório, registrando a saída.

**Fluxos alternativos e de erro:**
- 1a. Pacote já existe: recusa e pede para preservar a evidência.
- 2a. Pacote preparado para outro SHA ou fase: "Prepare e confira o pacote para o SHA atualmente publicado.".
- 2b. Marcador da primeira ativação presente: recusa a publicação rotineira.

**Critérios de aceite:**
- [x] Publicação V1–T5 registrada (`a90fbf5`, 02/11/2026).
- [ ] ⚠ Encerramento do marcador da primeira ativação registrado. Resolve: subfase 3.5.

**Situação:** Parcial — evidência: `scripts/operacao/preparar-release.sh`, `scripts/operacao/publicar-fase.sh`, [docs/99](../../99-publicacao-por-fase.md), [docs/100](../../100-publicacao-inicial-v1-t5.md).

---

## UC-003-05 — Publicar uma release na homologação

**Ator:** agente de execução ou operador técnico
**Permissão:** root na VM de homologação
**Escopo:** homologação (VM 120), nunca produção
**Pré-condições:** pacote da release com SHA-256 conhecido; entrega mesclada e com CI verde.
**Pós-condições:** release ativa em `/srv/gabarito360/current`, migrations aplicadas no banco da homologação e recibo privado.
**Tela/rota:** `infra/homologacao/deploy.py SHA ARQUIVO CHECKSUM [FASE]`.

**Fluxo principal:**
1. Confere o host, o SHA, o checksum e a identificação da fase.
2. Extrai o pacote com filtro seguro e ajusta permissões.
3. Instala dependências, migra o banco da homologação e troca o link `current` de forma atômica.
4. Registra o recibo (`producao_alterada: false`).

**Fluxos alternativos e de erro:**
- 1a. Host diferente da homologação: o roteiro não executa.
- 3a. Falha de comando: grava log privado e mantém a homologação em manutenção.

**Regras de negócio:** homologação obrigatória; produção por decisão expressa (ADR-H1). Conferência visual registrada por texto, sem prints.

**Critérios de aceite:**
- [x] O roteiro não recebe endereço nem credencial de produção.
- [x] Backup e restauração completa da VM ensaiados (clone).
- [ ] ⚠ Cópia externa dos backups da homologação. Resolve: subfase 3.7.

**Situação:** Implementado — evidência: `infra/homologacao/deploy.py`, [docs/127](../../127-h1-homologacao-isolada.md), [docs/129](../../129-h1-publicacao-ci1-e.md), PR #159.

---

## UC-003-06 — Promover um bloco da homologação para a produção

**Ator:** responsável do produto (decide); operador técnico (executa)
**Permissão:** decisão expressa do responsável; root no CT 111
**Escopo:** produção
**Pré-condições:** bloco validado na homologação; decisão §4 item 2 tomada; pendências operacionais do §7 resolvidas (rodapé e helpers da home, diff do incidente do painel do professor).
**Pós-condições:** bloco publicado e conferido; plano mestre e `planejamento.md` das FNs do bloco atualizados com evidência.
**Tela/rota:** UC-003-04 aplicado a cada bloco do §7 do plano mestre.

**Fluxo principal (proposto):**
1. O responsável escolhe o bloco (recomendação: segurança UR1–UR4, PR #194 e hotfix de leituras 500).
2. O operador prepara e publica a fase com backup final.
3. A conferência é registrada por texto e o status das FNs passa a "Entregue (produção)".

**Fluxos alternativos e de erro:**
- 1a. Bloco com produção expressamente não autorizada (por exemplo, docs/152 para o hotfix de leituras): exige nova decisão.

**Critérios de aceite:**
- [ ] Decisão do responsável registrada por bloco.
- [ ] Flags de L4 mantidas desligadas em produção, se assim decidido.

**Situação:** Não implementado (procedimento depende da decisão §4 item 2) — resolve: subfase 3.9.
