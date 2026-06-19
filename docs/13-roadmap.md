# 13 — Roadmap

---

## Micro-Passos (MPs)

Cada MP é uma unidade atômica de implementação com critérios de aceite claros.
Nenhum MP pode ser iniciado sem que suas dependências estejam concluídas.

---

## MP-001 — Estrutura do Repositório

**Objetivo:** Criar a estrutura base do monorepo com diretórios oficiais.

**Dependências:** Nenhuma

**Entregáveis:**
- apps/api/ (Laravel instalado)
- apps/web/ (Laravel instalado)
- apps/android/ (React Native configurado)
- apps/ios/ (React Native configurado)
- docs/ (documentação inicial)
- .gitignore configurado
- README.md

**Critérios de Aceite:**
- [ ] Cada app executa seu comando de start sem erros
- [ ] Estrutura de diretórios confirmada
- [ ] Repositório limpo (sem node_modules, vendor, .env no git)

**Commit esperado:** `chore: inicializa estrutura do monorepo com apps API, WEB, Android e iOS`

---

## MP-002 — Documentação Consolidada ✅ (Este MP)

**Objetivo:** Criar toda a documentação oficial do produto antes de qualquer código.

**Dependências:** Nenhuma

**Entregáveis:**
- CLAUDE.md
- docs/00 a docs/16

**Critérios de Aceite:**
- [x] Todos os mockups inventariados
- [x] Perfis e permissões documentados
- [x] Modelo de dados conceitual criado
- [x] Rotas API e WEB definidas
- [x] Roadmap criado
- [x] GAP Analysis criada

**Commit esperado:** `docs: cria documentação oficial completa do produto`

---

## MP-003 — Modelagem Conceitual e Banco de Dados

**Objetivo:** Criar o banco de dados MariaDB com todas as entidades do MVP.

**Dependências:** MP-001, MP-002

**Entregáveis:**
- Migrations para todas as entidades (ver docs/09-modelo-de-dados.md)
- Seeders para dados de desenvolvimento (rede, núcleo, escolas, turmas, alunos, usuários por perfil)
- Diagrama ER gerado

**Migrations (em ordem):**
1. create_redes_table
2. create_nucleos_table
3. create_escolas_table
4. create_turmas_table
5. create_alunos_table
6. create_usuarios_table
7. create_usuario_escopos_table
8. create_provas_table
9. create_prova_turmas_table
10. create_gabaritos_table
11. create_gabarito_questoes_table
12. create_cartoes_table
13. create_cartao_respostas_table
14. create_notas_table
15. create_ambiguidade_logs_table
16. create_visitas_table
17. create_sincronizacoes_seges_table

**Critérios de Aceite:**
- [ ] `php artisan migrate` executa sem erros
- [ ] `php artisan db:seed` popula dados de dev
- [ ] Todas as FKs com índices corretos
- [ ] Dados de seed incluem ao menos 1 registro de cada perfil

**Commit esperado:** `db: cria migrations e seeders completos do MVP`

---

## MP-004 — Arquitetura da API (Base)

**Objetivo:** Configurar a API Laravel com autenticação, estrutura de rotas e padrão de resposta.

**Dependências:** MP-003

**Entregáveis:**
- Laravel Sanctum configurado
- Estrutura de rotas em routes/api.php
- AuthController (login, logout, me)
- Middleware de autenticação e autorização
- Padrão de resposta JSON (success/error)
- Policies base para cada perfil

**Critérios de Aceite:**
- [ ] POST /api/v1/auth/login retorna token e dados do usuário
- [ ] GET /api/v1/auth/me retorna dados do usuário autenticado
- [ ] POST /api/v1/auth/logout invalida o token
- [ ] Rotas protegidas retornam 401 sem token
- [ ] Rotas fora do escopo retornam 403

**Commit esperado:** `feat(api): implementa autenticação base com Sanctum e RBAC`

---

## MP-005 — Arquitetura do WEB (Base)

**Objetivo:** Configurar o WEB Laravel com layout base, autenticação e estrutura de views.

**Dependências:** MP-004

**Entregáveis:**
- Layout base Blade (com GovBar, Header, Nav, Breadcrumb)
- Implementação dos tokens CSS do gov.br (gov.css integrado)
- Middleware de autenticação WEB
- Rotas base em routes/web.php
- Login/Logout funcional consumindo a API
- Redirecionamento para dashboard conforme perfil

**Critérios de Aceite:**
- [ ] Tela de login idêntica ao mockup login.html
- [ ] Login com credenciais válidas redireciona para dashboard do perfil
- [ ] Login com credenciais inválidas exibe mensagem de erro
- [ ] Logout funciona e redireciona para /login
- [ ] Layout base renderiza GovBar, Header e Breadcrumb corretamente
- [ ] Toggle de tema claro/escuro funciona

