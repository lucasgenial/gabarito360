# 06 — Regras de Negócio

Todas as regras abaixo têm rastreabilidade nos mockups (`docs/01-inventario-dos-mockups.md`),
nos requisitos funcionais (`docs/04-requisitos-funcionais.md`) e nas permissões (`docs/03-perfis-e-permissoes.md`).

---

## RN-001 — Autenticação e Acesso

### RN-001.1 — E-mail (revisada 2026-06-18)
- **Cadastro institucional:** aceita e-mail institucional (`nome@edu.gov.br` ou
  variações de domínio governamental) OU e-mail comum, a critério da secretaria/rede
  no momento da assinatura — deixou de ser uma exigência rígida, pois redes pequenas
  e escolas privadas (clientes SaaS) nem sempre têm domínio próprio.
- **Cadastro individual (professor autônomo):** aceita qualquer e-mail válido. Não há
  exigência de domínio institucional.
- Validação de formato de e-mail (RFC básico) continua obrigatória em ambos os casos.

### RN-001.2 — Perfil único por usuário
- Cada usuário possui exatamente um perfil ativo
- O perfil determina o dashboard exibido e todas as permissões base concedidas
- Dentro de uma rede individual, o perfil PROFESSOR pode ter permissões elevadas
  (equivalentes a DIR_ESCOLAR) via o motor de permissões configuráveis (RN-016.3) —
  isso não constitui um segundo perfil, é uma sobreposição de permissões no mesmo
  contexto de escola
- Não é permitido um usuário ter múltiplos perfis simultâneos

### RN-001.3 — Escopo de acesso
- O acesso do usuário é limitado ao seu escopo institucional: secretaria, rede,
  núcleo, escola, turma ou matrícula
- O escopo é definido no cadastro e não pode ser alterado pelo próprio usuário
  (exceto pelo próprio fluxo de migração de tenant, RN-016.4)
- A validação de escopo deve ser feita exclusivamente na API

### RN-001.4 — Aprovação e liberação de acesso (revisada 2026-06-18)
Existem agora dois fluxos de liberação de acesso, em vez de um único fluxo de
aprovação manual:

- **Cadastro institucional (SECRETARIO_EDUCACAO, ADMIN_REDE iniciando uma rede
  nova):** acesso liberado após confirmação de e-mail + assinatura de um plano
  institucional (trial ou pago) com pagamento confirmado. Não há mais aprovação
  manual por um humano administrador do sistema.
- **Convite dentro de uma instituição já existente** (ex.: ADMIN_REDE convida um
  DIR_ESCOLAR; DIR_ESCOLAR convida um PROFESSOR ou APLICADOR): o convidado recebe
  acesso imediato ao aceitar o convite por e-mail — não há estado de "aguardando
  aprovação" nesse caso, pois quem convidou já é uma autoridade validada dentro do
  escopo.
- **Cadastro individual (PROFESSOR autônomo):** acesso liberado imediatamente após
  pagamento confirmado pelo gateway (ou início de período trial, se houver). Sem
  aprovação manual.
- Usuários sem e-mail confirmado ou sem assinatura/convite válido não conseguem
  efetuar login.

### RN-001.5 — Sessão persistente
- A opção "Manter conectado" estende a duração do token de autenticação
- O token de acesso expira em tempo configurável pelo sistema
- O refresh token permite renovação silenciosa da sessão

---

## RN-002 — Estrutura Hierárquica

### RN-002.1 — Hierarquia obrigatória
- Uma Rede pertence opcionalmente a uma Secretaria (`secretaria_id` nullable —
  só preenchido quando uma secretaria gerencia múltiplas redes)
- Uma Escola pertence a exatamente um Núcleo
- Um Núcleo pertence a exatamente uma Rede
- Uma Turma pertence a exatamente uma Escola
- Um Aluno pertence a exatamente uma Turma por período letivo
- Uma Prova pertence a exatamente uma Escola e a um Professor

### RN-002.2 — Inativação em cascata
- Ao inativar uma Escola, suas Turmas ficam invisíveis nas listas ativas
- Alunos de uma Escola inativa não participam de estatísticas da rede
- Escolas inativas podem ser reativadas pelo Administrador da Rede
- A inativação não apaga dados históricos

