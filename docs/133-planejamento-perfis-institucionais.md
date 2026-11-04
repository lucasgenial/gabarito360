# Catálogo ampliado de funções e perfis — proposta

O responsável solicitou considerar vice-diretor escolar, secretário escolar e outras funções para o futuro da aplicação. Esta proposta organiza o desenvolvimento; **não habilita perfis, não altera a matriz oficial nem concede acessos**. O contrato vigente continua em [03 — Perfis e permissões](03-perfis-e-permissoes.md). A implementação seguirá a revisão da matriz antes da ativação de cada perfil.

## Situação verificada no código

Referências: `apps/api/app/Support/PerfisEspaco.php`, `ContextoVinculo.php`, `Cadastro.php`, `Permissao.php` e doc03. Compatibilidade de perfil não significa que todos os seus portais ou consumidores estejam concluídos.

| Contexto | Perfis existentes | Observação |
|---|---|---|
| Escola | Diretor escolar, Secretário escolar, Coordenador, Professor, Aplicador, Aluno | Secretário escolar já implementado: alunos/turmas, sem desempenho nem gestão de equipe. Aluno ainda depende de completar seus consumidores individuais. |
| Núcleo/regional | Diretor de núcleo | Limitado ao núcleo e às instituições subordinadas. |
| Rede | Administrador da rede | Limitado à própria rede. |
| Pessoal | Professor autônomo | Espaço pessoal próprio; selecionar instituição no catálogo não concede acesso escolar. |
| Equipe da plataforma | Desenvolvedor, Suporte, Financeiro | Contexto interno separado. Não concede acesso pedagógico geral. Atendimento excepcional depende do contrato AU3. |
| Secretaria de Educação | Secretário de Educação no catálogo documental | Não consta em POR_TIPO no regime contextual atual. Exige desenho de espaço/seleção e habilitação antes de declarar o acesso disponível. |
| Vice-direção | Vice-diretor escolar ausente | Será perfil próprio proposto; não cadastrar como diretor para contornar a ausência. |

## Pessoa, cargo, vínculo e alcance

A mesma pessoa pode atuar como diretor, vice-diretor, coordenador e professor, inclusive na mesma escola. A identidade e a conta permanecem únicas. Cada vínculo identifica instituição, perfil, situação e vigência; a seleção na aba define as permissões, sem unir poderes dos demais vínculos.

Cargo é a denominação funcional usada pela instituição. Perfil é um contrato de acesso. Lotação define turmas, componentes curriculares, curso, turno e intervalo de atuação quando aplicável. Não criar uma pessoa nova, nem um perfil de segurança novo, para cada nome de cargo ou disciplina.

Professor substituto/temporário usa o perfil Professor com lotação e vigência próprias. Interinidade de direção é uma designação explícita, datada e auditada; não decorre automaticamente do cargo de vice-diretor. Perfis não devem usar hierarquia numérica que faça um cargo herdar todos os poderes dos anteriores.

## Propostas por prioridade

As capacidades abaixo são limites propostos, ainda sujeitos à matriz detalhada. Os identificadores técnicos novos são preliminares. Novos recortes de alcance devem ser aplicados na API, em arquivos, relatórios, filas e exportações antes da habilitação.

