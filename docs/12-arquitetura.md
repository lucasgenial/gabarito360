# 12 — Arquitetura

---

## Visão Geral

```
┌─────────────────────────────────────────────────────────────────┐
│                          CLIENTES                               │
│                                                                 │
│  ┌─────────────┐  ┌──────────────┐  ┌────────────────────────┐ │
│  │  apps/web   │  │ apps/android │  │      apps/ios          │ │
│  │  (Laravel)  │  │ (React Native│  │    (React Native)      │ │
│  └──────┬──────┘  └──────┬───────┘  └───────────┬────────────┘ │
│         │                │                       │              │
└─────────┼────────────────┼───────────────────────┼─────────────┘
          │                │                       │
          └────────────────┼───────────────────────┘
                           │ HTTPS / JSON
                           ▼
          ┌────────────────────────────┐
          │        apps/api            │
          │        (Laravel)           │
          │                            │
          │  ┌──────────────────────┐  │
          │  │   Business Logic     │  │
          │  ├──────────────────────┤  │
          │  │   OMR Service        │  │
          │  ├──────────────────────┤  │
          │  │   Report Service     │  │
          │  ├──────────────────────┤  │
          │  │   Auth (Sanctum/JWT) │  │
          │  └──────────────────────┘  │
          └───────────┬────────────────┘
                      │
          ┌───────────▼────────────────┐
          │          MariaDB           │
          └────────────────────────────┘
```

---

## Aplicações

### apps/api (Laravel)
**Única responsável por:**
- Autenticação e autorização
- Regras de negócio
- Acesso ao banco de dados
- Processamento OMR
- Geração de relatórios
- Integrações externas (SEGES)

**Proibido:**
- Renderizar HTML
- Conter lógica de apresentação
- Expor dados fora do escopo do usuário

**Estrutura de diretórios sugerida:**
```
apps/api/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Models/
│   ├── Services/
│   │   ├── OmrService.php
│   │   ├── NotaService.php
│   │   ├── RelatorioService.php
│   │   └── IntegracaoSegesService.php
│   ├── Policies/
│   └── Enums/
├── database/
│   ├── migrations/
│   └── seeders/
└── routes/
    └── api.php
```

---

### apps/web (Laravel)
**Única responsável por:**
- Interface administrativa
- Dashboards
- Formulários e interações do usuário
- Consumo da API

**Proibido:**
- Acessar o banco de dados diretamente
- Implementar regras de negócio
- Criar tokens de acesso direto ao banco

**Estrutura de diretórios sugerida:**
```
apps/web/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Middleware/
│   └── Services/
│       └── ApiClient.php  (HTTP client para a API)
├── resources/
│   └── views/
│       ├── auth/
│       ├── dashboard/
│       ├── escolas/
│       ├── turmas/
│       ├── alunos/
│       ├── provas/
│       ├── correcao/
│       ├── resultados/
│       ├── relatorios/
│       ├── equipe/
│       ├── perfil/
│       └── configuracoes/
└── routes/
    └── web.php
```

---

### apps/android e apps/ios (React Native)
**Únicas responsáveis por:**
- Experiência mobile nativa
- Captura de imagem dos cartões-resposta (câmera)
- Consumo da API

**Proibido:**
- Acessar banco de dados diretamente

---

## Banco de Dados

**MariaDB** — único banco aprovado.

### Estratégia de Migrations
- Uma migration por entidade principal
- Migrations de relacionamentos separadas das entidades
- Migrations de índices adicionais separadas
- Nunca alterar migrations já executadas em produção (criar nova migration)

---

## Autenticação

### Laravel Sanctum (API Tokens)
- Tokens pessoais para WEB e Mobile
- Middleware `auth:sanctum` em todas as rotas protegidas da API
- Token enviado via header: `Authorization: Bearer {token}`

### Fluxo de Login
```
1. POST /api/v1/auth/login (e-mail + senha)
2. API valida credenciais
3. API cria token Sanctum
4. API retorna: { token, user: { id, nome, perfil, escopo } }
5. Cliente armazena token (cookie httpOnly no WEB, SecureStorage no mobile)
6. Todas as requisições subsequentes enviam o token no header
```