### RN-002.3 — Vínculo de usuário ao escopo
- SECRETARIO_EDUCACAO: vinculado a uma Secretaria
- ADMIN_REDE: vinculado à Rede
- DIR_NUCLEO: vinculado a um Núcleo
- DIR_ESCOLAR, COORDENADOR, PROFESSOR, APLICADOR: vinculados a uma Escola
- ALUNO: vinculado a uma Turma (via entidade Aluno)

### RN-002.4 — Rede individual (SaaS, nova em 2026-06)
- Toda Rede tem um campo `tipo`: `institucional` ou `individual`
- Uma Rede do tipo `individual` é criada automaticamente quando um professor conclui
  o cadastro autônomo (RN-016.1), junto com 1 Núcleo padrão e 1 Escola — de forma
  transparente: a UI de uma rede individual não exibe os níveis "rede" e "núcleo"
- Uma Rede do tipo `individual` tem exatamente um usuário PROFESSOR como titular;
  esse titular pode convidar outros PROFESSOR/APLICADOR para colaborar na mesma
  escola, mas não pode criar uma segunda escola dentro da mesma rede individual
  (limite de 1 escola por rede individual)
- Uma Rede do tipo `individual` pode ser migrada para dentro de uma Rede
  `institucional` através do fluxo de absorção (RN-016.4); o inverso (institucional
  virar individual) não é suportado

---

## RN-003 — Gestão de Escolas

### RN-003.1 — INEP
- O código INEP é o identificador único da escola no MEC
- Deve ter exatamente 8 dígitos numéricos
- Não pode ser duplicado no sistema
- Obrigatório para cadastro

### RN-003.2 — Tipos de rede
- Valores válidos: Estadual, Municipal, Federal, Privada
- O tipo de rede impacta a hierarquia de gestão e relatórios

### RN-003.3 — Escola ativa
- Apenas escolas ativas aparecem em seleções de criação de provas e turmas
- Escolas inativas permanecem visíveis na listagem com opacidade reduzida
- O status ativo/inativo é gerenciado exclusivamente pelo Administrador da Rede

---

## RN-004 — Gestão de Turmas

### RN-004.1 — Nome único por escola e período
- O nome da turma deve ser único dentro de uma escola no mesmo período letivo

### RN-004.2 — Série
- A série deve ser informada (ex.: 6º ano, 7º ano, 8º ano, 9º ano)
- A série é usada em filtros e relatórios pedagógicos

### RN-004.3 — Status da turma
- **Em dia:** nenhum aluno em recuperação e nenhuma pendência de cartão
- **Em recuperação:** um ou mais alunos com nota abaixo da nota mínima de aprovação
- **Com pendências:** um ou mais cartões não lidos para provas no estado "Em correção"
- O status é calculado dinamicamente, não armazenado

### RN-004.4 — Importação por planilha
- Turmas e alunos podem ser importados via planilha (xlsx/csv)
- O sistema valida duplicidades de matrícula antes de importar
- Erros de importação devem ser relatados linha a linha

---

## RN-005 — Gestão de Alunos

### RN-005.1 — Matrícula única
- A matrícula deve ser única por período letivo dentro da rede
- Formato de exibição: AAAA.NNNNN (ex.: 2026.00412)

### RN-005.2 — Vínculo com turma
- Todo aluno deve estar vinculado a exatamente uma turma por período letivo
- A turma do aluno é o escopo primário para provas e resultados

### RN-005.3 — Acesso do aluno ao sistema
- O aluno pode ter um usuário no sistema para visualizar seus próprios resultados
- A criação do usuário do aluno é opcional e gerenciada pelo Coordenador ou Diretor Escolar
- O aluno só visualiza seus próprios dados (notas, resultados, próximas provas)

### RN-005.4 — Frequência
- A frequência do aluno é exibida no detalhe do aluno (ex.: 96%)
- O cálculo de frequência pode ser importado via integração SEGES
- Na ausência da integração, o campo permanece vazio ou como dado informativo manual

---

## RN-006 — Gestão de Membros (Usuários da Escola)

### RN-006.1 — Criação de membros
- Membros da escola são criados pelo Administrador da Rede ou pelo Diretor Escolar
- Ao criar, o membro fica no estado "aguardando aprovação" se criado pelo próprio usuário via formulário público
- Se criado diretamente pelo Diretor ou Admin, pode ser aprovado imediatamente

### RN-006.2 — Perfis permitidos por escola
- Uma escola pode ter: Diretor Escolar, Coordenador, Professor
- Cada escola deve ter no máximo um Diretor Escolar ativo

