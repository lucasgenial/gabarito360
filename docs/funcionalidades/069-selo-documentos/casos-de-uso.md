# 069 — Selo dos documentos — Casos de uso

**Natureza:** nova
**Perfis envolvidos:** todos os que imprimem ou exportam documentos (professor, coordenador, direção, núcleo, rede); Financeiro e Suporte definem a modalidade do cliente em outras funcionalidades. Ver [docs/03](../../03-perfis-e-permissoes.md).
**Atualizado em:** 2026-11-10

Nenhum caso de uso está implementado. O UC-010 do [docs/07](../../07-casos-de-uso.md) (exportar relatório/PDF) é o fluxo hospedeiro e não é repetido. O único dado existente é `clientes.modalidade`.

---

## UC-069-01 — Imprimir ou exportar documento de cliente presenteado

**Ator:** usuário com permissão de relatório ou impressão no espaço
**Permissão:** a do documento (por exemplo, `relatorio.turma`); nenhuma nova
**Escopo:** o do documento
**Pré-condições:** cliente do espaço com modalidade `presenteado`
**Pós-condições:** documento impresso ou PDF com o bloco institucional do Gabarito360 e QR
**Tela/rota:** impressões e PDFs dos relatórios; `components/selo-documento.blade.php` (a criar)

**Fluxo principal:**
1. Usuário imprime ou exporta um relatório.
2. O sistema resolve `Selo::do(espaco)` e inclui o bloco institucional com QR.

**Fluxos alternativos e de erro:**
- 2a. Falha ao resolver a modalidade: usa a variante definida como padrão pelo responsável; nunca bloqueia a impressão.

**Regras de negócio:** docs/28 R1 passos 1, 2 e 5.

**Critérios de aceite:**
- [ ] Bloco presente no papel e no PDF, ausente na tela.
- [ ] Sem dados do aluno no bloco.

**Situação:** Não implementado — subfase 1.1 (R1).

---

## UC-069-02 — Imprimir ou exportar documento de cliente pagante

**Ator:** usuário com permissão de relatório ou impressão
**Permissão:** a do documento
**Escopo:** o do documento
**Pré-condições:** cliente do espaço com modalidade `pagante`
**Pós-condições:** documento apenas com "Feito no gabarito360.com.br"
**Tela/rota:** impressões e PDFs dos relatórios

**Fluxo principal:**
1. Usuário imprime ou exporta.
2. O sistema inclui só a linha discreta.

**Fluxos alternativos e de erro:**
- 1a. Modalidades `teste` e `interno`: comportamento a decidir (§8 do planejamento).

**Regras de negócio:** docs/28 R1 passo 3.

**Critérios de aceite:**
- [ ] Teste cobre as duas modalidades.

**Situação:** Não implementado — subfase 1.1 (R1).

---

## UC-069-03 — Gerar cartões-resposta e PDFs de resultado com o selo

**Ator:** professor, coordenador (geração de cartões e resultados)
**Permissão:** as de cartões e relatórios já existentes
**Escopo:** prova e turmas do escopo
**Pré-condições:** selo implementado nos geradores `omr/gerar_cartoes.py` e `omr/gerar_resultado.py`
**Pós-condições:** PDF com o selo da modalidade, sem alterar a área de leitura
**Tela/rota:** geração de cartões e de resultados (FN-040, FN-045, FN-049)

**Fluxo principal:**
1. Usuário gera o PDF.
2. O gerador recebe a variante do selo e a desenha fora das áreas de marcadores, QR e campos.

**Fluxos alternativos e de erro:**
- 2a. Variante não informada: gera sem bloco institucional e registra o fato.

**Regras de negócio:** docs/28 R1 passo 4; docs/161 (preservar marcadores, QR, campos e posições do cartão).

**Critérios de aceite:**
- [ ] Cartão com selo é lido corretamente pelo OMR (teste de leitura).
- [ ] Impressão conferida em A4, registrada por texto.

**Situação:** Não implementado — subfase 1.1 (R1).

---

## UC-069-04 — Refletir a mudança de modalidade do cliente

**Ator:** Financeiro, Suporte ou Desenvolvedor (alteram a modalidade em FN-023/FN-068); sistema
**Permissão:** `cliente.gerir` no contexto interno
**Escopo:** cliente e seus espaços
**Pré-condições:** modalidade alterada no cadastro do cliente
**Pós-condições:** documentos gerados a partir daí usam o novo selo; documentos já emitidos não são reescritos
**Tela/rota:** cadastro do cliente (FN-023); efeito nas impressões

**Fluxo principal:**
1. A modalidade muda de `presenteado` para `pagante` (ou o inverso).
2. A próxima impressão ou PDF usa o selo correspondente.

**Fluxos alternativos e de erro:**
- 2a. PDF em cache: a chave de cache considera a variante do selo.

**Regras de negócio:** docs/28 R1 passo 1 (regra central).

**Critérios de aceite:**
- [ ] Teste de troca de modalidade sem resíduo do selo anterior.

**Situação:** Não implementado — subfase 1.1 (R1).