**Commit esperado:** `feat(web): implementa layout base e autenticação consumindo API`

---

## MP-006 — Dashboard Admin

**Objetivo:** Implementar o dashboard do Administrador da Rede.

**Dependências:** MP-004, MP-005

**Entregáveis:**
- GET /api/v1/dashboard/admin (endpoint)
- View dashboard-admin fiel ao mockup dashboard-admin.html
- KPIs: Escolas, Alunos, Provas do mês, Média da rede
- Gráfico: Top 5 escolas por desempenho
- Painel de alertas: cartões pendentes, escolas abaixo da meta, SEGES
- Tabela: últimos acessos de usuários
- Ações rápidas: 4 cards linkáveis

**Critérios de Aceite:**
- [ ] Dashboard visualmente idêntico ao mockup
- [ ] KPIs exibem dados reais do banco
- [ ] Alertas aparecem apenas quando há dados críticos
- [ ] Acesso negado para qualquer outro perfil
- [ ] Responsivo nos breakpoints definidos

**Commit esperado:** `feat(web): implementa dashboard do administrador da rede`

---

## MP-007 — Dashboard Diretor de Núcleo

**Objetivo:** Implementar o dashboard do Diretor de Núcleo.

**Dependências:** MP-006

**Entregáveis:**
- GET /api/v1/dashboard/diretor-nucleo
- View dashboard-diretor-nucleo fiel ao mockup
- KPIs, tabela comparativa de escolas, gráfico bimestral, lista de visitas

**Critérios de Aceite:**
- [ ] Dados limitados ao escopo do núcleo do usuário
- [ ] Visitas programadas exibidas com urgência correta
- [ ] Acesso negado para outros perfis

**Commit esperado:** `feat(web): implementa dashboard do diretor de núcleo`

---

## MP-008 — Dashboard Diretor Escolar

**Objetivo:** Implementar o dashboard do Diretor Escolar.

**Dependências:** MP-006

**Entregáveis:**
- GET /api/v1/dashboard/diretor-escolar
- View dashboard-diretor-escolar fiel ao mockup
- Banner escola, KPIs, tabela de turmas, equipe, calendário

**Commit esperado:** `feat(web): implementa dashboard do diretor escolar`

---

## MP-009 — Dashboard Coordenador

**Objetivo:** Implementar o dashboard do Coordenador.

**Dependências:** MP-006

**Entregáveis:**
- GET /api/v1/dashboard/coordenador
- View dashboard-coordenador fiel ao mockup

**Commit esperado:** `feat(web): implementa dashboard do coordenador`

---

## MP-010 — Dashboard Professor

**Objetivo:** Implementar o dashboard do Professor.

**Dependências:** MP-006

**Entregáveis:**
- GET /api/v1/dashboard/professor
- View dashboard-professor fiel ao mockup

**Commit esperado:** `feat(web): implementa dashboard do professor`

---

## MP-011 — Dashboard Aluno

**Objetivo:** Implementar o dashboard do Aluno.

**Dependências:** MP-006

**Entregáveis:**
- GET /api/v1/dashboard/aluno
- View dashboard-aluno fiel ao mockup

**Commit esperado:** `feat(web): implementa dashboard do aluno`

---

## MP-012 — Autenticação Completa

**Objetivo:** Completar o fluxo de autenticação com redefinição de senha e cadastro.

**Dependências:** MP-005

**Entregáveis:**
- Fluxo "Esqueci a senha" com e-mail de recuperação
- Tela de redefinição de senha
- Cadastro de usuário com aceite de LGPD

**Commit esperado:** `feat(api,web): implementa fluxo completo de autenticação`

---

## MP-013 — Perfis e Permissões

**Objetivo:** Implementar gerenciamento de membros da equipe.

**Dependências:** MP-005

**Entregáveis:**
- CRUD de usuários via API
- Telas: perfis-equipe, membro-cadastrar, membro-editar
- Ativação/desativação de membros

**Commit esperado:** `feat(api,web): implementa gestão de membros da equipe`

---

## MP-014 — Núcleos

**Objetivo:** Implementar CRUD de núcleos regionais.

**Dependências:** MP-004

**Entregáveis:**
- CRUD de núcleos via API
- (Tela WEB a definir — não há mockup específico)

**Commit esperado:** `feat(api): implementa CRUD de núcleos`

---

## MP-015 — Escolas

**Objetivo:** Implementar gestão completa de escolas.

**Dependências:** MP-014

**Entregáveis:**
- CRUD de escolas via API
- Telas: escolas.html, escola-detalhe.html
- Modal de criação/edição
- Ativar/desativar escola
- KPIs da escola
- Busca em tempo real

