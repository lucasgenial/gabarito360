# 014 — Perfis institucionais — Planejamento

**Implementação:** Concluída
**Etapa:** Concluída
**Conforme princípios:** versão 1
**Prioridade no plano mestre:** sem posição nas ondas do §5 (concluída). Pendências: backlog de perfis futuros (sem priorização); Secretário de Educação contextual depois de FN-017 (Onda 2); código de acesso do responsável com FN-067 (Onda 4).
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/70 (A1-C), docs/132–135 (PF1), docs/139
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Representar as funções reais da escola além da matriz original de oito perfis: secretário escolar, vice-diretor e auxiliar de secretaria, cada um com política própria e sem herdar poderes de direção. Inclui a regra que impede a mesma pessoa de acumular funções administrativas e pedagógicas na mesma escola, salvo exceção autorizada pela equipe da plataforma.

## 2. Escopo
- **Inclui:** perfil `secretario_escolar` (A1-C); perfis `vice_dir_escolar` e `auxiliar_secretaria` (PF1); extras concedíveis do vice-diretor; regra de grupos funcionais e exceção por Suporte/Desenvolvedor com chamado e motivo; inclusão desses perfis em convites, gestão de vínculos e consumidores.
- **Não inclui:**
  - Motor de permissões e regra "quem cadastra quem": FN-012.
  - Convites e gestão por vínculo: FN-015.
  - Secretário de Educação contextual: depende de FN-017.
  - Código de acesso do responsável e portal: FN-067.
  - Consulta de identidade pelo vice-diretor: FN-016 (decisão §4 item 6).
  - Importação de alunos pelo secretário (`aluno.importar` sem tela): FN-034.
  - Foto e avatar de perfil (nascido no PF1-C): FN-019.

## 3. Fontes de verdade
- Mockups: sem mockup próprio; reutiliza as telas de equipe e convite.
- Origem: docs/70 (`docs/70-a1-c-secretario-escolar.md`), docs/132 (`docs/132-provas-autoria-cabecalho.md`), docs/133 (`docs/133-planejamento-perfis-institucionais.md`), docs/134 (`docs/134-pf1-b-matriz-perfis-prioritarios.md`), docs/135 (`docs/135-pf1-c-perfis-escolares.md`), docs/139 (`docs/139-id1-dc1-ativacao-e-sequencia.md`); docs/03 §8 (A1-C e PF1) (`docs/03-perfis-e-permissoes.md`).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Código: `apps/api/app/Support/CatalogoPermissoes.php` (`SECRETARIO_ESCOLAR`, `VICE_DIRETOR`, `VICE_EXTRAS`, `AUXILIAR_SECRETARIA`), `PerfisEspaco.php`, `PerfisEscolares.php`, `GruposFuncionais.php`, `Cadastro.php`, `apps/api/app/Http/Controllers/Api/ExcecaoFuncionalController.php`, `apps/web/app/Http/Controllers/ExcecaoFuncionalController.php`.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-012 permissoes-motor | Matriz e extras | Concluída |
| FN-015 vinculos-contexto | Os novos perfis só existem em vínculo de escola aceito e vigente | Concluída |
| FN-013 acessos-internos | Exceção de grupos funcionais concedida por Suporte/Desenvolvedor | Concluída |
| FN-017 convites-cpf | Secretário de Educação contextual | Pendente |
| FN-067 portal-responsaveis | Código de acesso do responsável | Pendente |

