# Diretriz da família G360 Icones

Todos os agentes de lote seguem este documento. O lote 1 (Plano e uso e Leitor360) já foi aprovado e é a **referência visual**.

## 1. Especificação (obrigatória)

- **Grade:** 24 × 24. Área útil 2..22. Nada passa de 2..22, nem com a espessura do traço. O selo pode encostar em 22.
- **Traço:** 1,8, pontas e junções redondas. Ele vem da raiz do SVG; não redefina `stroke-width`.
- **Coordenadas:** múltiplos de 0,25. **Nunca use `transform`**, porque a escala muda a espessura. Calcule os pontos.
- **Linhas-guia (centro do traço):**
  - círculo r 9 (3..21);
  - quadrado 4..20;
  - folha retrato 5..19 × 3..21;
  - paisagem 3..21 × 6..18.

  Ícones da mesma linha precisam ter o mesmo peso ótico. Compare sempre com a REFERÊNCIA do lote 1 na prancha.
- **Folga:** no mínimo 1,2 px visíveis entre traços. A distância entre centros fica ≥ 3,0.
- **Raios:** 2 nos contornos externos e 1 em detalhes internos.
- **Pontos:** círculos cheios com `fill="currentColor" stroke="none"`, r 0,95 a 1,6.
- **Tracejado:** `stroke-dasharray` funciona. Prefira traços que comecem e terminem nos cantos.
- **Letras e números** só quando o próprio sinal é o conceito: T de texto, Σ de fórmula, ! de alerta, ? de ajuda, i de informação. Desenhe-os com traço. Não use texto SVG.

## 2. As duas variantes, pelas classes

Cada elemento recebe uma classe. O compilador gera as duas variantes a partir dela.

| classe | contorno | preenchido |
|---|---|---|
| `corpo` | traço | forma cheia |
| *(nenhuma)* = detalhe | traço | **recorte** dentro do corpo (XOR); fora do corpo, fica tinta |
| `fixo` | traço | traço, nunca recortado |
| `sc` | traço | não aparece |
| `sp` | não aparece | forma cheia (corpo) |
| `folga` | área retirada de tudo | idem |
| `selo` | desenhado por último | idem |

**Regra do preenchido:** a variante preenchida é o mesmo desenho com o corpo cheio e os detalhes vazados, no estilo Material.
- Detalhe colado na borda do corpo vira entalhe. Mantenha 1,5 px de distância da borda ou cruze-a de propósito.
- Ícone sem corpo fechado (seta, Σ, T) pode ter o preenchido igual ao contorno.
- O preenchido serve para **item ativo do menu**, por isso precisa ser reconhecível ao lado do contorno.

**Selo (badge):** é um modificador no canto inferior direito, centro (17,5; 17,5), conteúdo dentro de r 4,5.
- Use `selo_folga()` para abrir espaço no desenho-base e desenhe o selo com `class="selo"`.
- Use selo só quando o conceito for "base + modificador": instituição + editar, pessoa + engrenagem.

## 3. Motivos compartilhados (`motivos.py`, congelado)

Os motivos são `CIRCULO`, `FOLHA`, `DOCUMENTO` + `DOBRA`, `CALENDARIO`, `CAMERA` + `CAMERA_LENTE`, `LUPA`, `vale()` + `PICOTE`, `CHECK`, `CHECK_INTERNO`, `XIS`, `XIS_INTERNO`, `MAIS`, `MAIS_INTERNO`, `MENOS`, `MENOS_INTERNO`, `PONTO()`, `seta(graus)`, `chevron(graus)`, `seta_circular()`, `pessoa(cx, topo, s)` e `selo_folga()`.

- Se o conceito usa uma dessas formas, **importe o motivo**. Não redesenhe.
- Se precisar de um motivo novo que outro lote também usaria, defina no seu arquivo com prefixo `m_` e **relate no relatório final**.

## 4. Mapa de metáforas reservadas

Uma metáfora tem um dono. Você não pode usar uma metáfora reservada para outro conceito. Se o seu conceito precisa dela, combine com selo.