**Critérios de Aceite:**
- [ ] Grid de escolas idêntico ao mockup escolas.html
- [ ] Modal de criação/edição funcional
- [ ] Busca filtra em tempo real
- [ ] Escola inativa com visual diferenciado
- [ ] KPIs atualizados dinamicamente

**Commit esperado:** `feat(api,web): implementa gestão completa de escolas`

---

## MP-016 — Turmas

**Objetivo:** Implementar gestão de turmas.

**Dependências:** MP-015

**Entregáveis:**
- CRUD de turmas via API
- Telas: turmas.html, turma-detalhe-2.html
- Importação por planilha

**Commit esperado:** `feat(api,web): implementa gestão de turmas com importação por planilha`

---

## MP-017 — Alunos

**Objetivo:** Implementar gestão de alunos.

**Dependências:** MP-016

**Entregáveis:**
- CRUD de alunos via API
- Telas: aluno-cadastrar.html, aluno-detalhe.html, aluno-editar.html
- Histórico de avaliações do aluno
- Gráfico de evolução
- Exportar ficha PDF

**Commit esperado:** `feat(api,web): implementa gestão completa de alunos`

---

## MP-018 — Professores

**Objetivo:** Implementar cadastro e gestão de professores como membros da equipe.

**Dependências:** MP-013, MP-016

**Entregáveis:**
- Vínculo professor ↔ turmas
- Escopo de acesso do professor

**Commit esperado:** `feat(api,web): implementa vínculo de professores com turmas`

---

## MP-019 — Avaliações (Provas)

**Objetivo:** Implementar CRUD de provas e o stepper de criação.

**Dependências:** MP-016

**Entregáveis:**
- CRUD de provas via API
- Telas: provas.html, criar-prova.html
- Stepper de criação (3 etapas)
- Filtros: busca, disciplina, status
- Status: Rascunho, Publicada, Em correção, Corrigida
- Ação contextual por status

**Commit esperado:** `feat(api,web): implementa gestão de avaliações com stepper de criação`

---

## MP-020 — Gabaritos

**Objetivo:** Implementar o editor de gabarito e a visualização do gabarito publicado.

**Dependências:** MP-019

**Entregáveis:**
- Editor de bolhas interativo (criar-prova.html passo 2)
- Publicação do gabarito
- Tela de visualização: gabarito.html
- Exportação do gabarito em PDF
- Padrões configuráveis por prova

**Critérios de Aceite:**
- [ ] Editor de bolhas funcional e idêntico ao mockup
- [ ] Contador X/N atualiza em tempo real
- [ ] Publicação desabilitada com gabarito incompleto
- [ ] Gabarito publicado não pode ser editado
- [ ] Exportação PDF via window.print()

**Commit esperado:** `feat(api,web): implementa editor de gabarito e publicação`

---

## MP-021 — OMR (Leitura de Cartões)

**Objetivo:** Implementar o motor OMR e o fluxo de leitura de cartões.

**Dependências:** MP-020

**Entregáveis:**
- Interface OmrDriverInterface
- Implementação inicial do motor OMR
- Upload de imagem via API
- Job de processamento assíncrono
- Resolução manual de ambíguos
- Tela: acompanhar-correcao.html
- Polling a cada 5s para atualização

**Critérios de Aceite:**
- [ ] Upload de imagem aceito pela API
- [ ] OMR processa e retorna respostas com confiança
- [ ] Cartões ambíguos listados com opções de resolução
- [ ] Resolução manual atualiza contadores em tempo real
- [ ] Estado "Leitura concluída" funcional

**Commit esperado:** `feat(api,web): implementa motor OMR e fluxo de acompanhamento de correção`

---

## MP-022 — Relatórios

**Objetivo:** Implementar todos os relatórios do MVP.

**Dependências:** MP-021

**Entregáveis:**
- Resultado individual: resultado.html
- Relatório da prova: relatorio-prova.html
- Relatório turma × prova: relatorio-turma-prova.html
- Exportação PDF dos relatórios
- Revisão de leitura pós-correção

**Critérios de Aceite:**
- [ ] Resultado individual idêntico ao mockup
- [ ] Folha de respostas corrigida com cores corretas (verde/vermelho/amarelo)
- [ ] Badge Aprovado/Recuperação conforme nota
- [ ] Comparativo com média da turma exibido
- [ ] Relatório da prova com KPIs e tabela por aluno
- [ ] Breadcrumb dinâmico funcional

**Commit esperado:** `feat(api,web): implementa relatórios de resultado e prova`

---

## MP-023 — Aplicativo Android

**Objetivo:** Implementar o app Android em React Native.