**Funcionalidades que dependem desta:** FN-019 (IM1 nasceu do PF1-C), FN-038 (coordenação e vice-diretor em disciplinas), FN-017 (catálogo de perfis aprovado entra no VI1), FN-067 (perfil responsável).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (A1-C) | Secretário escolar só em vínculo de escola; alunos e turmas sem notas/provas | Gestão por admin rede, dir. núcleo e dir. escolar | Entregue (produção) | PR #104 |
| 2.1 (PF1-A) | Inventário de funções | — | Entregue (homologação, validado) | PR #163 |
| 2.2 (PF1-B) | Matriz vice-diretor / secretário / auxiliar | Aprovada em 03/11/2026 | Entregue | docs/134 |
| 2.3 (PF1-C1..C5) | Catálogo e contexto, gestão e convites, consumidores, interface, CI e homologação | Publicado na homologação e validado | Entregue (homologação, validado) | PR #164, #165; docs/135, 139 |
| 2.4 (PF1-C) | Grupos funcionais e exceção por Suporte/Desenvolvedor | Bloqueio com mensagem e exceção com chamado | Entregue (homologação) | docs/134, 135 |
| 3.1 | Backlog de perfis futuros (coordenador de área, orientador, AEE, responsável, auditor etc.) | Planejamento próprio | Pendente | docs/133 |
| 3.2 | Secretário de Educação contextual | Contrato contextual no VI1 | Pendente | depende de FN-017 |
| 3.3 | Código de acesso do responsável | — | Adiada | depende de FN-067 |
| 3.4 | Extras do vice-diretor em disciplinas e consulta de identidade | Decisão | Pendente | docs/139; §4 item 6 |

## 6. Contrato técnico (proporcional ao risco)
- **Políticas independentes:** secretário escolar com 8 permissões fixas; vice-diretor com 11 padrões e 18 extras concedíveis; auxiliar de secretaria com `escola.ver`, `turma.ver`, `aluno.ver`, `aluno.gerir`. Nenhum desses perfis tem permissão sem vínculo selecionado; o campo global da conta não autoriza.
- **Compatibilidade:** os três perfis só existem em espaço do tipo `escola` (`PerfisEspaco::POR_TIPO`).
- **Grupos funcionais:** administrativos (`secretario_escolar`, `auxiliar_secretaria`) não acumulam com pedagógicos (`professor`, `coordenador`, `dir_escolar`, `vice_dir_escolar`, `aplicador`) na mesma escola em vigência sobreposta, salvo exceção em `usuario_funcoes_excecoes`.
- **Exceção:** API `GET configuracoes/funcoes/opcoes`, `POST configuracoes/funcoes/consultar` (10/min), `POST configuracoes/funcoes` (10/min); exige contexto interno de Suporte ou Desenvolvedor; versão otimista; histórico em `usuario_funcoes_eventos`; ninguém autoriza a própria exceção.

### Perfis futuros (backlog)

Origem: docs/133. As capacidades abaixo são limites propostos. Nenhum perfil está habilitado. Os identificadores técnicos novos são preliminares. Cada perfil depende da revisão da matriz do docs/03 antes da ativação. Novos recortes de alcance precisam valer na API, em arquivos, relatórios, filas e exportações antes da habilitação.

