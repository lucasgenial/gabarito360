# 078 — Governança executável — Casos de uso

**Natureza:** nova
**Perfis envolvidos:** responsável (lucasgenial), agentes de IA do time ([02-agentes.md](../../planejamento/02-agentes.md)), CI do GitHub
**Atualizado em:** 2026-11-10

Cada caso de uso é verificável. Os critérios de aceite viram testes da CI ou conferências registradas por texto.

---

## UC-078-01 — Bloquear um PR que viola uma regra verificável

**Ator:** CI do GitHub
**Escopo:** repositório inteiro
**Pré-condições:** a regra está registrada em `docs/decisoes/regras.yml` com gravidade "bloqueia".
**Pós-condições:** o PR com violação nova não pode ser mesclado.

**Fluxo principal:**
1. Um agente ou uma pessoa abre um PR.
2. A CI roda todas as regras registradas.
3. A CI compara cada resultado com a linha de base.
4. A CI encontra uma violação nova.
5. A CI reprova o job e mostra a regra, o ADR de origem, o arquivo e a linha.

**Fluxos alternativos:**
- 4a. Nenhuma violação nova: a CI aprova a etapa.
- 4b. A regra tem gravidade "avisa": a CI mostra a violação e não reprova.

**Critérios de aceite:**
- [ ] Um `onclick` novo numa view reprova a CI.
- [ ] Um `confirm(` novo num arquivo JavaScript reprova a CI.
- [ ] Uma violação já registrada na linha de base não reprova a CI.

**Situação:** Não implementado.

---

## UC-078-02 — Impedir a autoria de IA em commits

**Ator:** gancho local de commit e CI do GitHub
**Pré-condições:** o gancho está ativado no ambiente (`core.hooksPath`).
**Pós-condições:** nenhum commit na `main` tem autoria ou coautoria de IA.

**Fluxo principal:**
1. Uma ferramenta de IA prepara um commit com a linha `Co-Authored-By` de IA.
2. O gancho `commit-msg` remove a linha antes de gravar o commit.
3. O commit fica com o responsável como único autor.

**Fluxos alternativos:**
- 2a. O gancho não está ativado: o commit chega ao PR, e a CI reprova o PR com a lista dos commits em violação.
- 2b. O autor do commit não é o responsável: a CI reprova o PR.

**Critérios de aceite:**
- [ ] Um commit com `Co-Authored-By: Claude …` reprova a CI.
- [ ] Um commit com "Generated with" reprova a CI.
- [ ] O gancho remove as duas linhas quando está ativado.

**Situação:** Não implementado.

---

## UC-078-03 — Mudar um princípio e propagar a mudança

**Ator:** responsável (decide); agente de impacto (propaga)
**Pré-condições:** o responsável expressou uma decisão nova ou alterou uma decisão existente.
**Pós-condições:** o princípio novo está registrado, verificado e incorporado ao planejamento das funcionalidades afetadas.

**Fluxo principal:**
1. O responsável expressa a decisão.
2. Um agente registra a decisão como ADR, com os campos de máquina.
3. Se a decisão substitui outra, o agente marca a antiga como "Substituída por ADR-NNNN".
4. O script de princípios sobe a versão em `docs/decisoes/VERSAO`.
5. O agente de impacto lê o campo `afeta` do ADR.
6. O agente de impacto lista as funcionalidades, os documentos e os trechos de código afetados.
7. O agente de impacto gera a subfase "Revalidar contra ADR-NNNN" de cada funcionalidade afetada em `docs/planejamento/04-adequacoes.md`.
8. O plano mestre aponta para essa lista na trilha de qualidade. A regra `R-VERSAO-PRINCIPIOS-FN` obriga a adequação quando a funcionalidade é alterada.
9. O script regera o `AGENTS.md` e o `CLAUDE.md`.
10. A CI confirma que tudo está consistente.

**Fluxos alternativos:**
- 6a. O princípio é verificável por código: o agente cria o teste e registra a regra em `regras.yml`. As violações atuais entram na linha de base.
- 6b. O princípio exige julgamento: o agente registra o tipo `revisao-ia`, e o revisor de conformidade passa a considerá-lo.

**Critérios de aceite:**
- [ ] Um ADR novo sem campo `verificacao` reprova a CI.
- [ ] Um ADR novo sem subir a versão dos princípios reprova a CI.
- [ ] O `AGENTS.md` regerado inclui o princípio novo.
- [ ] Cada funcionalidade listada em `afeta` recebe a subfase de adequação.

**Situação:** Não implementado.

---

## UC-078-04 — Revalidar uma funcionalidade antes de alterá-la