**Dependências:** MP-004 (API base), MP-021 (OMR)

**Entregáveis:**
- Autenticação no app
- Dashboard do perfil correspondente
- Captura de cartões pela câmera
- Envio para OMR
- Visualização de resultados

**Commit esperado:** `feat(android): implementa app base com autenticação e captura OMR`

---

## Nota de Sequenciamento (pós MP-022, revisada 2026-06-18)

A partir de 2026-06, foi decidido priorizar o fechamento de funcionalidades WEB/API
antes de iniciar o MP-023 (Android). Os MPs 024 em diante abaixo foram planejados
nessa janela e devem ser executados **antes** do MP-023, apesar da numeração mais
alta — nenhum deles altera contrato existente de API de forma incompatível, então não
há retrabalho para o MP-023 quando ele for iniciado.

**Revisão de 2026-06-18:** o plano original (MP-025 a MP-030, focado em fechar gaps de
mockup e no motor de permissões) foi **reordenado e expandido** para acomodar a decisão
estratégica de transformar o Gabarito360 em SaaS multi-tenant com cadastro autônomo
(individual e institucional) e cobrança recorrente. Ver `docs/03-perfis-e-permissoes.md`
(novos perfis SECRETARIO_EDUCACAO e APLICADOR, novo nível Secretaria), `docs/06-regras-de-negocio.md`
(RN-001 revisada, RN-002.4, RN-015 e RN-016 novas) e `docs/09-modelo-de-dados.md`
(secretarias, redes.modalidade, planos, assinaturas, pagamentos). Os MPs de fechamento
de gaps de mockup (visitas, correção por turma, relatórios) continuam válidos, apenas
foram empurrados para depois da fundação SaaS, pois esta agora é pré-requisito lógico
(ex.: o motor de permissões configuráveis, antes pensado só para `perfis-equipe.html`,
agora é exigido também pelo professor autônomo e pelo aplicador).

**Referência de mercado:** este desenho foi inspirado em dois padrões já validados no
mercado — o modelo de assinatura individual-ou-organização do ZipGrade (concorrente
direto, líder em correção de cartão-resposta por app) e o modelo de "Organizations"
usado por SaaS B2B self-service como Slack/Notion (cadastro autônomo que cria um
espaço próprio, com caminho de entrada em uma organização maior depois).

---

## MP-024 — Meu Perfil e Configurações da Rede ✅

**Objetivo:** Implementar a tela de perfil do usuário logado e a tela de configurações
de parâmetros da rede (RN-013.2).

**Dependências:** MP-005, MP-006

**Entregáveis:**
- Tela: perfil.html (dados pessoais editáveis, alteração de senha com verificação da senha atual)
- Tela: configuracoes.html (escopo reduzido: meta_media, meta_minima, limiar_seges_min — campos
  documentados em RN-013.2; demais seções do mockup — tema, idioma, integrações, LGPD — não têm
  modelo de dados e foram propositalmente omitidas)

**Critérios de Aceite:**
- [x] Troca de senha exige senha atual correta
- [x] Configurações restritas a ADMIN_REDE
- [x] RN-009.5 (nota mínima) passa a ler `redes.meta_minima` dinamicamente

**Commit:** `feat(api,web): implementa Meu Perfil e Configurações da rede` (076e759)

**Status:** Concluído (registro retroativo — implementado antes deste documento ser atualizado).

---

## MP-025 — Fundação Multi-Tenant (Secretaria + Modalidade de Rede) ✅

**Objetivo:** Preparar o schema para suportar o nível "Secretaria" acima da rede e a
distinção entre rede institucional e rede individual, sem alterar nenhum comportamento
visível ainda — é a base sobre a qual os MPs seguintes (027 a 029) constroem.

**Origem documentada:** RN-002.4, RN-002.1 (revisadas), `docs/09-modelo-de-dados.md`
(tabela `secretarias`, campos `redes.secretaria_id`/`redes.modalidade`/`redes.usuario_titular_id`).

**Dependências:** MP-003 (Modelagem e Banco de Dados)

**Entregáveis:**
- Migration: cria tabela `secretarias`
- Migration: adiciona `secretaria_id` (nullable), `modalidade` (enum institucional/individual,
  default institucional), `usuario_titular_id` (nullable) em `redes`
- Migration: adiciona `secretaria` ao enum `escopo_tipo` de `usuario_escopos`
- Nenhuma tela nova nesta MP — é só fundação de dados

**Critérios de Aceite:**
- [x] Todas as redes existentes continuam com `modalidade=institucional` após a migration
- [x] Nenhum endpoint existente quebra (campos novos são opcionais)

**Commit:** `feat(db): adiciona fundação multi-tenant (secretaria e modalidade de rede)`