### RN-006.3 — Desativação de membro
- Membros desativados perdem acesso imediato ao sistema
- Os dados históricos (provas criadas, correções feitas) são preservados
- A reativação é possível pelo Administrador da Rede ou Diretor Escolar

---

## RN-007 — Gestão de Provas

### RN-007.1 — Ciclo de vida da prova
```
Rascunho → Publicada → Em correção → Corrigida
```
- **Rascunho:** gabarito incompleto ou não publicado; editável
- **Publicada:** gabarito completo e publicado; aguardando aplicação; não editável
- **Em correção:** aplicação realizada; cartões sendo lidos
- **Corrigida:** todos os cartões lidos e processados

### RN-007.2 — Gabarito obrigatório antes de publicar
- Não é possível publicar uma prova sem o gabarito completamente preenchido
- O contador "X/N preenchido" deve atingir N/N para habilitar o botão "Publicar"

### RN-007.3 — Imutabilidade do gabarito publicado
- Após a publicação, o gabarito não pode ser alterado
- A alteração exigiria nova versão da prova (funcionalidade pós-MVP)
- Exceção: o Administrador pode corrigir o gabarito antes de qualquer cartão ser processado

### RN-007.4 — Configurações de alternativas
- Valores válidos: 3 (A–C), 4 (A–D), 5 (A–E)
- Padrão do sistema: 5 alternativas
- A configuração é definida por prova, no passo de criação

### RN-007.5 — Anulação de questão
- Se a opção "Anular questão se todas marcadas" estiver ativa na prova:
  - Cartão com todas as alternativas marcadas para uma questão → questão anulada
  - Questão anulada conta como acerto para todos os alunos da prova

### RN-007.6 — Nota máxima
- Configurável por prova: 1 a 100
- Padrão: 10
- A nota final é proporcional: `(acertos / total_questoes) × nota_maxima`

### RN-007.7 — Tipo de pontuação
- **Pesos iguais:** todas as questões valem o mesmo (MVP)
- **Pesos personalizados:** cada questão tem peso diferente (pós-MVP)

### RN-007.8 — Vínculo prova-turma
- Uma prova pode ser aplicada a uma ou mais turmas da escola
- O vínculo é feito no passo 3 do criador de provas ("Cartão & Turmas")
- A data de aplicação é registrada por turma

---

## RN-008 — OMR (Leitura dos Cartões)

### RN-008.1 — Confiança mínima
- Leituras com confiança abaixo do limiar configurável são classificadas como "ambíguas"
- O limiar de confiança é configurável pelo Administrador (padrão: 90%)
- A confiança exibida nos mockups é de 98,6% (meta do produto)

### RN-008.2 — Estados do cartão
- **Pendente:** ainda não processado pelo OMR
- **Lido:** marcação detectada com confiança suficiente
- **Ambíguo:** marcação com confiança abaixo do limiar ou múltiplas marcações na mesma questão

### RN-008.3 — Resolução de ambíguos
- O usuário escolhe manualmente a alternativa correta
- Após resolução, o cartão muda de "Ambíguo" para "Lido"
- A resolução é registrada com o usuário responsável e timestamp
- Apenas Professor (da prova) e Coordenador podem resolver ambíguos

### RN-008.4 — Questão em branco
- Cartão com questão sem nenhuma marcação → questão em branco → não conta como acerto

### RN-008.5 — Revisão da leitura
- Após correção, é possível revisar uma leitura individual
- A revisão altera a nota do aluno e atualiza os relatórios em cascata
- O histórico de revisão é registrado

### RN-008.6 — Conclusão da leitura
- A prova muda para "Corrigida" quando todos os cartões estão no estado "Lido"
- Cartões ambíguos impedem a conclusão automática

---

## RN-009 — Notas e Resultados

### RN-009.1 — Cálculo da nota
```
nota_final = (acertos / total_questoes) × nota_maxima
```
- Arredondamento: uma casa decimal

### RN-009.2 — Status de aprovação
- **Aprovado:** nota_final >= nota mínima de aprovação (configurável; padrão 6,0)
- **Recuperação:** nota_final < nota mínima de aprovação
- Badge "Aprovado" exibido em verde; "Recuperação" em vermelho

### RN-009.3 — Média da turma
- Média aritmética das notas de todos os alunos da turma na prova

### RN-009.4 — Média da escola
- Média aritmética das notas de todos os alunos da escola no período letivo