---

## Autorização (RBAC)

### Políticas Laravel (Policies)
Cada entidade terá uma Policy com os métodos:
- `viewAny` — listar
- `view` — ver detalhe
- `create` — criar
- `update` — editar
- `delete` — excluir
- Métodos adicionais por ação específica (ex.: `publish`, `activate`)

### Validação de Escopo
Além do perfil, o acesso é validado pelo escopo:
```php
// Exemplo: professor só acessa suas próprias provas
Gate::define('view-prova', function (User $user, Prova $prova) {
    return $user->perfil === 'professor' && $prova->criado_por === $user->id
        || $user->perfil === 'coordenador' && $prova->escola_id === $user->escola_id
        || in_array($user->perfil, ['dir_escolar', 'dir_nucleo', 'admin_rede']);
});
```

---

## Camada de Serviços

As regras de negócio residem em Services, não em Controllers.

| Service                    | Responsabilidade                               |
|----------------------------|------------------------------------------------|
| OmrService                 | Processamento de imagens e leitura OMR         |
| NotaService                | Cálculo de notas, médias e aprovação           |
| RelatorioService           | Geração de relatórios consolidados             |
| DashboardService           | Agregação de dados para cada tipo de dashboard |
| IntegracaoSegesService     | Sincronização com SEGES                        |
| CartaoRespService          | Geração de PDF do cartão-resposta              |
| GabaritoService            | Publicação e validação de gabaritos            |

---

## Processamento OMR

### Estratégia de Fila (Assíncrona)
```
Upload de imagem
      ↓
Job na fila (Laravel Queue)
      ↓
OmrService::processar($cartao)
      ↓
Resultado salvo no banco
      ↓
Evento disparado
      ↓
WebSocket / Polling atualiza a UI
```

### Motor OMR
- Interface `OmrDriverInterface` com método `processar(string $imagePath): OmrResult`
- Implementação inicial: a definir no MP-021
- Possíveis implementações: OpenCV via microsserviço Python, biblioteca PHP, serviço externo

---

## Comunicação em Tempo Real

A tela de acompanhamento de correção (`acompanhar-correcao.html`) exibe dados em tempo real.

**Opções:**
1. **Polling** (MVP): cliente faz GET a cada N segundos para `/provas/{id}/cartoes/status`
2. **WebSocket** (evolutivo): Laravel Broadcasting com Pusher ou Laravel WebSockets

**Para o MVP:** polling com intervalo de 5 segundos.

---

## Design System

O WEB implementa o Padrão Digital de Governo (gov.br Design System).

**Tokens de design (extraídos de `style-system/css/gov.css`):**
```css
--bg:           #f8f8f8  (canvas)
--surface:      #ffffff  (cards)
--fg:           #1c2733  (texto primário)
--muted:        #555555  (texto secundário)
--border:       #cccccc  (bordas)
--accent:       #1351b4  (azul institucional)
--accent-2:     #168821  (verde sucesso)
--warn:         #ffcd07  (amarelo atenção)
--danger:       #e52207  (vermelho erro)
--accent-dark:  #0c326f  (azul hover/active)
--accent-light: #c5d4eb  (azul fundo sutil)
```

**Fontes:**
- Principal: Rawline / Raleway (fallback)
- Mono: Roboto Mono

---

## Decisões Arquiteturais

### DA-001 — Separação Total entre API e WEB
**Decisão:** A API é exclusivamente JSON. O WEB é exclusivamente HTML/views.
**Motivo:** Permite que Android/iOS, WEB e integrações externas consumam a mesma API.
**Data:** 2026-06-16

### DA-002 — MariaDB como único banco
**Decisão:** MariaDB para toda a persistência.
**Motivo:** Definido na especificação do projeto. Compatível com MySQL e amplamente suportado.
**Data:** 2026-06-16

### DA-003 — Polling no MVP para leitura em tempo real
**Decisão:** Polling a cada 5s para acompanhamento de correção.
**Motivo:** Simplicidade para o MVP. WebSocket pode ser adicionado posteriormente sem quebrar a API.
**Data:** 2026-06-16

