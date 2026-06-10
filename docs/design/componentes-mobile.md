# Componentes Mobile Flutter

## 1. Escopo

O aplicativo Android deve mapear os tokens oficiais para o tema Flutter no MP-040A e criar a biblioteca compartilhada no MP-040B. Telas funcionais não devem definir cores, tipografia, raios ou espaçamentos diretamente.

## 2. Tema Flutter

- Mapear temas claro e escuro para `ThemeData` e extensões semânticas quando necessário.
- Mapear tipografia Rawline e fallbacks oficiais.
- Mapear `semantic.*`, `spacing.*`, `radius.*`, `shadow.*` e `motion.*`.
- Usar Material Symbols como biblioteca oficial de ícones.
- Centralizar tokens e evitar valores repetidos em widgets.

## 3. Componentes fundamentais

| Componente | Aplicação mobile |
|---|---|
| AppButton | ações primária, secundária, perigo e textual; loading e disabled |
| AppTextField | label, ajuda, erro, teclado adequado e ação de limpar |
| AppSelect/DatePicker | seleção acessível e confirmação clara |
| AppCard | aplicações, alunos, resultados e alertas |
| AppBadge | status online, pendente, lido, alerta e sincronização |
| AppDialog/BottomSheet | confirmação e tarefas contextuais |
| AppToast/Banner | feedback não bloqueante e acessível |
| AppEmptyState | ícone, mensagem e próxima ação |
| AppLoadingState | skeleton ou progresso curto |
| AppErrorState | mensagem humana, retry e suporte quando aplicável |
| AppNavigationBar | no máximo cinco itens |

## 4. Componentes operacionais específicos

- Card de aplicação com escola, turma, prova, data, status e progresso.
- Item de aluno com identificação mínima e estado da leitura.
- Indicador de conexão e sincronização.
- Guia de captura com instruções de enquadramento, iluminação e nitidez.
- Grade de respostas para conferência, destacando branco, dupla marcação e baixa confiança por texto, ícone e cor.
- Confirmação de aluno e código impresso antes do envio.
- Resumo de resultado com acertos, alertas e estado de sincronização.

## 5. Interação

- Bottom Navigation possui no máximo cinco itens.
- FAB é opcional e reservado à ação predominante, como nova leitura.
- Alvos de toque devem ter pelo menos `44x44px`; preferir alturas tokenizadas de `48px` ou mais.
- Mensagens não devem depender somente de cor.
- Ação destrutiva ou confirmação de leitura deve exigir texto claro e contexto.
- Estados offline futuros devem ser visualmente distintos dos estados de erro.

## 6. Critérios de aceite

- Tema claro e escuro usam exclusivamente tokens oficiais ou extensões documentadas.
- Widgets compartilhados cobrem estados padrão, foco, disabled, loading e erro.
- Fluxos principais funcionam com leitor de tela e escalonamento de texto.
- Navegação e foco não bloqueiam uso com teclado ou tecnologias assistivas disponíveis.
- `flutter analyze`, testes de widgets e golden tests selecionados passam.