| Função/perfil | Alcance proposto | Capacidades e limites | Tratamento |
|---|---|---|---|
| Vice-diretor escolar (`vice_dir_escolar`) | Escola do vínculo | Acompanhar todas as turmas/provas da escola e exercer gestão explicitamente aprovada. Sem autoconcessão, gestão de pares/superiores ou transferência entre escolas por simples título. Criação de prova institucional depende de permissão definida. | Prioridade imediata de definição; pode ter outro vínculo Professor. |
| Auxiliar de secretaria (`auxiliar_secretaria`) | Escola e tarefas delegadas | Cadastro/atualização de alunos e matrículas; sem notas, gabaritos, gestão de equipe ou autorização final de transferência. Exportações não são automáticas. | Próximo complemento administrativo; diferente do Secretário escolar responsável. |
| Coordenador de área/componente | Área, componentes e turmas atribuídas na escola | Acompanhamento pedagógico e de avaliações daquele recorte; sem visão geral da escola por padrão. | Especialização da coordenação, dependente de DC1. Não reutilizar Coordenador irrestrito. |
| Coordenador de curso | Curso e suas turmas/unidades autorizadas | Organização e acompanhamento acadêmico do curso; sem outros cursos por padrão. | Futuro, após modelar cursos/unidades acadêmicas e seus vínculos. |
| Orientador educacional / Supervisor pedagógico | Alunos, etapas ou turmas atribuídas | Acompanhamento e intervenções pedagógicas autorizadas; sem editar gabarito ou nota por padrão. | Avaliar base comum de acompanhamento com capacidades distintas; registros reservados exigem proteção específica. |
| Gestor pedagógico de núcleo/rede | Núcleo ou rede atribuídos | Indicadores e acompanhamento pedagógico; sem administração de contas, contratos ou edição de resultados por padrão. | Separado do Administrador da rede; priorizar consolidados e justificar acesso individual. |
| Assistente docente / Monitor / Tutor | Componentes e turmas atribuídas, com vigência | Apoio às atividades explicitamente delegado; sem reabrir correção, alterar notas ou acessar gabarito reservado por padrão. | Não confundir com Aplicador, cujo alcance atual é a aplicação do dia. |
| Professor de AEE | Atendimentos e alunos atribuídos | Preferir Professor com lotação específica quando suficiente. Dados de atendimento reservado exigem capacidade e armazenamento próprios. | AEE é uma função pedagógica; não concede acesso a todos os dados sensíveis da escola. |
| Responsável pelo aluno (`responsavel_aluno`) | Cada aluno com relação verificada e vigente | Consultar resultados/comunicações liberados para aquele aluno. Várias relações por conta, sem visibilidade da turma ou de outros responsáveis. | Portal futuro; depende de identidade, vínculo responsável–aluno e regras de liberação. |
| Auditor institucional | Instituição/caso e período autorizados | Consulta de trilhas e evidências, com minimização e registro do acesso; sem editar eventos, provas ou resultados. | Separado do desenvolvedor e do suporte. Não é usuário direto do banco. |
| Gestor financeiro institucional | Contrato/cliente/instituição autorizados | Assinatura, cobrança e documentos financeiros. Sem notas, gabaritos ou dados pedagógicos gerais. | Distinto do Financeiro interno da plataforma. Titularidade contratual não concede direção escolar. |
| Gestor de privacidade | Solicitação ou incidente autorizado | Acompanhar pedidos de titulares e providências atribuídas. Exportação/eliminação exigem autorização própria e ficam auditadas. | Capacidade por caso; não cria um perfil com leitura irrestrita de pessoas. |
| Autor / Revisor de conteúdo | Banco de questões ou coleção autorizada | Redigir, revisar e publicar conteúdo conforme fluxo futuro; sem dados de alunos. Autoria e revisão são capacidades diferentes. | Preparar para editor futuro, com enunciados, questões e avaliações em entidades separadas. |

Diretor de campus, reitor, mantenedor e diretor adjunto devem ter seu cargo preservado. O contrato de acesso pode reutilizar uma base de direção/gestão apropriada ao tipo de instituição, **após** modelar campus e unidades; não inventar escola ou conceder toda a rede para acomodar a nomenclatura. Procurador/recenseador institucional só deve ganhar perfil operacional quando existir um módulo correspondente de informação regulatória; cargo cadastrado não equivale a integração com MEC/INEP.

Bibliotecário, serviços gerais, portaria, transporte e alimentação podem constar do cadastro de profissionais quando esse cadastro existir. Não precisam receber acesso a provas ou histórico acadêmico para registrar o cargo. Evitar expandir o produto para módulos sem requisito definido.

## Regras antes de ativar um perfil