**Ator:** CI do GitHub e agente que executa a funcionalidade
**Pré-condições:** o `planejamento.md` registra a versão dos princípios com que foi validado.
**Pós-condições:** nenhuma funcionalidade é alterada com base em princípios superados.

**Fluxo principal:**
1. Um PR altera o código ou os documentos de uma funcionalidade.
2. A CI lê a versão de princípios do `planejamento.md` da funcionalidade.
3. A CI encontra um ADR mais recente que afeta a funcionalidade.
4. A CI reprova o PR e indica o ADR e a subfase de adequação pendente.
5. O agente aplica a adequação, atualiza o `planejamento.md` com a versão atual e atualiza o PR.

**Fluxos alternativos:**
- 3a. Nenhum ADR mais recente afeta a funcionalidade: a CI atualiza o aviso e aprova a etapa.

**Critérios de aceite:**
- [ ] Um PR numa funcionalidade com versão superada reprova.
- [ ] Depois da adequação e da atualização da versão, o mesmo PR aprova.

**Situação:** Não implementado.

---

## UC-078-05 — Exigir o vínculo entre código, funcionalidade e documentação

**Ator:** CI do GitHub
**Pré-condições:** o PR altera arquivos em `apps/`.
**Pós-condições:** toda mudança de código tem funcionalidade e documentação atualizadas.

**Fluxo principal:**
1. A CI lê o título e a descrição do PR.
2. A CI encontra pelo menos uma referência FN-NNN válida no catálogo.
3. A CI confirma que o PR altera o `planejamento.md` dessa funcionalidade.

**Fluxos alternativos:**
- 2a. Nenhuma FN válida: a CI reprova o PR.
- 3a. O planejamento não foi alterado: a CI reprova o PR.

**Critérios de aceite:**
- [ ] Um PR com mudança em `apps/` e sem FN reprova.
- [ ] Um PR com FN válida e planejamento atualizado aprova a etapa.

**Situação:** Não implementado.

---

## UC-078-06 — Revisar a conformidade com regras de julgamento

**Ator:** revisor de conformidade (agente de IA na CI)
**Pré-condições:** a chave de API está configurada como segredo do GitHub; o PR altera `apps/`, `docs/decisoes/` ou `docs/funcionalidades/`.
**Pós-condições:** o PR tem um veredito automático registrado.

**Fluxo principal:**
1. A CI envia ao revisor o diff, os ADRs que afetam os caminhos alterados e os documentos das funcionalidades citadas.
2. O revisor compara a mudança com os princípios e com os casos de uso.
3. O revisor publica um comentário no PR com o veredito: aprovado, aprovado com ressalvas ou reprovado.
4. Quando o veredito é "reprovado", a CI bloqueia o merge.

**Fluxos alternativos:**
- 1a. A chave não está configurada: a CI avisa e não bloqueia.
- 3a. O custo do mês passou do limite: a CI avisa e usa só as regras determinísticas.

**Critérios de aceite:**
- [ ] Um PR que contraria um ADR de interface recebe "reprovado".
- [ ] O comentário cita o ADR e o trecho em violação.
- [ ] Nenhum segredo, dado pessoal ou print aparece no comentário.

**Situação:** Não implementado.

---

## UC-078-07 — Proteger a `main`

**Ator:** GitHub
**Pré-condições:** a proteção da branch está configurada.
**Pós-condições:** a `main` só muda por PR aprovado pela CI.

**Fluxo principal:**
1. Uma pessoa ou um agente tenta enviar um commit direto para a `main`.
2. O GitHub recusa o envio.
3. A mudança segue por PR, com a CI obrigatória.

**Critérios de aceite:**
- [ ] Um envio direto para a `main` é recusado.
- [ ] Um envio forçado para a `main` é recusado.
- [ ] Um PR com a CI reprovada não pode ser mesclado.

**Situação:** Não implementado.

---

## UC-078-08 — Auditar o projeto periodicamente

**Ator:** fluxo agendado do GitHub e agente gerente de projetos
**Pré-condições:** o fluxo agendado está ativo.
**Pós-condições:** existe um relatório semanal de divergências.

**Fluxo principal:**
1. O fluxo agendado roda todas as verificações sobre a `main`.
2. O fluxo compara o catálogo e a arquitetura com o código.
3. O fluxo publica o relatório de divergências como issue.
4. O gerente de projetos transforma cada divergência em subfase de adequação na funcionalidade dona.

**Critérios de aceite:**
- [ ] O relatório é gerado sem ação humana.
- [ ] Cada divergência cita a funcionalidade e o documento afetados.

**Situação:** Não implementado.