**Status:** Concluído (2026-06-18). Testado: rede existente preservou `modalidade=institucional`;
login e `/v1/escolas`/`/v1/turmas` continuam funcionando sem novas entradas de erro no log.

---

## MP-026 — Novos Perfis: Secretário de Educação e Aplicador ✅

**Objetivo:** Introduzir os 2 novos perfis do sistema (SECRETARIO_EDUCACAO, APLICADOR),
seus painéis próprios e as regras de escopo correspondentes.

**Origem documentada:** `docs/03-perfis-e-permissoes.md` (perfis e matriz de permissões
já reescritos para 8 perfis). **Sem mockup HTML** para os dois novos painéis — a criar
seguindo o padrão visual dos dashboards existentes (`dashboard-diretor-nucleo.html` como
referência de estrutura para o painel da secretaria; `dashboard-professor.html` como
referência de estrutura simplificada para o painel do aplicador).

**Dependências:** MP-025

**Entregáveis:**
- Migration: adiciona `secretario_educacao` e `aplicador` ao enum `perfil` de `usuarios`
- Painel da Secretaria (novo): lista de redes vinculadas com KPIs agregados por rede
- Painel do Aplicador (novo): lista de provas agendadas para o dia na escola, com link
  direto para "Enviar Cartões" de cada uma — sem acesso a notas/relatórios/cadastros
- Middleware/regra de autorização restringindo o aplicador exatamente ao descrito na
  matriz de `docs/03` (capturar/enviar cartões; nada além disso)

**Critérios de Aceite:**
- [x] Aplicador não consegue acessar nenhuma rota de notas, relatórios ou cadastro (testar
  diretamente via API, não só esconder no menu)
- [x] Secretário vê apenas redes vinculadas à sua secretaria
- [x] Login de cada perfil novo direciona para o painel correto

**Commit:** `feat(api,web): implementa perfis secretario_educacao e aplicador`

**Status:** Concluído (2026-06-18). Testado via API e via WEB (login + render do painel)
para os dois perfis: aplicador recebeu 403 em dashboard/admin, criação de prova e listagem
de usuários; relatórios e resultados retornam "não encontrado" por escopo vazio (default
deny, sem alterar `ResultadoController`/`RelatorioController`); envio de cartão alcança o
controller normalmente (bloqueio era só de perfil, não de rota). Secretário viu apenas a
rede vinculada à sua secretaria, com KPIs agregados corretos. Dados de teste criados via
tinker foram removidos após a validação.

---

## MP-027 — Domínio de Assinatura e Cobrança (Mercado Pago)

**Objetivo:** Criar a infraestrutura de planos, assinaturas e pagamentos, integrada ao
Mercado Pago, e a tela de "Assinatura e Cobrança" para o titular da conta.

**Origem documentada:** RN-015 (nova), tabelas `planos`/`assinaturas`/`pagamentos` em
`docs/09-modelo-de-dados.md`. **Sem mockup HTML** — telas novas (página de planos,
checkout, "minha assinatura") a desenhar seguindo o Design System gov.br já em uso.

**Dependências:** MP-025

**Entregáveis:**
- Migrations: `planos`, `assinaturas`, `pagamentos`
- Seed dos 5 planos definidos em RN-015.1 (Professor Mensal R$9,99; Professor Anual
  R$99,90; Instituição até 10 licenças R$7,50/licença/mês; Instituição +10 licenças
  e Secretaria/Núcleos como planos `modo=comercial`, sem preço de tabela)
- Integração com Mercado Pago: criação de cobrança recorrente, webhook de confirmação
  (apenas para planos `modo=autoatendimento`)
- Tela pública: página de planos/preços (pré-login), exibindo os 3 planos de
  autoatendimento com preço e os 2 planos comerciais como "Fale Conosco"
- Tela: checkout (escolher plano, informar nº de licenças se institucional, pagamento)
- Tela: formulário "Fale Conosco" (planos `modo=comercial`) — captura lead, não cria
  assinatura nem dá acesso ao sistema; segue para contato comercial manual
- Tela: "Minha Assinatura" (status, histórico de pagamentos, cancelar, trocar forma de
  pagamento) — visível apenas ao titular (RN-015.2)
- Job/rotina de verificação de inadimplência (RN-015.4) suspendendo acesso após o prazo

**Critérios de Aceite:**
- [ ] Webhook do Mercado Pago atualiza o status da assinatura corretamente (testar com
  ambiente sandbox do Mercado Pago)
- [ ] Plano institucional até 10 licenças cobra corretamente `7.50 × nº de licenças`
- [ ] Tentar cadastrar instituição com mais de 10 licenças, ou perfil Secretaria, sempre
  cai no formulário "Fale Conosco" — nunca no checkout do Mercado Pago
