---
id: ADR-0058
titulo: Leitura de provas no aparelho, com paridade e modo servidor com créditos
estado: Aceita
substituida_por: []
data: 2026-11-11
afeta_funcionalidades: [FN-079, FN-080]
afeta_caminhos: ["apps/web/**", "apps/api/**"]
verificacao: revisao-ia
regras: []
agents_secao: Planejamento
agents_ordem: 60
agents_resumo:
  - "A leitura das folhas (núcleo OMR e Escriba) roda no aparelho, para reduzir o custo do servidor. Aparelho e servidor usam o mesmo código, as mesmas versões de OpenCV e NumPy e o mesmo modelo e versão do Escriba (paridade total). A nota é calculada só no servidor."
  - "Não existe uso offline. Nada é persistido no aparelho. O envio leva a imagem original, reduzida a 2400 px. O servidor não relê: quem confere é sempre o usuário. Leitura com dificuldade pede nova imagem, reenvio sem custo (modo servidor) ou revisão manual, que é o caminho principal."
  - "Aparelho sem capacidade usa o modo servidor: fila, aviso prévio de créditos e de fila, e marcador de identificação no arquivo. O marcador só encaminha: o QR prevalece. Os créditos de correção existem só no servidor: um por correção de prova de um aluno."
  - "A correção da prova não fecha com folha pendente. A folha pode ser salva com pendências e corrigida depois, na revisão."
---
# ADR-0058 — Leitura de provas no aparelho, com paridade e modo servidor com créditos

**Data:** 2026-11-11
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** decisões D-1 a D-5, D-7 a D-9, D-11 e D-12 do responsável, em 11/11/2026, no planejamento do Leitor360 (§6.12 e §8)
**Afeta:** [FN-079](../funcionalidades/079-leitor360/planejamento.md), [FN-080](../funcionalidades/080-ia-local-transcricao/planejamento.md); [06 OMR e documentos](../arquitetura/06-omr-e-documentos.md)

## Contexto

A leitura das folhas pelo servidor custa processamento a cada foto. O Leitor360 (FN-079) e o Escriba (FN-080) podem rodar no aparelho do usuário e reduzir esse custo. Nem todo aparelho tem capacidade para isso. O resultado precisa ser o mesmo nos dois lados, senão a nota depende do aparelho. A decisão também precisa dizer o que acontece sem internet e o que fica guardado no aparelho.

## Decisão

O responsável decidiu o conjunto abaixo em 11/11/2026. O planejamento da FN-079 (§6.12 e §8) guarda o detalhe de cada item.

1. **Leitura no aparelho.** O núcleo de leitura (OMR) e o Escriba rodam no aparelho para reduzir o custo do servidor.
2. **Paridade total (D-4, D-5).** O aparelho e o servidor usam o mesmo código, as mesmas versões de OpenCV e NumPy, e o mesmo modelo e a mesma versão do Escriba. Para a mesma imagem, o resultado é igual.
3. **Nota só no servidor.** O aparelho lê. O servidor calcula a nota.
4. **Sem releitura pelo servidor (D-1).** Quem confere a leitura é sempre o usuário. Reler só existe em dois casos: o Escriba no aparelho, com imagem ruim, pede nova imagem e/ou revisão manual; no modo servidor, se o Escriba tem dificuldade, o usuário reenvia só os alunos com problema, sem cobrar crédito, e/ou manda para a revisão manual. A revisão manual é sempre o caminho principal. Problema de qualidade sempre exige revisão manual.
5. **Sem uso offline e nada no aparelho (D-7, D-8, opção a).** Sem conexão, o Leitor360 não abre e não salva. Não existe fila offline. Nenhuma foto nem dado fica guardado no aparelho.
6. **Imagem original enviada (D-12, opção a).** O envio leva a imagem original da captura, reduzida a 2400 px, com a orientação EXIF aplicada.
7. **Modo servidor (D-2, D-3, D-9).** Se o aparelho não tem capacidade para o núcleo ou para o Escriba, o usuário continua corrigindo pelo app e o servidor processa. O mesmo vale para o lote de PDF acima do limite do navegador.
   - O usuário recebe dois avisos antes do primeiro envio: os créditos de correção serão consumidos, e a correção entra numa fila com aviso ao terminar.
   - O envio é foto por foto, com fila, posição e tempo estimado.
   - O aparelho identifica a folha pelo QR (prova, aluno e página) e grava um marcador de identificação no arquivo de imagem. O servidor não processa só para identificar.
   - O marcador só encaminha. O servidor revalida permissão e escopo. Se o QR diverge do marcador, o QR prevalece.
   - Sem QR legível, a imagem vai sem marcador.
8. **Créditos só no servidor.** O processamento no aparelho nunca consome créditos. O modo servidor consome um crédito de correção por correção de prova de um aluno (aluno × prova), qualquer que seja o número de páginas. Sem saldo, o envio é recusado. O reenvio por dificuldade do servidor não cobra. A chave `leitura.transcrever` não cobra crédito no aparelho.
9. **Correção com trava (D-11, opção b).** A folha pode ser salva com pendências e fica marcada "com pendência". A revisão corrige a pendência depois. A correção da prova não fecha enquanto houver folha pendente.

## Consequências

- Todo código de leitura tem uma só fonte, usada no aparelho e no servidor. Um conjunto de referência prova a paridade e é portão de publicação.
- É proibido guardar foto, leitura ou fila de envio no aparelho.
- É proibido o servidor trocar em silêncio o resultado conferido pelo usuário.
- O sistema de créditos de correção ainda não existe. Ele está na FN-081 (candidata) e bloqueia as subfases 4.3 e 6.3 da FN-079.
- O preço do crédito e o formato do marcador (proposta: segmento XMP no JPEG) são definidos nas subfases.
- ⚠ Sem releitura no servidor, a proteção contra resultado adulterado no cliente depende da conferência humana, da imagem original guardada, da nota só no servidor e da auditoria.
- ⚠ Não está confirmado o que o Pyodide empacota de OpenCV e NumPy. A subfase 1.1 decide a saída (D-5).
- Esta decisão não substitui ADR anterior.

## Verificação

- Revisão de IA (`revisao-ia`): o agente de conformidade confere, em cada PR da FN-079 e da FN-080, que o aparelho não persiste dados, que a nota nasce no servidor e que o QR prevalece sobre o marcador.
- O conjunto de referência confere a paridade entre aparelho e servidor antes de publicar.
- O AGENTS.md gerado cita esta decisão na seção Planejamento.