### DA-004 — Motor OMR como driver abstrato
**Decisão:** Implementar interface OmrDriverInterface para o motor de leitura.
**Motivo:** Permite trocar a implementação sem alterar o restante do sistema. Necessário para suporte futuro a OMR externo.
**Data:** 2026-06-16

### DA-005 — Autenticação via Laravel Sanctum
**Decisão:** Laravel Sanctum para tokens de API.
**Motivo:** Nativo no Laravel, suporta SPA e mobile com o mesmo mecanismo.
**Data:** 2026-06-16

---

## Segurança da Arquitetura

1. **HTTPS obrigatório** em todos os ambientes (exceto dev local)
2. **Tokens não armazenados em localStorage** no WEB (usar httpOnly cookies)
3. **Validação de escopo na API** para cada recurso acessado
4. **Rate limiting** nos endpoints de autenticação
5. **CSRF protection** no WEB (nativo do Laravel)
6. **Prepared statements** via Eloquent ORM (proteção contra SQL Injection)
7. **Sanitização de inputs** antes de processamento
8. **Headers de segurança** (X-Frame-Options, CSP, etc.)

---

## Motor OMR Nativo (MP-021)

- `apps/api/omr/` contém scripts Python (OpenCV/ReportLab) executados pela API via `Illuminate\Support\Facades\Process`.
- O driver é escolhido por `OMR_DRIVER` (`nativo` | `simulado`) em `config/omr.php`.
- Detalhes, decisões e dependências: `docs/18-omr-nativo-e-cartoes.md`.

---

## Evolução Arquitetural Prevista

| Fase   | Mudança                                             | Impacto         |
|--------|-----------------------------------------------------|-----------------|
| Fase 2 | Adicionar entidades de Banco de Questões            | Novo módulo API |
| Fase 3 | Adicionar módulo Montador de Provas                 | Novo módulo API |
| Fase 4 | Adicionar ProvaVersao e gabaritos individualizados  | Refactor Cartão |
| Fase 5 | Adicionar suporte a questões discursivas            | Novo módulo API |
| Fase 6 | Adicionar driver OMR externo                        | Novo driver     |
| Fase 7 | WebSocket para tempo real                           | Laravel Echo    |
| Fase 8 | Suporte offline no Mobile                           | Sync API        |


### T1 — Clientes, espaços e vínculos (aprovado, 31/10/2026)

O [documento T1](40-clientes-espacos-e-vinculos.md), aprovado pelo responsável e incorporado no PR #73, define propriedade dos dados por espaço, vínculos e permissões contextuais, cópias independentes e migração gradual no MariaDB compartilhado. O [mapa de consultas](41-t1-mapa-de-consultas.md) registra os consumidores a adaptar. T2 foi autorizado e começa pelo inventário; a aprovação do modelo não representa isolamento já implementado nem autoriza deploy.

### L6 — Galeria de avatares (31/10/2026, aprovação do responsável)

A API persiste somente `alunos.avatar_id`, inteiro de 1 a 36 ou nulo para iniciais. Não aceita URLs nem upload de imagens de alunos. A web usa o catálogo estático versionado `images/avatares/estudantes-v1.png`, com mapeamento estável por linha/coluna, e componentes Blade compartilhados para exibição e seleção. O catálogo aprovado não deve ser reordenado: novos personagens devem preservar os IDs existentes. A migração é aditiva e não remove `foto_path` nem arquivos. Telas de turma e painéis recebem o mesmo identificador.

### Adaptador transitório de consultas T2-D4

Conforme T1 aprovado, os consumidores pedagógicos usam contexto explícito do ator e conferem a propriedade das relações antes de agregar resultados. `ConsultasPedagogicas` e `ContextoImportacao` não mantêm estado global de requisição; seleção institucional web segue URL/formulário. Detalhes e limites em [47-t2-consultas-e-integracoes.md](47-t2-consultas-e-integracoes.md). Adaptador desligado até E e aprovação operacional; V1/V2 permanecem responsáveis por vínculos/token contextual.


### Integridade T2-E e corte explícito