- [ ] Nenhum dado de cartão é armazenado pela API (apenas referências do gateway)
- [ ] Assinatura em trial expira corretamente e bloqueia acesso (exceto à própria tela
  de cobrança) se não houver pagamento

**Commit esperado:** `feat(api,web): implementa assinatura e cobrança via Mercado Pago`

---

## MP-028 — Cadastro Autônomo (Individual e Institucional)

**Objetivo:** Implementar os dois fluxos de cadastro autônomo descritos em RN-016.1 e
RN-016.2, substituindo o fluxo único de "aguardando aprovação manual" (RN-001.4 antiga).

**Origem documentada:** RN-001.1/001.4 (revisadas), RN-016.1, RN-016.2.

**Dependências:** MP-026 (perfis), MP-027 (assinatura — o cadastro autônomo termina
escolhendo um plano)

**Entregáveis:**
- Tela de cadastro expandida: escolha entre "Conta Individual" (professor) e "Conta
  Institucional" (secretaria/rede)
- Fluxo individual: ao confirmar pagamento/trial, cria automaticamente 1 rede
  (`modalidade=individual`) + 1 núcleo padrão + 1 escola, com o professor como titular
- Fluxo institucional: ao confirmar e-mail + pagamento/trial, cria a secretaria (se
  aplicável) ou a rede institucional com o usuário como titular
- Convite por e-mail para os demais perfis dentro de uma instituição/rede individual já
  existente, com acesso liberado imediatamente ao aceitar (RN-001.4)

**Critérios de Aceite:**
- [ ] Professor autônomo, ao logar pela primeira vez, já vê sua própria escola/turma
  prontas para uso, sem nenhuma etapa manual de aprovação
- [ ] UI da rede individual não exibe os níveis "rede"/"núcleo" (RN-002.4)
- [ ] Convite aceito dá acesso imediato, sem estado de "aguardando aprovação"

**Commit esperado:** `feat(api,web): implementa cadastro autônomo individual e institucional`

---

## MP-029 — Migração/Absorção de Rede Individual para Institucional

**Objetivo:** Implementar o fluxo de absorção de uma rede individual de professor para
dentro de uma rede institucional, quando uma instituição decide assinar depois.

**Origem documentada:** RN-016.4.

**Dependências:** MP-028

**Entregáveis:**
- Fluxo de convite/código gerado por um ADMIN_REDE ou DIR_NUCLEO institucional
- Tela de confirmação para o titular da rede individual aceitar a migração
- Rotina de migração: move escola/turmas/alunos/provas/histórico da rede individual
  para dentro de um núcleo da rede institucional; cancela a assinatura individual

**Critérios de Aceite:**
- [ ] Após a migração, todo o histórico de provas/notas do professor permanece intacto
  e acessível dentro da nova rede institucional
- [ ] A assinatura individual é cancelada automaticamente (sem cobrança duplicada)
- [ ] Processo é irreversível por autoatendimento (exige suporte manual para desfazer)

**Commit esperado:** `feat(api,web): implementa migração de rede individual para institucional`

---

## MP-030 — Motor de Permissões Configuráveis (Fase 1)

**Objetivo:** Criar a infraestrutura de permissões configuráveis por perfil/escola e a
tela de gestão (`perfis-equipe.html`), **sem** remover ou substituir os middlewares
`perfil:...` já existentes — o motor novo roda em paralelo, com os valores padrão
replicando fielmente a matriz de `docs/03-perfis-e-permissoes.md`, para não mudar
nenhum comportamento atual. Este motor passa a ser usado também para conceder
permissões elevadas ao professor titular de uma rede individual (RN-016.3) e para
restringir o aplicador (MP-026) — não é mais só uma tela de configuração, é peça
estrutural do modelo SaaS.

**Origem documentada:** mockup `perfis-equipe.html` (UI completa de toggles por perfil).
**Decisão registrada em conversa (2026-06-18):** tratar como mudança arquitetural real,
não como tela cosmética — ver `docs/12-arquitetura.md` (a ser atualizado neste MP).

**Dependências:** MP-013 (Perfis e Permissões), MP-018, MP-026 (novos perfis), MP-028
(rede individual já precisa do motor para o professor titular)

**Entregáveis:**
- Migrations: `permissoes` (catálogo), `perfil_permissoes_padrao` (seed a partir de
  docs/03), `escola_perfil_permissoes` (overrides por escola)
- `PermissaoService::pode($usuario, $chave, $escolaId = null)` — resolve override de
  escola > padrão do perfil > nega por padrão
