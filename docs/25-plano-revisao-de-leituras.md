# Plano: fotos e nova revisão das leituras

Situação em 30/10/2026: aprovado. R1 em andamento. Os processos da web e da API já foram separados no servidor.

## Problemas

- **Fotos:** a tela pede todas as imagens originais, cada uma passando pela web e pela API. Com 47 cartões, os processos se esgotam, as fotos não aparecem e o site para.
- **Revisão:** a lista mistura alunos e questões. Para decidir, o professor abre a foto inteira, procura a questão e volta para a lista.
- **Respostas:** a tela não oferece "em branco" nem "anulada". Hoje a API só aceita de A a E.
- **Prova 4:** 5 cartões aguardam revisão, com 16 questões pendentes, e ficam sem nota até serem resolvidos.

## O que o sistema já permite

- A leitura endireita a foto usando os quatro marcadores do cartão.
- O layout conhece a posição exata de cada bolinha.
- Portanto, dá para mostrar só a linha da questão, sem o professor procurar na foto inteira.

## Nova dinâmica: fila de revisão

Um botão **Revisar leituras (16)** abre uma tela só para isso, com uma questão por vez:

```
Yasmin Silva Dias · Cartão 1 de 5 · Questão 37 · Faltam 16

[ recorte ampliado da linha da questão 37, com as bolinhas ]

Leitura do sistema: B (marcação fraca)

[ A ] [ B ] [ C ] [ D ] [ E ] [ Em branco ] [ Anulada ]

Ver cartão inteiro · Voltar · Pular
```

- Ao escolher a resposta, ela salva e passa sozinha para a próxima questão.
- Quando o último ponto do cartão é resolvido, a nota é calculada e a tela avisa.
- **Voltar** permite trocar a resposta recém-salva.
- **Pular** deixa a questão no fim da fila.
- No computador, as teclas A a E respondem, com atalhos também para "em branco" e "anulada".
- No celular, os botões ficam grandes e ao alcance do polegar.
- Quando a fila acaba, a tela mostra um resumo e volta para a correção.
- A lista de corrigidos deixa de mostrar os ambíguos misturados. Ela só indica quantas questões faltam em cada aluno.

## Etapas

### R1. Fotos leves e sem travamento

- Criar, para cada cartão, uma miniatura pequena e uma versão endireitada da foto.
- Gerar esses arquivos na leitura e também para os cartões já existentes.
- Entregar as imagens por link temporário assinado, indo direto à API. Assim a foto ocupa um processo da API só por um instante, e a web não fica presa esperando.
- Carregar as miniaturas conforme aparecem na tela, poucas por vez.
- As miniaturas voltam à lista, substituindo o botão do PR #37.

### R2. Tela de revisão

- Criar a fila descrita acima, com o recorte de cada questão.
- Incluir "em branco" e "anulada", registrando quem decidiu e quando.
- Usar a mesma tela para revisar qualquer questão de um cartão já lido.

### R3. Menos revisões desnecessárias

- Medir as decisões reais dos professores em comparação com a leitura.
- Só então ajustar os limites de preenchimento. As marcações de exemplo da prova 4 ficaram entre 45% e 63% de preenchimento, abaixo da confiança exigida.
- Esta etapa não muda nenhuma nota já decidida.

## Decisões a confirmar

1. "Anulada" conta como erro, sem pontos para o aluno, e não muda o gabarito da prova.
2. O PR #37 entra primeiro, para o site parar de travar. As miniaturas voltam na R1.

## Testes

- Abrir a tela com 47 cartões e confirmar que o site segue respondendo.
- Revisar um cartão até o fim e confirmar a nota.
- Testar teclado, celular, foto torta e cartão sem marcadores.
- Conferir que outro professor sem acesso à prova não abre os links de imagem.