- Definir ações padrão, extras permitidos, ações proibidas, alcance e quem pode convidar/gerir cada perfil. Ninguém se promove nem amplia o próprio alcance.
- Registrar convite, aceite, início/fim, concessão, retirada e eventual substituição. Vínculos futuros não liberam acesso antes da vigência; outros vínculos da pessoa permanecem independentes.
- Delimitar histórico consultável após mudança de função ou instituição. Desativar um vínculo não transfere a autoria nem apaga fatos antigos; retenção não significa manter acesso da pessoa.
- Manter o perfil/contexto de criação nas provas. Função gestora não pode disfarçar autoria como professor ao trocar de vínculo. A futura inclusão da vice-direção exige revisar o conjunto de funções institucionais e seus testes, sem alterar silenciosamente o legado.
- Transferência entre instituições continua sob TR1/TE1, com autorização específica, origem, destino, executor e recibo. Cargo escolar não concede transferência geral. A cessão/recebimento por escola deve seguir o papel explicitamente definido no fluxo.
- Testar a mesma pessoa em duas funções na mesma escola, escolas diferentes, perda de lotação, revogação, início futuro e acesso direto por ID/arquivo. A interface representa as capacidades efetivas retornadas pela API.

## Planejamento atualizado

| Entrega | Conteúdo | Estado/dependência |
|---|---|---|
| Correção de autoria e Meu espaço | Isolamento docente, autoria contextual e troca de vínculo em modal | PR162 integrado; publicação/validação da homologação registrada no próprio PR. |
| PF1-A — inventário | Conferir catálogo real, funções ausentes e consumidores incompletos | Proposta documentada aqui. Nenhum novo acesso habilitado. |
| PF1-B — contrato dos prioritários | Vice-diretor; revisão do Secretário escolar; Auxiliar de secretaria; hierarquia de gestão | Próxima definição de produto. Matriz precisa ser aprovada antes de semear/habilitar. |
| PF1-C — implementação e verificação | Contexto, API, telas, auditoria, testes e migração de cada perfil aprovado | Planejada; sem substituição improvisada por perfil mais poderoso. |
| ID1 — identidade | Unicidade pessoal, proteção e conciliação | Mantida como próxima etapa estrutural do plano115; não iniciada por este documento. |
| VI1 — vínculos e convites | CPF-primeiro, aceite, agenda, notificações e múltiplas funções | Incorpora o catálogo aprovado; Secretário de Educação requer contrato contextual próprio. |
| DC1 — disciplinas e lotação | Componentes, turmas, responsáveis, vigência e recortes pedagógicos | Pré-requisito de coordenação de área e apoio docente restritos. |
| TR1/TE1 — transferências | Capacidades específicas e cessão/recebimento auditados | Não conceder automaticamente aos novos cargos. |
| Portais e módulos futuros | Responsáveis, AEE reservado, cursos/campus, financeiro institucional e conteúdo | Planejar por módulo; não declarar disponível só por cadastrar um nome de perfil. |

Homologação recebe implementações concluídas e validadas. A aprovação de uma proposta documental não promove código à produção; a publicação de produção continua sob decisão expressa do responsável. Não mudar a sequência do plano115 ou iniciar ID1 implicitamente.

## Referências de funções educacionais

O [Caderno de conceitos do Censo Escolar/INEP](https://download.inep.gov.br/pesquisas_estatisticas_indicadores_educacionais/censo_escolar/orientacoes/matricula_inicial/caderno_de_conceitos_e_orientacoes_censo_escolar_2024.pdf) descreve funções de coordenação, orientação, supervisão e apoio. As [orientações do INEP](https://www.gov.br/inep/pt-br/acesso-a-informacao/perguntas-frequentes/censo-escolar/) distinguem funções docentes, auxiliares e AEE. O [IFSULDEMINAS](https://portal.ifsuldeminas.edu.br/procuradoria-educacional/regulacao-da-educacao-superior/449-coordenador-de-curso) descreve a atuação de coordenador de curso junto às funções institucionais de informação. Essas referências informam a nomenclatura; **as permissões propostas são decisões de produto do Gabarito360**, não exigências extraídas das fontes.