- Tela `perfis-equipe.html` implementada de verdade: cards por perfil dentro de uma
  escola, toggles de permissão, modal "ver membros"
- Atualização de `docs/03-perfis-e-permissoes.md` e `docs/09-modelo-de-dados.md`
  com o novo modelo
- Registro da decisão arquitetural em `docs/12-arquitetura.md`

**Critérios de Aceite:**
- [ ] Seed inicial reproduz exatamente o comportamento atual (nenhuma regressão)
- [ ] Toggle de permissão por escola persiste e é lido corretamente por `PermissaoService`
- [ ] Nenhum middleware `perfil:...` existente foi removido nesta fase
- [ ] Professor titular de rede individual recebe automaticamente as permissões
  elevadas via seed/regra específica (RN-016.3), sem configuração manual

**Commit esperado:** `feat(api,web): implementa motor de permissões configuráveis por perfil/escola`

---

## MP-031 — Migração dos Endpoints Existentes para o Motor de Permissões (Fase 2)

**Objetivo:** Substituir gradualmente os middlewares fixos `perfil:...` por
`permissao:chave`, usando o motor criado no MP-030.

**Dependências:** MP-030 (validado em uso real antes de iniciar esta fase)

**Entregáveis:**
- Migração endpoint por endpoint (não em lote), com testes manuais a cada lote migrado
- Remoção do middleware `perfil:...` somente após todos os pontos de uso migrados

**Critérios de Aceite:**
- [ ] Cada endpoint migrado mantém exatamente o mesmo comportamento de acesso de antes
- [ ] Suite de testes manuais (login de cada um dos 8 perfis + ações principais) refeita
  ao final da migração

**Commit esperado:** `refactor(api): migra autorização fixa para o motor de permissões configuráveis`

---

## MP-032 — Visitas Pedagógicas (CRUD)

**Objetivo:** Implementar o agendamento, edição e cancelamento de visitas pedagógicas
pelo Diretor de Núcleo.

**Origem documentada:** UC-009 (`docs/07-casos-de-uso.md`), tabela `visitas` já existe em
`docs/09-modelo-de-dados.md`, rotas já especificadas em `docs/10-rotas-api.md` e
`docs/11-rotas-web.md`. Ação "Agendar Visita" já aparece como card de ação rápida no
dashboard-diretor-nucleo.html.

**Dependências:** MP-007 (Dashboard Diretor de Núcleo)

**Entregáveis:**
- `VisitaController` na API (index/store/update/destroy, escopo por núcleo)
- `VisitaController` no WEB + modal de agendar/editar (AJAX, conforme já indicado em docs/11)
- Lista "Próximas agendas em campo" no dashboard-diretor-nucleo passa a usar dados reais

**Critérios de Aceite:**
- [ ] Visita aparece na lista do dashboard imediatamente após ser salva
- [ ] Apenas DIR_NUCLEO do núcleo correspondente pode editar/cancelar
- [ ] Urgência (Prioritária/Monitorar/Referência) refletida visualmente

**Commit esperado:** `feat(api,web): implementa CRUD de visitas pedagógicas`

---

## MP-033 — Acompanhamento de Correção por Turma

**Objetivo:** Implementar a visão consolidada de progresso de leitura OMR de todas as
provas de uma turma.

**Origem documentada:** mockup `acompanhar-correcao-turma.html`, inventário seção 12.

**Dependências:** MP-021 (OMR)

**Entregáveis:**
- Rota `/turmas/{id}/acompanhar` (WEB) consolidando o status de todos os cartões das
  provas vinculadas à turma
- Reaproveita o motor OMR e os componentes visuais já criados no MP-021
  (`acompanhar-correcao.html`), adaptados para múltiplas provas

**Critérios de Aceite:**
- [ ] Lista pendências de leitura agregadas por prova dentro da turma
- [ ] Permissão restrita a COORDENADOR e DIR_ESCOLAR (conforme inventário)

**Commit esperado:** `feat(api,web): implementa acompanhamento de correção por turma`

---

## MP-034 — Relatórios Consolidados (Escola / Núcleo / Rede) + Exportação PDF

**Objetivo:** Implementar as visões agregadas de desempenho por escola, núcleo e rede,
e a exportação em PDF dos relatórios e do resultado individual.

**Origem documentada:** rotas já especificadas em `docs/10-rotas-api.md` (linhas 153-156)
e `docs/11-rotas-web.md` (relatorio.escola). **Não há mockup HTML dedicado** para estas
3 telas — serão construídas seguindo o padrão visual já estabelecido em
`relatorio-prova.html`/`relatorio-turma-prova.html`, mesma abordagem usada em `provas/show`
(MP-019), que também não tinha mockup próprio.

**Dependências:** MP-022 (Relatórios)

