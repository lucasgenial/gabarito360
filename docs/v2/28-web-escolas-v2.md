# Etapa Web V2: lista de escolas

## Objetivo

Implementar a rota web `/escolas` a partir do mockup canônico em
`style-system/escolas.html`, usando dados reais do backend V2 e sem reutilizar a
interface administrativa anterior.

## Referências obrigatórias consultadas

- `style-system/escolas.html`
- `docs/v2/telas/escolas.md`
- `docs/v2/15-matriz-rastreabilidade.md`
- `docs/design/componentes-web.md`
- `docs/ui_token_gov_brasil.json`
- `docs/SDGB.md`

## Escopo desta etapa

- shell autenticado V2 já em uso;
- KPIs reais do contexto autorizado;
- grid de cards de escola com status, contato e contadores;
- busca na listagem;
- modal único para criar e editar;
- reativação de escola inativa;
- integração com endpoints `/api/v2/escolas`.

## Ajuste técnico documentado

O mockup da modal não exibe seleção de núcleo. No backend V2, a criação de
escola exige vínculo com um núcleo autorizado.

Para preservar o fluxo do mockup sem perder integridade organizacional:

- quando o ator possui exatamente um núcleo gerenciável, o núcleo é resolvido
  automaticamente;
- quando o ator possui mais de um núcleo gerenciável, a modal exibe um campo
  adicional de seleção de núcleo antes do salvamento;
- quando o ator não possui permissão de gestão de escolas, a ação de criação não
  é exibida.

Esse ajuste não reaproveita comportamento legado; ele só completa o contrato do
backend V2 com base no escopo real do usuário.

## Dados necessários na view

- paginação de escolas autorizadas;
- KPIs agregados: total, ativas, alunos e turmas ativas;
- lista de núcleos gerenciáveis pelo ator para criação;
- capacidade de editar/reativar por policy.

## Evidência esperada

- rota `/escolas` renderizada em shell V2;
- cards e modal consistentes com o mockup;
- ações funcionando com dados reais e autorização por escopo.
