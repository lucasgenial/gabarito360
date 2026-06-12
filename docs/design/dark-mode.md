# Dark Mode

## Tema padrão

O Gabarito360 inicia sempre no tema claro. A preferência do sistema operacional
não deve ativar automaticamente o tema escuro na primeira visita.

O cabeçalho deve oferecer um botão acessível para alternância. Após a primeira
escolha explícita, a preferência pode ser persistida por usuário autenticado e,
como contingência, no armazenamento local do navegador.

## 1. Princípio

O modo escuro deve reduzir luminosidade sem usar preto absoluto e sem perder identidade, hierarquia ou acessibilidade. Ele não é uma simples inversão do modo claro.

## 2. Fonte oficial

Usar exclusivamente `themes.dark` de `docs/ui_token_gov_brasil.json`:

- fundos: `background.body`, `surface`, `surfaceAlt`, `elevated` e `modal`;
- textos: `text.primary`, `secondary`, `muted` e `inverse`;
- bordas: `border.default`, `strong` e `focus`;
- ações: `action.primary`, `primaryHover`, `secondary`, `warning`, `danger`, `neutral` e `disabled`;
- sombras: `shadow.darkSm` e `shadow.darkMd`;
- gráficos: `charts.gridDark` e `charts.textDark`.

Os valores do JSON prevalecem sobre exemplos de cor presentes no SDGB.

## 3. Regras

- Não usar `#000000` como fundo principal.
- Não reutilizar automaticamente sombras do tema claro.
- Preservar contraste mínimo de texto e controles.
- Usar o foco amarelo definido por `themes.dark.border.focus`.
- Validar imagens, logos, gráficos e estados semânticos nos dois temas.
- A preferência do sistema operacional não ativa o tema escuro automaticamente; somente a escolha explícita persistida pode alterá-lo.

## 4. Critérios de aceite

- Componentes compartilhados são verificáveis nos dois temas.
- Nenhum conteúdo desaparece ou perde significado no modo escuro.
- Foco, hover, disabled, erro, alerta e sucesso permanecem distinguíveis.
- Contrastes são medidos nos pares realmente renderizados.
- Capturas ou testes visuais selecionados cobrem telas críticas.
