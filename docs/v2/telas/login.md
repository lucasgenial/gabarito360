# Tela: Acesso ao sistema (`login.html`)

- **Rota web:** `/login`
- **Módulo:** Acesso
- **Atores/permissões:** público (não autenticado). Pós-autenticação redireciona
  ao painel do ator.
- **Objetivo:** autenticar usuários institucionais e iniciar o cadastro
  controlado de novos usuários.

## Layout e componentes

Layout de duas colunas (`auth-wrap`, grid `1.1fr / 1fr`):

- **Aside de marca** (`auth-aside`): logotipo "G360 Gabarito360", título-chamada,
  parágrafo descritivo e três estatísticas (`12s` por cartão, `98,6%` precisão
  OMR, `340` escolas). Oculto abaixo de 860px.
- **Cartão de acesso** (`auth-card`, máx. 408px): eyebrow "Secretaria de
  Educação", título "Acesso ao sistema", abas e os dois formulários.
- **Abas** (`tabs`, `role=tablist`): "Entrar" e "Cadastrar", alternando os panes.

## Controles e ações

| Controle | Tipo | Ação | Endpoint/Evento | Regra/Validação |
|---|---|---|---|---|
| Aba Entrar/Cadastrar | tab | troca o pane visível | — (cliente) | aria-selected; um pane ativo |
| E-mail institucional | input email | credencial | `POST /api/v2/auth/login` | regex `\S+@\S+\.\S+`; erro "Informe um e-mail válido." |
| Senha | input password | credencial | idem | mín. 6 caracteres; erro "A senha deve ter ao menos 6 caracteres." |
| Manter conectado | checkbox | sessão longa | flag em `auth/login` | marcado por padrão |
| Esqueci a senha | link | recuperação | `POST /api/v2/auth/forgot-password` | fluxo de e-mail de redefinição |
| Entrar no painel | botão primário | autenticar | `POST /api/v2/auth/login` | bloqueia submit se inválido; redireciona ao painel do ator |
| Nome completo | input text | cadastro | `POST /api/v2/onboarding` | obrigatório |
| CPF | input text (máscara) | cadastro | idem | máscara `000.000.000-00`, 11 dígitos; validar CPF |
| Perfil | select | cadastro | idem | opções: Coordenação/Secretaria, Professor(a), Diretor(a) |
| E-mail institucional (cadastro) | input email | cadastro | idem | e-mail válido; domínio institucional |
| Aceite termos + LGPD | checkbox | consentimento | idem | obrigatório para criar conta |
| Criar conta | botão primário | solicitar cadastro | `POST /api/v2/onboarding` | registra consentimento LGPD |

## Dados exibidos

| Campo | Origem (tabela/campo) | Observação |
|---|---|---|
| Estatísticas do aside (12s / 98,6% / 340) | `snapshots_indicadores` | estáticos no mockup → consulta real (ou conteúdo institucional configurável) |
| Opções de Perfil | `perfis` | catálogo real de perfis disponíveis ao cadastro |

## Estados

- **default/hover/focus/active:** abas, inputs e botões.
- **error:** mensagens `err` em e-mail e senha (classe `.show`).
- **loading:** botão "Entrar"/"Criar conta" durante a requisição.
- **success:** redireciona ao painel.
- **disabled:** "Criar conta" enquanto termos não aceitos (recomendado).
- **access_denied:** credenciais inválidas → mensagem genérica sem revelar qual
  campo falhou.

## Regras de negócio

- **Cadastro controlado (não aberto):** "Criar conta" gera uma
  `solicitacoes_cadastro`/`convites_usuarios` com validação institucional e
  aprovação — nunca acesso imediato (inventário, adaptação segura ADR-D014).
- **Consentimento LGPD** registrado no ato do cadastro (`consentimentos`).
- **Autenticação** via Sanctum; "Manter conectado" controla expiração do token.
- Mensagem de erro de login não distingue e-mail inexistente de senha errada.

## Responsividade

- ≥ 861px: duas colunas (aside + cartão).
- ≤ 860px: aside oculto; cartão centralizado ocupando a largura.
- Validar nos 9 viewports sem rolagem horizontal; inputs com alvo de toque ≥ 44px.

## Endpoints `/api/v2` necessários

- `POST /api/v2/auth/login` — autenticação (e-mail, senha, manter_conectado).
- `POST /api/v2/auth/forgot-password` — solicitação de redefinição.
- `POST /api/v2/auth/reset-password` — redefinição via token (tela de destino do e-mail).
- `POST /api/v2/onboarding` — solicitação de cadastro (nome, cpf, perfil, email, consentimento).
- `GET /api/v2/onboarding/perfis` — catálogo de perfis para o select.

## Pendências/decisões

- Definir a tela/rota de redefinição de senha (destino do e-mail) — não há HTML
  dedicado no mockup; criar como adaptação segura.
- Confirmar se o cadastro usa convite (token) ou solicitação com aprovação;
  ambos atendem o mockup. Recomendado: solicitação + aprovação por gestor.
- "gov.br" citado no rodapé: tratar como integração futura, não bloqueante do MVP.
