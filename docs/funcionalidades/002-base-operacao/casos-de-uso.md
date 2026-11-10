# 002 — Base de operação (backup, Redis, filas, leitura pela fila) — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** Professor, Aplicador e demais perfis com `cartao.enviar` (ver docs/03 (`docs/03-perfis-e-permissoes.md`)); operador técnico (sem perfil na matriz)
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve.

O acompanhamento da correção pelo usuário é o UC-004 do docs/07 (`docs/07-casos-de-uso.md`), detalhado em FN-045. Aqui ficam só o contrato assíncrono e a operação.

---

## UC-002-01 — Enviar a foto de um cartão para leitura pela fila

**Ator:** Professor, Aplicador ou outro perfil com a permissão
**Permissão:** `cartao.enviar`
**Escopo:** prova dentro do escopo do vínculo selecionado
**Pré-condições:** prova publicada, em correção ou corrigida; correção não fechada para o usuário.
**Pós-condições:** foto gravada, envio registrado como `na_fila` e job `ProcessarCartaoJob` enfileirado na fila `alta`.
**Tela/rota:** câmera da correção (WEB, FN-045); API `POST /api/v1/provas/{id}/cartoes`.

**Fluxo principal:**
1. O usuário fotografa o cartão; a WEB envia `imagem`, opcionalmente `aluno_id` e `envio_id` (UUID gerado no cliente).
2. A API confere escopo da prova, bloqueio de correção, situação da prova e validações.
3. `FilaCartoes::receber` aplica a barreira de qualidade, grava a foto e cria o envio sob lock pela chave contextual.
4. A API responde `202` com o estado do envio.

**Fluxos alternativos e de erro:**
- 2a. Prova fora do escopo: `404` "Prova não encontrada.".
- 2b. Correção fechada: `403` com `motivo: correcao_fechada`.
- 2c. Prova não publicada: `422` "A prova precisa estar publicada para receber cartões.".
- 2d. Aluno fora das turmas da prova: `422` "O aluno não pertence às turmas desta prova.".
- 3a. Mesma chave já aceita: devolve o envio existente, sem duplicar.
- 3b. Mesma chave usada em outra prova: `409` "Este identificador já foi usado em outra prova.".

**Regras de negócio:** idempotência por chave do envio; revalidação de vínculo e espaço no worker (docs/58).

**Critérios de aceite:**
- [x] Resposta 202 sem esperar a leitura OMR.
- [x] Reenvio com a mesma chave não cria segundo cartão.
- [ ] ⚠ Leitura de um cartão impresso real em produção registrada (pendência da subfase 2.5 e de FN-045).

**Situação:** Implementado — evidência: `CartaoController::store`, `App\Services\Omr\FilaCartoes`, `CartoesFilaTest`, `FilaVinculoTest`.

---

## UC-002-02 — Acompanhar e repetir um envio

**Ator:** autor do envio
**Permissão:** `cartao.enviar`
**Escopo:** só o próprio envio, no vínculo e no escopo original da prova
**Pré-condições:** envio existente.
**Pós-condições:** estado atualizado na tela; em repetição, nova tentativa enfileirada.
**Tela/rota:** API `GET /api/v1/provas/{id}/cartoes/envios/{chave}`, `POST /api/v1/provas/{id}/cartoes/envios/{chave}/repetir`; resumo em `GET /api/v1/provas/{id}/cartoes/status`.

**Fluxo principal:**
1. A WEB consulta o envio pela chave.
2. A API devolve situação (`na_fila`, `processando`, concluído, `recusado`, erro) e resultado.
3. Em erro ou recusa, o usuário pede nova tentativa; a API responde `202`.

**Fluxos alternativos e de erro:**
- 1a. Envio de outro usuário, de outro vínculo ou fora do escopo: `404` "Envio não encontrado.".
- 3a. Correção fechada: `403`; prova não publicada: `422`.

**Regras de negócio:** só o autor acompanha o envio (comentário em `CartaoController::envio`).

**Critérios de aceite:**
- [x] Outro usuário não descobre a existência do envio (404).
- [x] Tentativa antiga não repete efeitos (o job ignora envio que não está ativo).

**Situação:** Implementado — evidência: `CartaoController::envio`, `CartaoController::repetirEnvio`, `ProcessarCartaoJob::envioAtivo`.

---

## UC-002-03 — Processar trabalhos na fila

**Ator:** worker (`gabarito360-worker@N`)
**Permissão:** não se aplica (processo do sistema)
**Escopo:** plataforma
**Pré-condições:** Redis ativo; worker em execução.
**Pós-condições:** trabalho concluído ou registrado em `failed_jobs`.
**Tela/rota:** `php artisan queue:work redis --queue=alta,padrao,baixa`.

**Fluxo principal:**
1. O worker retira trabalhos por prioridade: `alta` (leitura de cartão), `padrao`, `baixa` (auditoria e e-mails).
2. Executa com até 3 tentativas e o tempo-limite do job.

**Fluxos alternativos e de erro:**
- 2a. Falha definitiva: o trabalho vai para `failed_jobs`; o operador consulta com `queue:failed` e reprocessa com `queue:retry`.
- 2b. Leitura do mesmo cartão em paralelo: impedida por `WithoutOverlapping`.

