# 61 — V2: contexto autenticado e seleção

Origem: T1 (docs/40), V2 do plano mestre e autorização de 01/11/2026 após o PR #92. Sem deploy.

## Sequência

- V2-A: API lista vínculos válidos da própria identidade e emite token independente para um vínculo. Cada requisição reconstrói o contexto pelo token persistido, nunca pelo ID enviado em header/query. Incorporado no PR #93.
- V2-B (incorporado no PR #94): web associa identificador opaco por aba a token guardado no servidor; seleção automática se único vínculo, escolha se múltiplos, estado sem acesso e troca no topo. Cobrir navegação, forms, AJAX, downloads, expiração por inatividade e duas abas.
- V2-C: gestão e aceite implementados para revisão; contrato e validações em [docs/62](62-v2-gestao-e-aceite-de-convites.md). Ensaio da release em ambiente equivalente permanece necessário antes do corte.

## Contrato V2-A

`GET /api/v1/auth/vinculos` lista somente vínculos válidos da conta autenticada. `POST /api/v1/auth/vinculos/selecionar`, com `vinculo_id`, emite um novo token contextual; não altera o token de identidade nem outros contextos. Ambos exigem token persistido de identidade e respeitam troca obrigatória de senha. O modo de seleção fica disponível somente com `vinculos.obrigatorio` habilitado em testes/ensaio; padrão permanece desligado.

O token usa nome reservado `vinculo-contexto` e abilities internas `vinculo:ID` e `identidade:ID_TOKEN_ORIGEM`, gravadas exclusivamente pelo servidor. Não são permissões de negócio: os poderes são calculados pelo catálogo/vínculo a cada uso. Nenhuma migration é necessária. O middleware rejeita metadados incompletos, pai ausente/expirado, conta divergente e vínculos revogados, vencidos ou de instituição suspensa. O contexto também é validado quando o regime obrigatório está desligado, sem retornar ao perfil legado.

A saída com token contextual encerra apenas aquele token; a saída com token de identidade invalida os contextos derivados pela ausência do pai. Redefinição de senha já elimina todos os tokens. Não há validade máxima nova de oito horas. A sessão web mantém os 120 minutos de inatividade na futura integração V2-B. A seleção não reutiliza automaticamente o último vínculo da conta.

O V2-A não entrega ainda isolamento de abas na interface. Não habilitar o corte nem publicar isoladamente: V2-B/C e ensaio continuam necessários.

## Entrega V2-B

Autorização em 01/11/2026: merge do PR #93 e seguir com V2-B, sem deploy. Base `479fb02`. A tela nova deriva da aprovação explícita do V2 no plano mestre e do T1; usa o layout e tokens existentes, com registro no inventário.

- O login conserva o token de identidade no servidor e regenera a sessão. O sinal `selecionar_vinculo` da API ativa o fluxo novo; sessões legadas continuam no fluxo existente enquanto o corte estiver desligado.
- `/vinculos` lista somente opções autorizadas pela API. Um único vínculo envia automaticamente o formulário protegido por CSRF; múltiplos exigem escolha. A troca explícita mostra a escolha mesmo com um só. Sem vínculo, oferece atualização e saída, sem painel vazio ou acesso legado.
- Cada seleção cria um identificador aleatório imutável, associado ao token contextual em cache cifrado e vinculado à sessão e ao token de identidade. A seleção nunca substitui um contexto anterior. A sessão compartilhada não recebe perfil/permissões da aba; esses dados são próprios da requisição.
- A aba guarda somente o identificador em `sessionStorage`. Links e GETs/downloads transportam `_ctx`; formulários recebem campo oculto; `fetch` usa `X-G360-Contexto`. Esses valores não são tokens API e só funcionam na sessão de origem. Links externos não recebem contexto. Não se usa `localStorage` para autorização. Links abertos em nova aba levam o contexto explicitamente; abas sem contexto escolhem um vínculo, sem recuperar o último vínculo global.
- GET sem identificador mostra apenas uma página de retomada, sem dados operacionais. Requisições de gravação sem contexto válido são interrompidas e nunca repetidas automaticamente. AJAX recebe 409 e indicação para escolher novamente. O histórico restaurado pelo navegador é revalidado ao retornar.
- Cada requisição contextual consulta `/auth/me`; menu e perfil usam as permissões atuais. Revogação/401/403 remove o contexto local e pede seleção. Falha transitória da API retorna 503, preservando o contexto e impedindo a operação. O topo identifica instituição e papel e permite trocar.
- O cache tem prazo deslizante igual à sessão web (120 minutos por padrão), renovado por requisições autenticadas válidas. Como na sessão existente, requisições periódicas contam como atividade; não foi introduzido temporizador de eventos de teclado/mouse. A API continua sem limite máximo de oito horas. A saída encerra a identidade, invalida os filhos na API e invalida a sessão web.

Validação: testes HTTP de duas abas/contextos, vínculo ausente/alheio/expirado, revogação sem reenvio, indisponibilidade, formulário, AJAX, download, menu, seleção única/múltipla/vazia e logout. Ensaio de transporte em Chromium com páginas fictícias e duas abas em `scripts/auditoria/contexto-abas.mjs`; executar com `CHROME_PATH` e dependências de `scripts/auditoria`. Não substitui o ensaio integrado com API e dados da futura release.

V2-C (gestão/aceite dos convites e integração) está em revisão; o ensaio operacional permanece pendente. Sem deploy, migration ou configuração de ativação no servidor.

V2-C implementado após o merge autorizado do PR #94: [gestão e aceite de convites](62-v2-gestao-e-aceite-de-convites.md). Sem publicação.
