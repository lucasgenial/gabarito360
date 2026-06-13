# Tela: Configurações (`configuracoes.html`)

- **Rota web:** `/configuracoes` (seções via âncora, ex.: `#integracoes`)
- **Módulo:** Conta
- **Atores/permissões:** usuário autenticado; seções administrativas (plano/uso,
  integrações, zona de perigo) exigem permissão de gestão.
- **Objetivo:** centralizar preferências do sistema, unidade e conta —
  aparência, idioma/região, acessibilidade, importação/exportação, plano/uso,
  integrações, privacidade e zona de perigo.
- **Shell:** ver [`_shell.md`](_shell.md).

## Layout e componentes

- **Layout 2 colunas:** **navegação lateral** agrupada (Geral / Sistema / Conta)
  e conteúdo em `section-card` por seção.

### Seções (do mockup)

| Âncora | Seção | Conteúdo |
|---|---|---|
| `#aparencia` | Aparência | Tema (**Claro padrão**, Escuro, Alto contraste, Automático/SO) via `option-card`; densidade |
| `#idioma` | Idioma e Região | idioma, região/fuso, formato de data |
| `#acessibilidade` | Acessibilidade | preferências (contraste, movimento, tamanho) via toggles |
| `#importacao` | Importação / Exportação | importar/exportar dados (planilhas) |
| `#plano` | Plano e Uso | `plan-card` com plano atual, uso e limites |
| `#integracoes` | Integrações | `integration-item` com catálogo, status, conectar/testar/desconectar |
| `#privacidade` | Privacidade | consentimentos, dados pessoais, solicitações LGPD |
| `#zona-perigo` | Zona de Perigo | ações destrutivas (encerrar conta/dados) — adaptação LGPD |

## Controles e ações

| Controle | Tipo | Ação | Endpoint | Regra |
|---|---|---|---|---|
| Tema (option-card) | radio | define tema | `PATCH /api/v2/me/preferencias` | claro é padrão; persistido |
| Idioma/região/data | select | preferências | idem | — |
| Toggles de acessibilidade | toggle | preferências | idem | persistido por usuário/dispositivo |
| Importar/Exportar | botão | dados | `POST /api/v2/importacoes` · `GET /api/v2/exportacoes` | idempotente; autorizado/auditado |
| Plano e Uso | leitura | plano/limites | `GET /api/v2/plano-uso` | estado real |
| Conectar/Testar/Desconectar integração | botão | gerencia integração | `POST/DELETE /api/v2/integracoes/{id}` | segredos criptografados; status real |
| Privacidade / solicitações LGPD | botão | consentimentos/solicitações | `POST /api/v2/solicitacoes-lgpd` | rastreável |
| Zona de perigo | botão | ação destrutiva | `POST /api/v2/solicitacoes-lgpd` | inativação/anonimização, não exclusão direta |

## Dados exibidos

| Campo | Origem | Observação |
|---|---|---|
| Preferências (tema, idioma, acessibilidade) | `preferencias_usuarios` | persistidas |
| Plano e uso | `planos_uso` | estado real de limites |
| Integrações | `integracoes`, `credenciais_integracoes` | status/última execução; segredos ocultos |
| Privacidade | `consentimentos`, `solicitacoes_lgpd`, `politicas_retencao` | rastreável |

## Estados

`default`, `hover`/`focus`, `selected` (option-card), `loading`, `success`,
`error`, `access_denied` (seções de gestão). Integrações: conectada / com
atenção / desconectada (estados reais).

## Regras de negócio

- **Tema claro é o padrão**; escuro/alto contraste opcionais e persistidos por
  usuário/dispositivo.
- Integrações têm catálogo, status, conexão, teste, sincronização, última
  execução e erros; **segredos criptografados e nunca retornados completos**.
- Importação/exportação idempotentes, autorizadas e auditadas.
- Zona de perigo e "excluir dados" seguem adaptação segura LGPD (inativação,
  anonimização ou solicitação rastreável) — sem exclusão direta de dados pessoais.

## Responsividade

`config-layout` 2→1 coluna ≤900px (nav vira linha); `option-grid` 2 colunas;
`form-row` 1 coluna. Sem overflow horizontal.

## Endpoints `/api/v2` necessários

- `GET/PATCH /api/v2/me/preferencias` — aparência, idioma, acessibilidade.
- `GET /api/v2/plano-uso` — plano e limites.
- `GET/POST/DELETE /api/v2/integracoes` — catálogo e gestão.
- `POST /api/v2/integracoes/{id}/testar` · `/sincronizar` — operação.
- `POST /api/v2/importacoes` · `GET /api/v2/exportacoes` — dados.
- `GET/POST /api/v2/solicitacoes-lgpd` — privacidade/zona de perigo.

## Pendências/decisões

- Definir o catálogo inicial de integrações e seus contratos de conexão.
- Confirmar quais seções exigem permissão de gestão vs usuário comum.