### RN-009.5 — Meta da rede
- Meta configurável pelo Administrador (exibida nos dashboards; ex.: 7,0)
- Nota mínima de aprovação configurável (ex.: 6,0 / 60%)

### RN-009.6 — Tendência
- Calculada comparando com o bimestre/período anterior:
  - ▲ melhora de mais de 0,1 ponto
  - ▼ queda de mais de 0,1 ponto
  - ● variação dentro de ±0,1 (estável)

### RN-009.7 — Acertos por tema
- O resultado individual exibe acertos agrupados por tema/assunto
- A associação questão→tema é definida no cadastro da questão (pós-MVP por enquanto; no MVP o grupo é por prova)

---

## RN-010 — Relatórios e Dashboards

### RN-010.1 — Alertas críticos (Admin)
- **Cartões pendentes:** total de cartões ambíguos sem resolução em toda a rede
- **Escolas abaixo da meta:** escolas com média inferior à meta configurada
- **Integração com atenção:** última sincronização do SEGES atrasada além do limiar

### RN-010.2 — Visita pedagógica (Diretor de Núcleo)
- Visitas classificadas por urgência com base no desempenho da escola:
  - **Prioritária** (danger): escola abaixo da meta
  - **Monitorar** (warn): escola próxima à meta
  - **Referência** (success): escola acima da meta
- Urgência recalculada a cada atualização dos dados

### RN-010.3 — Alunos em atenção
- Alunos com nota abaixo da nota mínima de aprovação
- Exibidos no dashboard do Coordenador e do Professor
- Ordenados por nota (pior primeiro)

### RN-010.4 — Top 5 escolas (Admin)
- Classificação das 5 escolas com maior média no período atual
- Exibida como gráfico de barras horizontais

### RN-010.5 — Últimos acessos (Admin)
- Tabela com os últimos acessos de usuários ao sistema
- Campos: nome, perfil, escola, último acesso, status (Online/Ativo/Offline)

---

## RN-011 — Integração SEGES

### RN-011.1 — Sincronização periódica
- Dados de alunos e matrículas podem ser importados do SEGES automaticamente
- A sincronização gera log de status (sucesso/erro/atraso)

### RN-011.2 — Alerta de atraso
- Se a sincronização atrasar além do limiar configurado, um alerta é exibido no dashboard do Admin
- O alerta exibe o tempo de atraso

### RN-011.3 — Tolerância a falhas
- Falha na sincronização não interrompe o funcionamento do sistema
- Os dados existentes permanecem válidos até a próxima sincronização bem-sucedida

---

## RN-012 — Período Letivo

### RN-012.1 — Bimestres
- O ano letivo é dividido em 4 bimestres
- Filtros de relatório usam o bimestre como unidade de tempo
- Dashboards exibem o bimestre atual como padrão

### RN-012.2 — Ano letivo
- O ano letivo é configurado nas configurações do sistema pelo Administrador
- Todos os dados de provas, turmas e matrículas são contextualizados por ano letivo
- Apenas um ano letivo pode estar ativo por vez por rede

---

## RN-013 — Configurações do Sistema

### RN-013.1 — Escopo das configurações
- Configurações globais da rede: gerenciadas pelo Administrador da Rede
- Configurações da escola: gerenciadas pelo Administrador ou Diretor Escolar

### RN-013.2 — Parâmetros configuráveis pela rede
- Meta de nota da rede (ex.: 7,0)
- Nota mínima de aprovação (ex.: 6,0)
- Limiar de confiança OMR (ex.: 90%)
- Limiar de alerta de atraso SEGES (em minutos)
- Número padrão de alternativas por questão

### RN-013.3 — Parâmetros configuráveis pela escola
- Parâmetros padrão de avaliação da escola (podem diferir dos globais da rede)
- Calendário de provas

---

## RN-014 — Exportação de Dados

### RN-014.1 — Exportação para PDF
- Gabarito da prova: exportável por Professor, Coordenador, Diretor
- Resultado individual do aluno: exportável pelo aluno (seus dados), Professor, Coordenador, Diretor
- Ficha do aluno: exportável por Coordenador, Diretor

### RN-014.2 — Mecanismo de exportação
- No MVP, exportação de PDF via `window.print()` na interface web
- O conteúdo imprimível deve estar formatado corretamente para impressão

### RN-014.3 — Exportação de planilha
- Não prevista no MVP; planejada para versões futuras

---