| metáfora | dono (id) | lote |
|---|---|---|
| vale (ticket) | crédito de correção: correcoes-disponiveis, adicionais (+), consumo-operacao (−) | 1 |
| escudo | cobertura-plano | 1 |
| ampulheta | operacoes-processamento | 1 |
| calendário com check | franquia-ciclo; calendário sem sinal → calendario | 1 / 6 |
| medidor | consumo-ciclo | 1 |
| presente | beneficio | 1 |
| documento assinado | contratacao | 1 |
| seta circular em volta de folha | reenvio | 1 |
| folha + ! / folha tracejada | leitura-pendente / pagina-faltando | 1 |
| ◉ bolha marcada | tipo-objetiva | 1 |
| linha de teto | teto-periodo, teto-operacao | 1 |
| pilha de documentos | lote-pdf | 1 |
| webcam / fotos | webcam / galeria | 1 |
| grade de 4 blocos | painel; grade + seta saindo → abrir-painel | 2 / 3 |
| pessoa dentro de círculo | perfil | 2 |
| pessoa com capelo | aluno | 2 |
| duas pessoas | equipe | 2 |
| três pessoas | turma | 2 |
| pessoa + selo (engrenagem, chave/crachá, ⇄) | usuarios-configuracao, acessos-internos, trocar-vinculo | 2 |
| maleta | clientes | 2 |
| prédio com frontão e colunas | instituicao; + selo: solicitacao (+), instituicao-gestao (lápis), instituicoes-cadastro (lista) | 2 |
| prédio com bandeira | escola | 2 |
| livro | disciplina | 2 |
| folha com linhas de questão (bolinha + linha) | prova; duas provas sobrepostas → copias | 2 |
| moldura de leitura (4 cantos) com folha | corrigir (≠ câmera) | 2 |
| documento com barras | relatorio | 2 |
| pilha de vales | plano-e-uso | 2 |
| engrenagem | configuracoes | 2 |
| porta com seta saindo | sair | 2 |
| sol / lua | tema-claro / tema-escuro | 2 |
| três traços / três pontos verticais | menu / mais-vertical; três pontos horizontais → opcoes | 2 / 3 |
| lápis | editar | 3 |
| lixeira | excluir | 3 |
| disquete | salvar | 3 |
| caixa de arquivo | arquivar | 3 |
| dois retângulos sobrepostos | copiar | 3 |
| impressora | imprimir | 3 |
| bandeja + seta ↓ / ↑ | baixar / enviar-arquivo | 3 |
| lupa; lupa + / − | buscar; ampliar / diminuir | 3 |
| funil; funil + × | filtro; limpar-filtro | 3 |
| olho; olho riscado | ver; ocultar | 3 |
| duas setas circulares | atualizar | 3 |
| seta curva para a esquerda | desfazer | 3 |
| seis pontos | arrastar | 3 |
| alfinete | fixar | 3 |
| quatro cantos para fora | tela-cheia | 3 |
| tabela (grade com cabeçalho) / cards (2×2 com cantos) | exibir-tabela / exibir-cards (≠ painel) | 3 |
| seta reta (`seta`) | voltar, avancar | 4 |
| chevron (`chevron`) | chevron-* | 4 |
| ⇄ duas setas opostas | transferir | 4 |
| quadrado + seta diagonal | link-externo | 4 |
| círculo + check / × / i / ? | sucesso / erro / informacao / ajuda | 5 |
| triângulo + ! | alerta | 5 |
| check sozinho / × sozinho | confirmar / fechar | 5 |
| folha + check | leitura-conferida | 5 |
| zigue-zague com seta | tendencia-alta / tendencia-baixa; seta reta → estável | 5 |
| cadeado fechado / aberto | cadeado / desbloqueado | 5 |
| sino | notificacao | 5 |
| nuvem riscada | sem-conexao | 5 |
| spinner (arco aberto) | carregando | 5 |
| cartão de pagamento + selo relógio | aguardando | 5 |
| cronômetro | periodo-teste | 5 |
| botão liga/desliga | situacao | 5 |
| câmera (`CAMERA`) | camera | 6 |
| relógio | relogio | 6 |
| chave | senha | 6 |
| folha com grade de bolhas | cartao-resposta | 6 |
| barras com base / horizontais / linha | grafico-barras / grafico-barras-horizontais / grafico-linha | 6 |
| documento (`DOCUMENTO`) com linhas | documento; documento + lápis → rascunho | 6 |
| documento com "PDF" desenhado em traço não; use selo ou dobra | pdf | 6 |
| QR | qr-code | 6 |
| lanterna | lanterna | 6 |
| envelope / telefone / pino de mapa | email / telefone / local | 6 |
| crachá com código (barras) | inep | 6 |
| cilindro de dados | banco-questoes | 7 |
| medalha | olimpiada | 7 |
| pena escrevendo | escriba (nunca robô, cérebro ou "IA") | 7 |
| caixa de questão (quadrado 4..20) + sinal | acerto, erro-questao, anulada, em-branco, marcacao-dupla; com linhas → questao | 7 |
| estrela | nota | 7 |
| carimbo | curadoria | 7 |
| folha com bolhas + selo chave | gabarito | 7 |
| prova + selo cronômetro / ▶ | simulado / aplicacao | 7 |
| zigue-zague que assenta na horizontal | tendencia-estavel (≠ avancar) | 5 |