**Entregáveis:**
- `/relatorios/escola/{id}`, `/relatorios/nucleo/{id}`, `/relatorios/rede` (API + WEB)
- `/relatorios/rede/pdf` e `/resultados/{aluno}/{prova}/pdf` (exportação)

**Critérios de Aceite:**
- [ ] Cada nível mostra KPIs agregados e comparativo entre as unidades do nível abaixo
- [ ] Escopo de acesso respeita a hierarquia (docs/03)
- [ ] PDF gerado é fiel ao conteúdo da tela (via window.print() ou equivalente, como já
  feito no MP-020)

**Commit esperado:** `feat(api,web): implementa relatórios consolidados e exportação PDF`

---

## MP-035 — Fechamento de Gaps Menores (Auditoria de Mockups)

**Objetivo:** Resolver pendências pontuais identificadas na auditoria mockup-vs-implementação
de 2026-06.

**Dependências:** MP-017 (Alunos), MP-024

**Entregáveis:**
- Upload/alteração de foto também na edição de aluno (hoje só existe no cadastro)
- Campo "Status da Matrícula" com 3 estados (Ativo/Trancado/Transferido) no modal de
  edição de aluno, substituindo o toggle binário atual

**Critérios de Aceite:**
- [ ] Foto pode ser alterada a qualquer momento, não só no cadastro
- [ ] Mudança de status de matrícula não quebra o relacionamento com turma/notas

**Commit esperado:** `fix(web): fecha gaps remanescentes da auditoria de mockups`

---

## Status dos MPs

| MP     | Título                          | Status      | Dependências       |
|--------|---------------------------------|-------------|--------------------|
| MP-001 | Estrutura do Repositório        | Concluído   | —                  |
| MP-002 | Documentação Consolidada        | Concluído   | —                  |
| MP-003 | Modelagem e Banco de Dados      | Concluído   | MP-001, MP-002     |
| MP-004 | Arquitetura da API (Base)       | Concluído   | MP-003             |
| MP-005 | Arquitetura do WEB (Base)       | Concluído   | MP-004             |
| MP-006 | Dashboard Admin                 | Concluído   | MP-004, MP-005     |
| MP-007 | Dashboard Diretor de Núcleo     | Concluído   | MP-006             |
| MP-008 | Dashboard Diretor Escolar       | Concluído   | MP-006             |
| MP-009 | Dashboard Coordenador           | Concluído   | MP-006             |
| MP-010 | Dashboard Professor             | Concluído   | MP-006             |
| MP-011 | Dashboard Aluno                 | Concluído   | MP-006             |
| MP-012 | Autenticação Completa           | Concluído   | MP-005             |
| MP-013 | Perfis e Permissões             | Concluído   | MP-005             |
| MP-014 | Núcleos                         | Concluído   | MP-004             |
| MP-015 | Escolas                         | Concluído   | MP-014             |
| MP-016 | Turmas                          | Concluído   | MP-015             |
| MP-017 | Alunos                          | Concluído   | MP-016             |
| MP-018 | Professores                     | Concluído   | MP-013, MP-016     |
| MP-019 | Avaliações (Provas)             | Concluído   | MP-016             |
| MP-020 | Gabaritos                       | Concluído   | MP-019             |
| MP-021 | OMR                             | Concluído   | MP-020             |
| MP-022 | Relatórios                      | Concluído   | MP-021             |
| MP-023 | Aplicativo Android              | Pendente (adiado — ver Nota de Sequenciamento) | MP-004, MP-021 |
| MP-024 | Meu Perfil e Configurações      | Concluído   | MP-005, MP-006     |
| MP-025 | Fundação Multi-Tenant (Secretaria/Modalidade) | Concluído | MP-003    |
| MP-026 | Perfis Secretário Educação e Aplicador | Concluído | MP-025         |
| MP-027 | Assinatura e Cobrança (Mercado Pago) | Pendente | MP-025            |
| MP-028 | Cadastro Autônomo (Individual/Institucional) | Pendente | MP-026, MP-027 |
| MP-029 | Migração Rede Individual → Institucional | Pendente | MP-028     |
| MP-030 | Motor de Permissões (Fase 1)    | Pendente    | MP-013, MP-018, MP-026, MP-028 |
| MP-031 | Migração p/ Permissões (Fase 2) | Pendente    | MP-030             |
| MP-032 | Visitas Pedagógicas (CRUD)      | Pendente    | MP-007             |
| MP-033 | Correção por Turma              | Pendente    | MP-021             |
| MP-034 | Relatórios Consolidados + PDF   | Pendente    | MP-022             |
| MP-035 | Gaps Menores (Auditoria)        | Pendente    | MP-017, MP-024     |
