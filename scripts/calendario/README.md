# Calendar.js nos exemplos de agenda

Build local e reprodutível:

```sh
npm ci --ignore-scripts --prefix scripts/calendario
npm run build --prefix scripts/calendario
npm audit --prefix scripts/calendario
```

Versões fixadas: `@calendarjs/ce` 1.1.0, `lemonadejs` 5.3.6 e esbuild 0.28.2.

Fontes do adaptador: `apps/web/resources/js/painel-agenda.js` e `apps/web/resources/css/painel-agenda.css`. O build produz JS/CSS locais em `apps/web/public` e copia as licenças para `public/js/libs/calendarjs`.

O pacote npm Calendar.js declara MIT, mas não inclui o arquivo de licença; `LICENSE-calendarjs.md` é a cópia integral do repositório oficial `https://raw.githubusercontent.com/componade/calendarjs/main/LICENSE.md`. A licença do LemonadeJS vem do pacote fixado.

Uso limitado a Timeline de três widgets demonstrativos explicitamente autorizados. Não usa fontes/CDN remotas, URLs, APIs, criação, edição, exclusão, nem persistência de eventos. A lista HTML permanece como alternativa sem JavaScript. Dados operacionais não são encaminhados ao componente. O LemonadeJS interpreta expressões de seus templates internos com `Function`; esta integração não modifica a política CSP e não autoriza esse uso com templates de usuário.