## RN-015 — Assinatura e Cobrança (SaaS, nova em 2026-06)

### RN-015.1 — Planos
- Existem planos do tipo `individual` (voltados a um único professor) e
  `institucional` (voltados a redes/secretarias, com limites maiores de escolas,
  turmas e usuários)
- Cada plano tem: nome, público (individual/institucional), preço, periodicidade
  (mensal/anual), limites de uso (nº de escolas, turmas, provas/mês, usuários)
- Planos são gerenciados apenas pela equipe do Gabarito360 (não há tela de criação de
  planos para clientes — fora de escopo neste momento)

### RN-015.2 — Titularidade da assinatura
- Quem cria a rede individual ou inicia o cadastro institucional autônomo é o
  **titular** dessa assinatura
- Apenas o titular vê e gerencia a assinatura (módulo "Assinatura e Cobrança",
  `docs/03-perfis-e-permissoes.md`)
- A titularidade pode ser transferida apenas por solicitação manual ao suporte
  (fora de escopo de autoatendimento no MVP deste módulo)

### RN-015.3 — Gateway de pagamento
- Mercado Pago é o gateway oficial para cobrança recorrente (PIX, boleto, cartão)
- A API nunca armazena dados de cartão — apenas referências (IDs) retornadas pelo
  gateway
- Webhooks do Mercado Pago atualizam o status da assinatura (`ativa`, `atrasada`,
  `cancelada`)

### RN-015.4 — Suspensão por inadimplência
- Após N dias de atraso (parâmetro configurável, padrão 7 dias) sem confirmação de
  pagamento, a assinatura passa a `atrasada` e o acesso é bloqueado para todos os
  usuários daquela rede/escola, exceto o titular, que continua podendo acessar
  apenas a tela de "Assinatura e Cobrança" para regularizar
- Dados não são apagados por inadimplência — apenas o acesso é suspenso

### RN-015.5 — Período de teste (trial)
- Novo cadastro (individual ou institucional) pode iniciar com um período de teste
  gratuito (parâmetro configurável, padrão 14 dias) antes de exigir pagamento
- Ao final do trial sem pagamento confirmado, aplica-se RN-015.4

---

## RN-016 — Cadastro Autônomo e Migração de Tenant (SaaS, nova em 2026-06)

### RN-016.1 — Cadastro individual (professor autônomo)
- O professor escolhe "Conta Individual" na tela de cadastro, informa dados básicos
  e e-mail (sem exigência de domínio institucional, RN-001.1), escolhe um plano
  individual e confirma o pagamento (ou inicia trial, RN-015.5)
- Ao confirmar, o sistema cria automaticamente 1 Rede (`tipo=individual`), 1 Núcleo
  padrão e 1 Escola, e vincula o professor como titular dentro dessa escola

### RN-016.2 — Cadastro institucional autônomo
- SECRETARIO_EDUCACAO ou ADMIN_REDE escolhe "Conta Institucional", informa os dados
  da instituição, escolhe um plano institucional e confirma e-mail + pagamento
  (ou trial)
- Ao confirmar, o sistema cria a Secretaria (se aplicável) ou a Rede
  (`tipo=institucional`) com o usuário como titular

### RN-016.3 — Permissões elevadas em rede individual
- Dentro de uma rede individual, o motor de permissões configuráveis (RN a definir
  em `docs/12-arquitetura.md` junto ao MP-029/030) concede ao professor titular,
  por padrão, todas as permissões equivalentes a DIR_ESCOLAR dentro da própria escola
- Essas permissões podem ser revogadas individualmente pelo próprio titular para si
  mesmo não é permitido (ele sempre mantém controle total da própria rede individual)

### RN-016.4 — Migração/absorção de rede individual para institucional
- Processo manual, iniciado apenas pelo titular da rede individual, nunca pela
  instituição que está "absorvendo"
- O titular recebe um convite/código da instituição (gerado por um ADMIN_REDE ou
  DIR_NUCLEO institucional) e confirma a migração
- Ao confirmar: a Escola, suas Turmas, Alunos, Provas e histórico são movidos para
  dentro da Rede institucional (ajuste de `escola_id`/`turma_id` permanece igual,
  apenas a Escola passa a pertencer a um Núcleo da rede institucional); a assinatura
  individual é cancelada e o professor passa a ser coberto pelo plano institucional
- Esse processo é irreversível por autoatendimento (reversão exigiria suporte manual)
