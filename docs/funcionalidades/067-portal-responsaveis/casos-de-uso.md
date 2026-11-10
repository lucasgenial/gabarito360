# 067 — Portal dos responsáveis — Casos de uso

**Natureza:** nova
**Perfis envolvidos:** responsável pelo aluno (sem conta de usuário); secretaria da escola (Secretário Escolar e perfis com cadastro de aluno, a confirmar em P1); Desenvolvedor e Suporte (auditoria). Ver docs/03 (`docs/03-perfis-e-permissoes.md`).
**Atualizado em:** 2026-11-09

Todos os casos abaixo são propostos a partir do docs/28 Fase 8 e do docs/38. Nenhum está implementado. Relaciona-se ao UC-005 (resultado individual do aluno) e ao UC-010 (PDF) do docs/07 (`docs/07-casos-de-uso.md`), que tratam a visão interna.

---

## UC-067-01 — Fazer o primeiro acesso e receber o código

**Ator:** responsável pelo aluno
**Permissão:** nenhuma (rota pública protegida por Turnstile e limites)
**Escopo:** um aluno de escola com portal habilitado
**Pré-condições:** aluno sem código ativado; termo do responsável vigente (UC-067-02).
**Pós-condições:** código gerado, cifrado, exibido uma única vez; primeiro acesso registrado e auditado.
**Tela/rota:** tela de primeiro acesso do portal (a criar); API em `Api/Portal/`.

**Fluxo principal:**
1. O responsável informa matrícula e data de nascimento e resolve o Turnstile.
2. O sistema confere os dados e gera código de 10 caracteres sem letras ou números parecidos.
3. Mostra o código uma vez, com orientação para guardá-lo.

**Fluxos alternativos e de erro:**
- 2a. Dados incorretos: mesma resposta genérica para qualquer erro.
- 2b. Aluno já com código ativado: mesma resposta genérica; orientar procurar a secretaria.
- 2c. Excesso de tentativas por IP ou matrícula: bloqueio progressivo.

**Regras de negócio:** docs/28 P3; R01 e R03 do docs/38.

**Critérios de aceite:**
- [ ] Teste de repetição do primeiro acesso.
- [ ] Teste de enumeração (respostas indistinguíveis).

**Situação:** Não implementado — subfase 7.3.

---

## UC-067-02 — Aceitar o termo do responsável

**Ator:** responsável pelo aluno
**Permissão:** nenhuma
**Escopo:** acesso do aluno
**Pré-condições:** termo revisado (D14) e publicado.
**Pós-condições:** aceite com versão registrado; autorização de comunicações registrada à parte.
**Tela/rota:** etapa do primeiro acesso (a criar).

**Fluxo principal:**
1. O sistema mostra o termo do docs/33 §4.
2. O responsável aceita o termo.
3. Opcionalmente, autoriza comunicações.

**Fluxos alternativos e de erro:**
- 3a. Recusa das comunicações não bloqueia o portal.

**Regras de negócio:** docs/28 P2; RL7; R11 do docs/38.

**Critérios de aceite:**
- [ ] Teste confirma acesso ao portal sem autorização de comunicações.

**Situação:** Não implementado — subfase 7.2.

---

## UC-067-03 — Entrar no portal e consultar resultados

**Ator:** responsável pelo aluno
**Permissão:** nenhuma (matrícula + código + Turnstile)
**Escopo:** somente o aluno do código
**Pré-condições:** código ativo.
**Pós-condições:** sessão de 15 minutos; acesso auditado.
**Tela/rota:** entrada e tela de resultados do portal (a criar).

**Fluxo principal:**
1. O responsável informa matrícula e código.
2. O sistema abre a tela de resultados, sem menu do sistema, com logos da escola e do núcleo.
3. Mostra as notas por prova, sem dados de outros alunos.

**Fluxos alternativos e de erro:**
- 1a. Dados incorretos: resposta genérica; limites por IP e matrícula.
- 2a. Sessão vencida: volta à entrada.

**Regras de negócio:** docs/28 P3 e P5; RL14 (sem rastreamento); `noindex` e `no-store`.

**Critérios de aceite:**
- [ ] Teste de segurança confirma que outro aluno não é acessível (R02).
- [ ] Nenhum token na URL.

**Situação:** Não implementado — subfases 7.3 e 7.5.

---

## UC-067-04 — Imprimir ou baixar o PDF dos resultados

**Ator:** responsável pelo aluno
**Permissão:** sessão do portal
**Escopo:** o próprio aluno
**Pré-condições:** sessão ativa; selo (FN-069) disponível.
**Pós-condições:** PDF com logos, selo e marca-d'água com data, hora e código do acesso (nunca o código de entrada).
**Tela/rota:** ação na tela de resultados (a criar).

**Fluxo principal:**
1. O responsável escolhe imprimir ou baixar.
2. O sistema gera o PDF.

**Fluxos alternativos e de erro:**
- Nenhum envio por e-mail nesta versão.

**Regras de negócio:** docs/28 P6; R10 do docs/38.

**Critérios de aceite:**
- [ ] Selo segue a modalidade do cliente e não aparece na tela.

**Situação:** Não implementado — subfase 7.6.

---

## UC-067-05 — Consultar, gerar outro e imprimir códigos na secretaria

**Ator:** secretaria da escola
**Permissão:** a definir em P1 (só a secretaria recupera o código, docs/28 §4)
**Escopo:** alunos da escola
**Pré-condições:** P3 implementado.
**Pós-condições:** consulta, geração e impressão auditadas; código anterior invalidado quando há nova geração.
**Tela/rota:** início do cadastro do aluno (`alunos/show`) e impressão por turma (a criar).

**Fluxo principal:**
1. A secretaria abre o cadastro do aluno e vê o código em destaque, com datas do primeiro e do último acesso.
2. Se necessário, gera outro código.
3. Imprime os códigos da turma para entrega em papel antes do primeiro acesso.

**Fluxos alternativos e de erro:**
- 1a. Outro perfil consulta o aluno: a API não devolve o código.

**Regras de negócio:** docs/28 P4; R01 e R03 do docs/38.

**Critérios de aceite:**
- [ ] Teste de API nega o código ao professor e a outros perfis.

**Situação:** Não implementado — subfase 7.4.

---

## UC-067-06 — Receber o código por e-mail

**Ator:** responsável pelo aluno
**Permissão:** nenhuma
**Escopo:** contato confirmado do responsável
**Pré-condições:** contatos confirmados (FN-035), modelos de e-mail (FN-060 E4) e preferências (FN-057 N3).
**Pós-condições:** código enviado e auditado.
**Tela/rota:** a definir.

**Fluxo principal:**
1. O responsável pede o envio do código.
2. O sistema envia para o contato confirmado.

**Fluxos alternativos e de erro:**
- 2a. Sem contato confirmado: orientar procurar a secretaria.

**Regras de negócio:** docs/28 P8 (segunda versão).

**Critérios de aceite:**
- [ ] Nenhum código em log ou URL.

**Situação:** Não implementado — subfase 7.8.