O [T2-E](48-t2-integridade-e-ensaio.md) mantém o DDL restritivo fora de `migrate`, pois o modo legado continua desligando o preenchimento de espaço nas escritas. `g360:auditar-espacos` é somente leitura; `g360:restringir-espacos` simula por padrão e aplica o plano fechado apenas na janela de corte. O comando não liga `espacos.ativo`. A futura ativação exige release aprovado com escritores antigos parados, preenchimento e conciliação concluídos, auditoria e ensaio de manutenção.

FKs compostas garantem o mesmo espaço entre pais e filhos; serviços continuam validando relações funcionais e autorização. Recibo B6 conserva a prova de entrada quando o QR corrige a prova dentro do mesmo espaço. Exclusão/substituição de cartão desvincula explicitamente recibos na transação, sem anular seu espaço. Matrícula/CPF de aluno e idempotência B6 passam a índices por espaço durante o corte; identidade de usuários continua global.


### Gestão contextual V1-C2b (01/11/2026)

A gestão distingue identidade global de vínculo alvo, conforme T1: suspensão, concessões e turmas alteram apenas o vínculo validado, com evento e versão. Projeções usam perfil local, e resultados individuais não liberam o conteúdo geral da turma. O convite de primeira senha ganha referência nullable ao vínculo para impedir desativação global; não substitui o futuro convite de conta existente em `vinculo_convites`. Contratos, validação e limites em [59-v1-gestao-e-resultados-contextuais.md](59-v1-gestao-e-resultados-contextuais.md). Sem ativação, deploy ou migration no servidor.


### V1-C3 — Conferência e corte sem fallback

A conferência operacional é somente leitura, com hashes separados de origem/destino e decisão de todas as contas; nunca reativa um vínculo por sincronização legada. `vinculo_convites` implementa o convite de novo papel sem redefinir identidade, com emissor contextual e aceite pelo titular. `vinculos.obrigatorio`, desligado por padrão, prepara o bloqueio do legado no núcleo/HTTP/CLI/arquivos/filas; V2 ainda deve fornecer contexto confiável e telas. Contratos, testes e limites em [60-v1-conciliacao-e-preparo-do-corte.md](60-v1-conciliacao-e-preparo-do-corte.md). Sem deploy ou migration no servidor.

### V2-A — tokens contextuais independentes

Contexto autenticado é reconstruído a partir de metadados internos do token Sanctum persistido, com vínculo e token de identidade de origem. Não se altera o token de outras abas. Token contextual não emite outro contexto. Origem encerrada/expirada e vínculo inválido bloqueiam uso. Permissões permanecem dinâmicas; abilities não concedem poderes do catálogo. Sem migration nem ativação nesta entrega. Integração web por aba permanece V2-B: [contrato](61-v2-contexto-autenticado.md).

### V2-B — contexto web por requisição

A identidade continua na sessão compartilhada; perfil/permissões e token contextual são resolvidos por handle imutável por aba. Cache cifrado vincula o handle à sessão e identidade. Não se altera `auth_token`/`auth_perfil` global ao trocar. Cada requisição revalida a API, inclusive downloads e gravações; falhas não repetem POSTs. Contexto é transportado explicitamente em links/forms/headers. Detalhes, prazo deslizante e limites do ensaio em [docs/61](61-v2-contexto-autenticado.md).

### V2-C — gestão e confirmação de convite

A gestão web transporta explicitamente vínculo-alvo; não envia alterações globais de identidade. A confirmação de convite usa o token de identidade e referência temporária cifrada vinculada à sessão. O fragmento do e-mail é consumido e removido do histórico; não se guarda o token do convite na sessão compartilhada. Aceite/recusa permanecem transacionais e revalidados na API. [Contrato e limites](62-v2-gestao-e-aceite-de-convites.md).

### T3-A — contrato de perfil por tipo

`PerfisEspaco` centraliza a compatibilidade de perfil/espaço usada por contexto, cadastro escolar e convites. Um perfil desconhecido não assume escola como destino padrão. `professor_autonomo` possui política independente no catálogo e exige titularidade do espaço pessoal; permanece sem contexto habilitado enquanto consumidores dependem de escola. Também não obtém permissões como perfil global legado, mesmo com cache preenchido. Não há DDL nem alteração dos poderes institucionais nesta etapa. [Contrato, dependências e sequência](63-t3-perfis-por-espaco.md).

