# 18 — OMR Nativo e Cartões-Resposta (MP-021 — implementação)

> **Aprovação:** solicitado e aprovado por Lucas Matos em 29/10/2026 ("funcionalidade total: geração do
> gabarito, marcação das respostas corretas, turmas e correção pela foto do celular"), para uso no
> Simulado ENEM 2026 das 3ª séries. Completa as decisões deixadas "a definir" em `08-omr.md`.

---

## Decisões

| Tema | Decisão |
|------|---------|
| Motor OMR | `NativoOmrDriver` (PHP) executa `apps/api/omr/ler_cartao.py` (Python + OpenCV) via `Process`. Plugável por `OMR_DRIVER=nativo|simulado`. |
| Identificação do aluno | **Cartão pré-impresso** com nome/matrícula + **QR** `G360|P{prova}|A{aluno}|N{questões}|K{alternativas}|L{0/1}`. Cartões em branco (A0) caem na **vinculação manual pós-leitura**. |
| Geometria | 4 marcadores quadrados pretos (8 mm) nos cantos da A4 → correção de perspectiva. Layout gerado por `omr/layout.py`, compartilhado entre gerador e leitor; o QR descreve o layout impresso. |
| Geração do PDF | `GET /v1/provas/{id}/cartoes/pdf?turma_id=&brancos=` → `omr/gerar_cartoes.py` (ReportLab). Exceção documentada à regra "API só JSON": é **download de arquivo**, não tela. |
| Leitura | Fração preenchida do miolo de cada bolha, com fundo normalizado (sombra). ≥ 0,55 marcada; 0,30–0,55 dúvida (ambígua); 2+ marcadas = múltiplas. Confiança = 0,5 + (f1 − f2). |
| Língua estrangeira | Prova com `opcao_lingua`: Q01–05 têm gabarito Inglês (`alternativa`) e Espanhol (`alternativa_espanhol`). A língua marcada no cartão vai para `cartoes.lingua` e a `CalculadoraNota` usa o gabarito correspondente. |
| Status "Corrigida" | Passa a exigir **um cartão lido por aluno ativo** das turmas da prova (antes virava "corrigida" no 1º cartão e bloqueava os seguintes). Novo cartão em prova corrigida a reabre para "em correção". |
| Fila | Com `QUEUE_CONNECTION=sync` o upload devolve o resultado na hora (aluno, acertos, nota, ambíguas) para o celular. |

## Fluxo no sistema

1. Prova (ou `php artisan g360:importar-provas`) com gabarito publicado e turmas vinculadas.
2. Tela da prova → **🖨 Cartões** por turma (e "5 cartões em branco").
3. Aplicação.
4. **Acompanhar correção** → **📷 Fotografar cartão** (ou "Enviar várias fotos"). A foto é reduzida no
   celular (2400 px, JPEG 90%) antes do envio.
5. Ambíguos → fila de revisão existente. Sem aluno → seletor "Vincular" no próprio resultado.
6. Relatório por turma (nota de cada aluno) disponível já em "em correção".

## Comandos de importação

```bash
php artisan g360:importar-alunos {escola_id} alunos.csv --serie="3ª série" --turno=tarde --ano=2026
#   CSV: CLASS;NAME;MATRICULA (idempotente pela matrícula; cria turmas que faltarem)
php artisan g360:importar-provas database/data/simulado-enem-2026.json --escola=ID --usuario=ID --turmas=3A,3B --ano=2026
#   cria 4 provas (LC, CH, CN, MT), 45 questões, gabarito oficial FGV publicado
```

## Validação realizada

- Leitor Python: fotos simuladas (perspectiva, rotação, sombra, ruído, JPEG 80%) para 10/20/45/90
  questões e 4/5 alternativas — 0 erros de leitura; QR, aluno e língua corretos.
- Ponta a ponta em MariaDB local: PDF pela API e pelo WEB, upload pela tela do celular (Pixel 7
  emulado), nota com gabarito de Espanhol, cartão em branco vinculado manualmente, relatório da turma.
- **Pendente:** teste com foto real de cartão impresso.

## Dependências no servidor

`python3`, `python3-venv`; `infra/deploy.sh` cria `apps/api/omr/.venv` e instala `omr/requirements.txt`
(opencv-python-headless, numpy, reportlab). `.env` da API: `OMR_DRIVER=nativo`,
`OMR_PYTHON=/var/www/gabarito360/apps/api/omr/.venv/bin/python`.
