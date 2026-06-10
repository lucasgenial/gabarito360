# Acessibilidade

## 1. Meta

Todas as interfaces do Gabarito360 devem atender WCAG 2.2 nível AA. Acessibilidade é critério de aceite, não ajuste posterior.

## 2. Requisitos transversais

- Contraste mínimo de `4.5:1` para texto comum e `3:1` para texto grande.
- Navegação completa por teclado nas interfaces web.
- Foco visível usando tokens do tema.
- Labels obrigatórios e associados aos campos.
- Mensagens de erro ligadas ao campo e escritas em linguagem humana.
- Leitor de tela deve anunciar nomes, estados e alterações relevantes.
- Cor nunca deve ser o único indicador.
- Suporte a movimento reduzido.
- Hierarquia correta de títulos e landmarks.
- Áreas de toque adequadas em mobile.

## 3. Componentes

- **Formulários:** instruções antes da entrada, erros específicos e resumo quando necessário.
- **Modais e drawers:** foco contido, fechamento previsível e retorno ao elemento acionador.
- **Tabelas:** cabeçalhos associados, ordenação anunciada e alternativa responsiva.
- **Gráficos:** título, legenda, valores acessíveis e alternativa tabular.
- **Toasts e alertas:** anúncio sem interromper indevidamente a tarefa.
- **Loading:** informar progresso; evitar estado indefinido prolongado.
- **Empty state:** explicar a situação e oferecer ação possível.

## 4. Conteúdo

- Usar frases curtas e termos conhecidos pelo público escolar.
- Explicar ações de risco antes da confirmação.
- Não expor mensagens técnicas, stack traces ou identificadores internos.
- Diferenciar pendência, alerta e erro de forma consistente.
- Evitar abreviações sem explicação.

## 5. Verificação por plataforma

### Web

- teclado sem mouse;
- ordem de foco;
- landmarks e títulos;
- leitor de tela;
- zoom e reflow;
- contraste e modo escuro.

### Flutter

- Semantics e rótulos de controles;
- TalkBack;
- escalonamento de texto;
- alvos de toque;
- contraste;
- orientação e tamanhos de tela homologados.

## 6. Critérios para concluir um componente

- Estados relevantes foram testados.
- Uso por teclado ou tecnologia assistiva foi verificado.
- Contraste foi medido.
- Erros possuem recuperação clara.
- Existe alternativa para informação transmitida visualmente.