| Função ou perfil | Alcance proposto | Capacidades e limites | Tratamento |
|---|---|---|---|
| Coordenador de área ou componente | Área, componentes e turmas atribuídas na escola | Acompanhamento pedagógico e de avaliações do recorte. Sem visão geral da escola por padrão | Especialização da coordenação, após FN-038. Não reutilizar Coordenador irrestrito |
| Coordenador de curso | Curso e suas turmas ou unidades autorizadas | Organização e acompanhamento acadêmico do curso. Sem outros cursos por padrão | Futuro, após modelar cursos e unidades acadêmicas |
| Orientador educacional ou Supervisor pedagógico | Alunos, etapas ou turmas atribuídas | Acompanhamento e intervenções autorizadas. Sem editar gabarito ou nota por padrão | Avaliar base comum com capacidades distintas. Registros reservados exigem proteção específica |
| Gestor pedagógico de núcleo ou rede | Núcleo ou rede atribuídos | Indicadores e acompanhamento. Sem administração de contas, contratos ou edição de resultados por padrão | Separado do Administrador da rede. Priorizar consolidados |
| Assistente docente, Monitor ou Tutor | Componentes e turmas atribuídas, com vigência | Apoio delegado. Sem reabrir correção, alterar notas ou acessar gabarito reservado por padrão | Não confundir com Aplicador |
| Professor de AEE | Atendimentos e alunos atribuídos | Preferir Professor com lotação específica. Dados de atendimento reservado exigem capacidade e armazenamento próprios | Não concede acesso a todos os dados sensíveis da escola |
| Responsável pelo aluno (`responsavel_aluno`) | Cada aluno com relação verificada e vigente | Consultar resultados e comunicações liberados para aquele aluno. Várias relações por conta, sem ver a turma nem outros responsáveis | Portal futuro (FN-067). Depende de identidade, vínculo responsável–aluno e regras de liberação |
| Auditor institucional | Instituição ou caso e período autorizados | Consulta de trilhas e evidências, com minimização e registro do acesso. Sem editar eventos, provas ou resultados | Separado de Desenvolvedor e Suporte. Não é usuário direto do banco |
| Gestor financeiro institucional | Contrato, cliente ou instituição autorizados | Assinatura, cobrança e documentos financeiros. Sem notas, gabaritos ou dados pedagógicos | Distinto do Financeiro interno da plataforma. Titularidade contratual não dá direção escolar |
| Gestor de privacidade | Solicitação ou incidente autorizado | Acompanhar pedidos de titulares e providências atribuídas. Exportação e eliminação exigem autorização própria e auditoria | Capacidade por caso. Não cria leitura irrestrita de pessoas |
| Autor ou Revisor de conteúdo | Banco de questões ou coleção autorizada | Redigir, revisar e publicar conforme fluxo futuro. Sem dados de alunos. Autoria e revisão são capacidades diferentes | Preparar para editor futuro, com enunciados, questões e avaliações em entidades separadas |

Notas do docs/133:
- Cargo é a denominação funcional. Perfil é o contrato de acesso. Lotação define turmas, componentes, curso, turno e intervalo. Não criar perfil de segurança novo para cada nome de cargo ou disciplina.
- Professor substituto ou temporário usa o perfil Professor com lotação e vigência próprias. Interinidade de direção é designação explícita, datada e auditada. Não decorre do cargo de vice-diretor. Sem hierarquia numérica que faça um cargo herdar os poderes dos anteriores.
- Diretor de campus, reitor, mantenedor e diretor adjunto mantêm o cargo preservado. O contrato de acesso só reutiliza uma base de direção depois de modelar campus e unidades. Não inventar escola nem conceder toda a rede para acomodar nomenclatura.
- Procurador ou recenseador institucional só ganha perfil operacional com módulo de informação regulatória. Cargo cadastrado não equivale a integração com MEC ou INEP.
- Bibliotecário, serviços gerais, portaria, transporte e alimentação podem constar de um futuro cadastro de profissionais, sem acesso a provas nem histórico acadêmico.
- Regras antes de ativar um perfil: definir ações padrão, extras, ações proibidas, alcance e quem convida e gere o perfil. Ninguém amplia o próprio alcance. Registrar convite, aceite, início, fim, concessão, retirada e substituição. Testar a mesma pessoa em duas funções na mesma escola, em escolas diferentes, perda de lotação, revogação, início futuro e acesso direto por ID ou arquivo.
- Transferência entre instituições continua sob FN-025. Os novos cargos não recebem essa capacidade automaticamente.

## 7. Interface
Perfis aparecem nas telas de equipe, convite e permissões (FN-012, FN-015). Tela interna `funcoes/index` em `/configuracoes/usuarios`. Conferência por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 6 — Matriz ID1 e extras do vice-diretor | Aprovar `identidade.usuario.consultar`/`identidade.aluno.consultar` e os extras do vice-diretor | Plano mestre: aprovar a matriz ID1 (destrava VI1, TR1/TE1, L4-D) | Pendente |
| Backlog de perfis futuros | Priorizar individualmente | Registrar cada perfil como candidata quando houver pedido real | Pendente |

Nenhuma divergência entre o plano mestre e o código encontrada.

## 9. Ramos desta entrega
Nenhum aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | documento criado a partir do inventário e do código | lucasgenial |
