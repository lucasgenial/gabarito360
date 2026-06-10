# Dark Mode

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
- Preferência do sistema operacional deve ser respeitada; alteração manual futura deve persistir sem afetar autenticação.

## 4. Critérios de aceite

- Componentes compartilhados são verificáveis nos dois temas.
- Nenhum conteúdo desaparece ou perde significado no modo escuro.
- Foco, hover, disabled, erro, alerta e sucesso permanecem distinguíveis.
- Contrastes são medidos nos pares realmente renderizados.
- Capturas ou testes visuais selecionados cobrem telas críticas.