O lote 7 decide os demais (questao, gabarito, simulado, aplicacao, nota, acerto, erro-questao, anulada, em-branco, marcacao-dupla, curadoria). Ele **não pode** colidir com nada desta tabela. acerto e erro-questao não podem parecer sucesso e erro (círculo); use a bolha de questão ou uma caixa.

## 5. Como trabalhar (comandos)

Pasta: `/home/claude/icones/familia`. Seu arquivo: `lotes/loteN.py`, com `ICONES = {id: "<elementos SVG>"}` na ordem do catálogo. Seu rascunho: `lotes/revisao-loteN/`.

```
python3 gerar_fontes.py --lote N lotes/revisao-loteN/src
python3 compilar.py --src lotes/revisao-loteN/src --saida lotes/revisao-loteN/saida --sem-registro
python3 prancha_revisao.py lotes/revisao-loteN/saida lotes/revisao-loteN/prancha.html
google-chrome --headless=new --no-sandbox --disable-gpu --window-size=900,<altura> --screenshot=lotes/revisao-loteN/prancha.png "file:///home/claude/icones/familia/lotes/revisao-loteN/prancha.html"
```

Abra o PNG com a ferramenta Read e olhe de verdade. Se a prancha for alta, recorte em partes com PIL. Corrija e repita até passar no checklist.

## 6. Checklist de conferência (cada ícone)

1. O significado é lido em 24 px sem legenda? Em 16 px ainda se distingue dos vizinhos do mesmo lote?
2. Nada sai de 2..22. Nenhum traço cortado.
3. Folgas ≥ 1,2 px, sem traços se fundindo em 16 e 20 px.
4. O peso ótico é igual ao da REFERÊNCIA do lote 1. Não pode ficar maior, menor, mais denso ou mais vazio.
5. O preenchido é reconhecível, sem entalhe acidental, sem ilha solta e sem buraco que vire mancha em 16 px.
6. Funciona no tema escuro.
7. Não colide com metáfora reservada de outro conceito.
8. Usa os motivos compartilhados quando cabem.

## 7. Proibido

- Editar `motivos.py`, `compilar.py`, `gerar_fontes.py`, `registro.json`, `src/`, `saida/`, `fonte/` ou o arquivo de outro lote.
- Rodar `compilar.py` sem `--sem-registro`.
- Copiar o desenho de qualquer família existente (Font Awesome, Material, Lucide, Heroicons…). As metáforas são universais; o desenho é nosso.
- Acessar o computador do usuário.
- Criar id fora da lista do seu lote. Se achar que falta um conceito, relate.

## 8. Relatório final (curto)

1. Arquivo e quantidade de ícones.
2. Tabela id → metáfora, uma linha cada.
3. O que corrigiu na conferência.
4. Dúvidas ou colisões que você não resolveu.
5. Motivos novos (`m_*`) que outro lote poderia usar.

Caminho da prancha final em PNG.