**Regras de negócio:** docs/28 B4.

**Critérios de aceite:**
- [x] Um trabalho que falha aparece em `failed_jobs`.
- [ ] ⚠ Alerta sobre `failed_jobs` (passo 4 de B4) sem mecanismo automático; a consulta é manual. Resolve: FN-006 (A2) e FN-007 (X6).

**Situação:** Parcial — evidência: `infra/systemd/gabarito360-worker@.service`, docs/operacao/deploy.md "Falhas da fila".

---

## UC-002-04 — Executar tarefas agendadas

**Ator:** agendador (`gabarito360-agendador.timer`)
**Permissão:** não se aplica
**Escopo:** plataforma
**Pré-condições:** timer ativo.
**Pós-condições:** tarefas devidas executadas.
**Tela/rota:** `php artisan schedule:run` a cada minuto; tarefas em `apps/api/routes/console.php`.

**Fluxo principal:**
1. O timer chama `schedule:run`.
2. O Laravel executa as tarefas devidas, cada uma com sua condição de flag e `withoutOverlapping` quando declarado.

**Fluxos alternativos e de erro:**
- 1a. Deploy em andamento: o roteiro suspende o timer e o reativa ao final (FN-003).

**Critérios de aceite:**
- [x] Tarefas de cópias só executam com as flags correspondentes ligadas.

**Situação:** Implementado — evidência: `infra/systemd/gabarito360-agendador.*`, `apps/api/routes/console.php`.

---

## UC-002-05 — Copiar e conferir os backups do servidor

**Ator:** operador técnico
**Permissão:** acesso administrativo ao host (fora da aplicação)
**Escopo:** infraestrutura de produção
**Pré-condições:** disco local de destino conectado.
**Pós-condições:** cópias com SHA-256 conferido; datas além das 8 mais recentes removidas.
**Tela/rota:** `scripts/operacao/copiar-backups.ps1` → `proxmox-backup-copia preparar`.

**Fluxo principal:**
1. O roteiro lista os backups do Proxmox e copia o que falta.
2. Confere o SHA-256 de cada arquivo, com até 3 tentativas.
3. Só então remove as datas antigas e desmonta o disco.

**Fluxos alternativos e de erro:**
- 2a. Falha de conferência: registra em log e envia e-mail de alerta, se configurado.

**Critérios de aceite:**
- [x] Arquivo de última cópia válida atualizado.
- [ ] ⚠ Cifra do disco de destino e terceira cópia fora do local (pendências do docs/operacao/backup.md; decisão §4 item 11, FN-062).

**Situação:** Parcial — evidência: [docs/operacao/backup.md](../../operacao/backup-e-restauracao.md), `scripts/operacao/proxmox-backup-copia`.

---

## UC-002-06 — Restaurar o ambiente a partir do backup em local isolado

**Ator:** operador técnico
**Permissão:** acesso administrativo ao Proxmox
**Escopo:** clones isolados, nunca sobre produção
**Pré-condições:** backup conferido (UC-002-05).
**Pós-condições:** ambiente restaurado e comparado.
**Tela/rota:** procedimentos dos docs/52 e docs/100; scripts `scripts/ensaios/t2-*`.

**Fluxo principal:**
1. Restaurar os contêineres em clones sem acesso à produção.
2. Comparar contagens e conferir serviços e HTTP.
3. Registrar o tempo de recuperação observado.

**Critérios de aceite:**
- [x] Restauração real registrada (RTO observado de cerca de 7 a 8,5 minutos, sem valor de SLA).

**Situação:** Implementado (procedimento operacional) — evidência: docs/52 (`docs/52-t2-ensaio-completo-e-prontidao.md`), docs/100 (`docs/100-publicacao-inicial-v1-t5.md`), `docs/operacao/evidencias/t2-restauracao-completa-2026-11-01.json`.

---

## UC-002-07 — Eliminar resultados e recibos de envio vencidos

**Ator:** agendador
**Permissão:** não se aplica
**Escopo:** plataforma
**Pré-condições:** prazos aprovados (resultado detalhado: 30 dias; recibo mínimo: 90 dias, docs/39).
**Pós-condições:** dados do resultado eliminados após 30 dias; recibo reduzido ao mínimo até 90 dias e depois eliminado.
**Tela/rota:** a definir (tarefa agendada na API).

**Fluxo principal (proposto):**
1. A tarefa seleciona envios concluídos com marco vencido.
2. Elimina nome, nota e JSON detalhado após 30 dias, sem reapresentar como resultado atual.
3. Elimina o recibo mínimo após 90 dias, exceto se ainda necessário a uma retentativa.

**Fluxos alternativos e de erro:**
- 3a. Envio com erro pendente de retentativa: não apagar sem tratamento apresentado ao responsável (docs/39).

**Regras de negócio:** docs/39; UUID e IDs relacionados continuam sendo dados vinculáveis.

**Critérios de aceite:**
- [ ] Marco inequívoco dos prazos documentado.
- [ ] Idempotência dos envios preservada durante o prazo.

**Situação:** Não implementado — resolve: subfase 2.6, no pacote de retenção da Onda 3 (decisão §4 item 11).
