# 034 — Importação e exportação de alunos — Casos de uso

**Natureza:** nova (sobre o comando de terminal existente)
**Perfis envolvidos:** Secretário Escolar, Coordenador, Diretor Escolar, Administrador da Rede; Vice-diretor e Professor por extra — ver docs/03 (`docs/03-perfis-e-permissoes.md`)
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve.

---

## UC-034-01 — Importar alunos pelo terminal (operação assistida)

**Ator:** operador técnico agindo em nome de um usuário autorizado
**Permissão:** o usuário indicado precisa de `aluno.importar`, `aluno.gerir` e `turma.gerir`
**Escopo:** escola (espaço da escola)
**Pré-condições:** arquivo CSV com cabeçalho `CLASS`, `NAME`, `MATRICULA` (separador `;` ou `,`), escola existente.
**Pós-condições:** com `--aplicar`, turmas ausentes criadas pelo nome/ano e alunos criados ou atualizados pela matrícula.
**Tela/rota:** sem tela; `php artisan g360:importar-alunos {escola_id} {arquivo} --usuario= [--vinculo=] [--espaco=] [--ano=] [--turno=] [--serie=] [--aplicar]`

**Fluxo principal:**
1. O comando resolve o contexto do usuário e confere as permissões.
2. Valida cada linha (turma até 50, nome até 200, matrícula até 20) e matrículas repetidas no arquivo.
3. Sem `--aplicar`, informa "Simulação: N linhas válidas; nada gravado.".
4. Com `--aplicar`, trava ator, espaço e escola, revalida e grava em transação.

**Fluxos alternativos e de erro:**
- 2a. Turma arquivada → "Desarquive a turma antes de importar alunos."
- 2b. Aluno arquivado → "Desarquive o cadastro do aluno pelo fluxo próprio antes de importar."
- 2c. Aluno existente em outra turma → "A importação não transfere alunos entre turmas. Utilize a transferência de turma."
- 2d. Matrícula de outra escola ou de outro espaço, ou turma fora do escopo do usuário → recusa (409/404).

**Regras de negócio:** matrícula nunca transfere aluno de outra instituição.

**Critérios de aceite:**
- [ ] Simulação não grava nada.
- [ ] Nenhuma transferência implícita.

**Situação:** Implementado (somente terminal) — evidência: `apps/api/app/Console/Commands/ImportarAlunos.php`.

---

## UC-034-02 — Importar alunos por planilha na tela

**Ator:** Secretário Escolar, Coordenador, Diretor Escolar, Administrador da Rede
**Permissão:** `aluno.importar` (chave já no catálogo, sem rota)
**Escopo:** escola
**Pré-condições:** formato da planilha aprovado; turma de destino ativa.
**Pós-condições:** importação registrada com recibo; alunos criados ou atualizados pela matrícula; aviso ao concluir.
**Tela/rota:** mockup `turmas.html` (botão "Importar planilha"); rotas a definir

**Fluxo principal:**
1. O ator baixa o modelo da planilha com colunas documentadas.
2. Envia o arquivo preenchido.
3. O sistema mostra a prévia: linhas válidas, linhas com erro (com o motivo por linha) e efeito (criar/atualizar).
4. O ator confirma; a importação segue pela fila.
5. Ao concluir, o ator é avisado e vê o resumo.

**Fluxos alternativos e de erro:**
- 3a. Arquivo fora do formato → mensagem sem gravar nada.
- 4a. Reenvio da mesma confirmação → mesmo recibo, sem duplicar.
- 4b. Aluno em outra turma → recusado, com orientação para a transferência (FN-036).

**Critérios de aceite:**
- [ ] Testes cobrem importação com erros e repetição.
- [ ] Atualiza pela matrícula sem duplicar aluno.
- [ ] Nada fora do escopo do ator é lido ou gravado.

**Situação:** Não implementado — origem: docs/28 A5 passos 1–6 (subfases 1.1–1.3). ⚠ Hoje há só o aviso "A importação por planilha ainda não está disponível." em `membros/perfis`.

---

## UC-034-03 — Exportar alunos

**Ator:** Secretário Escolar, Coordenador, Diretor Escolar, Administrador da Rede
**Permissão:** a definir (docs/28 propõe `alunos.exportar`)
**Escopo:** escola | rede
**Pré-condições:** —
**Pós-condições:** arquivo gerado só com o escopo do ator.
**Tela/rota:** a definir

**Fluxo principal:**
1. O ator filtra a lista de alunos.
2. Pede a exportação; recebe o arquivo.

**Critérios de aceite:**
- [ ] Exportação respeita o escopo.
- [ ] Campos exportados aprovados conforme LGPD (FN-062).

**Situação:** Não implementado — origem: docs/28 A5 passo 7 (subfase 1.4).