### T3-B — dados pessoais sob gate

Tipo de raiz e espaço são ligados por FK composta; triggers recusam escola nula em cadastro escolar e dados excessivos de aluno pessoal. Contexto pessoal só é resolvido com `espacos.pessoal`, desligado por padrão. Mudança de turma fixa a turma histórica dos cartões em transação e preserva as notas. Consultas/relatórios distinguem turma atual da turma de aplicação. [Contrato e limites](64-t3-b-dados-e-formularios-pessoais.md).

### L4-A — contrato de pedidos dos titulares

EstadoPedidoTitular define transições sem acesso a banco ou efeitos sobre dados. Não concede autorização: fatos de identidade/autoridade vêm do serviço transacional futuro, com escopo e contexto revalidados. Contrato de persistência, permissões e validação em docs/103-l4-a-contrato-protocolos.md; registro/triagem ficam em L4-B.

### L4-C1 — ciclo do artefato de exportação

EstadoExportacaoTitular é um contrato puro: transições e disponibilidade temporal não concedem autorização. A geração e a entrega futuras exigirão concessão delimitada, sujeito validado, escopo e destinatário revalidados. Estado terminal não reabre nem apaga dados. Planejamento e dependências em [107-l4-c1-contrato-exportacao.md](107-l4-c1-contrato-exportacao.md). Sem schema, endpoint ou deploy nesta parte.

### L4-C2 — vinculação explícita do sujeito

A primeira parte de C2 vincula um aluno ao protocolo no espaço exato, sob atribuição interna vigente. Cada revisão cria versão e histórico cifrado, renova o ciclo das conferências e não altera o cadastro escolar. Isso não concede leitura escolar nem autorização de exportação. A política dos aprovadores permanece dependente da escolha do responsável pelo produto. [Planejamento e limites](108-l4-c2-sujeito-autorizacao.md).

### L4-C2 — concessão delimitada

A concessão é separada da atribuição de atendimento e exige a permissão específica do aprovador institucional ou proprietário, conforme aprovação do produto. Guarda sujeito/ciclo, contexto, destinatário, campos mínimos e prazo; revogação e eventos são transacionais. Consumidores futuros devem revalidar dentro de comAutorizacao, mantendo locks e sem tratar recibo antigo como poder de acesso. A flag própria inicia desligada. Detalhes em [109-l4-c2-concessao-exportacao.md](109-l4-c2-concessao-exportacao.md).

### L4-C3 — artefato mínimo cifrado e transacional

GeracaoExportacaoTitular gera cadastro_minimo_v1 sob os locks/revalidação de comAutorizacao. Documento JSON de até16KiB, somente nome/matrícula selecionados, manifesto versionado e hash dos dados dentro da cifra autenticada. Armazenamento privado em pedido_exportacao_artefatos, sem arquivo público: ciphertext, integridade, prazo, concessão e chave idempotente; eventos obrigatórios na mesma transação. Um artefato por concessão, chave única por acesso/versão. Falha ou expiração durante geração reverte tudo. Essa execução síncrona evita estado intermediário persistido e efeitos externos; formatos maiores exigem nova decisão de armazenamento/fila. Flag de geração desligada por padrão. Endpoint retorna só recibo mínimo; consulta/download seguros ficam para C4. Ver doc110.

### L4-C4 — consulta e entrega protegidas

EntregaExportacaoTitular resolve somente referência mínima por acesso/versão antes de comAutorizacao; conteúdo é lido sob locks e revalidado contra concessão/versão/destinatário atuais. Consulta e download verificam estado, validade, SHA-256 do ciphertext, MAC e manifesto fechado canônico. Eventos síncronos obrigatórios e nova conferência de validade precedem o retorno da transação; controller monta o anexo JSON depois do serviço, sem streaming tardio. Consulta só retorna metadados. Download_liberado representa autorização de entrega, não confirmação de recebimento. Proteção private/no-store existente da API, nome opaco e nosniff. Flag própria desligada, sem nova migration. Ver doc111; UI/ensaio C5 pendentes.
