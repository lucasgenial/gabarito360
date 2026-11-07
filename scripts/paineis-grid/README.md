# GridStack dos painéis

Versão fixada: GridStack 14.1.0 (MIT), esbuild 0.28.2. Sem dependências de runtime adicionais ou CDN.

```sh
npm ci --ignore-scripts --prefix scripts/paineis-grid
npm run build --prefix scripts/paineis-grid
node --test scripts/tests/painel-linhas.test.mjs
```

Fontes em `apps/web/resources/js/painel-grade.js`, `painel-linhas.js` e `resources/css/painel-grade.css`. O build gera `public/js/painel-grade.js`, `public/css/painel-grade.css` e copia a licença para `public/js/libs/gridstack`.

Uma instância nativa por seção, sem subgrids, aceitação externa, remoção, criação ou duplicação. O GridStack executa o drag/resize. Ao concluir o gesto, o adaptador recompõe linhas que somam 12 colunas e uniformiza a altura pelo maior `altura_desejada`; conflitos de mínimos/máximos geram quebra de linha. Não se usa a antiga engine de vizinhos imóveis.

Somente em viewport CSS de pelo menos 1024×700. Não identifica polegadas físicas. Em viewport menor, nenhuma consulta/salvamento de preferência ocorre e a grade nativa é desmontada, retornando à composição fluida sem sobrescrever o desktop. Ao retornar, busca o estado atual do servidor.

Salvamento explícito via `G360Requisicao`, nunca `grid.save()` bruto. DTO projetado: `id`, `section`, `x`, `y`, `w`, `h`, `altura_desejada`. `resumo` é sempre fixo e fica fora das instâncias e do payload. `padrao.desktop` autorizado pelo servidor é usado quando não há override e na restauração local, que só persiste após Salvar.
