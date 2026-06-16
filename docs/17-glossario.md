# 07 — Glossário

Termos e definições utilizados em toda a documentação e no código do Gabarito360.
Em caso de conflito de interpretação, este documento é a referência.

---

## Termos do Domínio

| Termo | Definição |
|-------|-----------|
| **Rede** | Conjunto de escolas sob uma mesma administração (ex.: Rede Municipal de Ensino de uma cidade). É o nível mais alto da hierarquia. |
| **Núcleo** | Subdivisão regional da rede, responsável por supervisionar um grupo de escolas. Também chamado de "Regional" em alguns sistemas. |
| **Escola** | Unidade de ensino identificada pelo código INEP. Pertence a um Núcleo. |
| **Turma** | Grupo de alunos de uma mesma série, em uma escola, num período letivo. Ex.: "9º Ano A". |
| **Aluno** | Estudante matriculado em uma Turma num dado período letivo. |
| **Matrícula** | Identificador único do aluno dentro da rede num período letivo. Formato: AAAA.NNNNN. |
| **Membro** | Usuário com perfil operacional vinculado a uma escola: Diretor Escolar, Coordenador ou Professor. |
| **Período Letivo** | Ano letivo de referência (ex.: 2026). Todos os dados de provas, turmas e matrículas são contextualizados por período letivo. |
| **Bimestre** | Subdivisão do período letivo em 4 partes. Usado como filtro de relatórios e dashboards. |
| **Prova** | Instrumento de avaliação com N questões de múltipla escolha, aplicado a uma ou mais turmas. |
| **Gabarito** | Conjunto de respostas corretas de uma Prova. Publicado antes da aplicação e usado pelo OMR para correção. |
| **Questão** | Item da Prova com alternativas de resposta (A, B, C, D ou E). |
| **Alternativa** | Opção de resposta de uma questão. |
| **Gabarito Oficial** | Gabarito publicado e imutável, disponível para uso no OMR. |
| **Cartão-Resposta** | Folha física preenchida pelo aluno durante a prova. Também chamado de "cartão" ou "folha de respostas". |
| **OMR** | Optical Mark Recognition — tecnologia de leitura automática de marcações em cartões-resposta via imagem fotográfica. |
| **Leitura OMR** | Processo de análise de uma imagem do cartão-resposta para extrair as respostas marcadas pelo aluno. |
| **Confiança OMR** | Percentual de certeza do sistema na leitura de uma marcação. Abaixo do limiar configurado, a leitura é classificada como ambígua. |
| **Cartão Ambíguo** | Cartão cuja leitura OMR gerou marcação com confiança insuficiente ou múltiplas marcações para uma mesma questão. Requer resolução manual. |
| **Resolução Manual** | Processo em que um usuário autorizado define a alternativa correta para uma questão ambígua. |
| **Nota Final** | Resultado numérico do aluno em uma prova: `(acertos / total_questoes) × nota_maxima`. |
| **Aprovado** | Status do aluno cuja nota final é igual ou superior à nota mínima de aprovação configurada. |
| **Recuperação** | Status do aluno cuja nota final é inferior à nota mínima de aprovação. |
| **Tendência** | Indicador de variação do desempenho em relação ao período anterior: ▲ melhora, ▼ queda, ● estável. |
| **SEGES** | Sistema de Gestão Escolar da Secretaria — sistema externo de onde alunos e matrículas podem ser sincronizados. |
| **INEP** | Instituto Nacional de Estudos e Pesquisas Educacionais Anísio Teixeira. O código INEP identifica unicamente cada escola no Brasil (8 dígitos). |
| **LGPD** | Lei Geral de Proteção de Dados (Lei 13.709/2018). O sistema deve estar em conformidade. |
| **gov.br DS** | Padrão Digital de Governo — design system oficial do governo brasileiro, adotado na interface do Gabarito360. |
| **GovBar** | Faixa superior padronizada do gov.br com links de Acessibilidade e Alto Contraste, presente em todas as telas autenticadas. |
| **KPI** | Key Performance Indicator — indicador numérico de desempenho exibido nos dashboards. |
| **Aluno em Atenção** | Aluno com nota abaixo da nota mínima de aprovação, listado nos dashboards de Coordenador e Professor para acompanhamento. |
| **Meta da Rede** | Nota média alvo configurada pelo Administrador para toda a rede (ex.: 7,0). |
| **Nota Mínima de Aprovação** | Nota mínima para que o aluno seja classificado como "Aprovado" (ex.: 6,0). |
| **Visita Pedagógica** | Visita programada do Diretor de Núcleo a uma escola, categorizada por urgência baseada no desempenho. |
| **Dashboard** | Painel inicial personalizado por perfil com KPIs, alertas e ações rápidas. |
| **Ação Rápida** | Card de atalho no dashboard que direciona para uma funcionalidade específica. |
| **Badge** | Elemento visual que exibe um status com cor semântica (success/warn/danger/info/muted). |
| **Mini-bar** | Barra de progresso compacta usada para representar notas e percentuais em tabelas. |
| **Donut** | Gráfico circular com valor central, usado para representar percentuais (ex.: nota, aprovação). |
| **Toast** | Notificação flutuante de feedback exibida temporariamente após ações do usuário. |
| **Stepper** | Componente de fluxo em etapas com indicador visual de progresso. Usado na criação de provas. |
| **Editor de Bolhas** | Interface interativa para preenchimento do gabarito, simulando as bolhas do cartão-resposta. |

---

## Termos de Perfil

| Código | Nome Completo | Abreviação Informal |
|--------|---------------|---------------------|
| ADMIN_REDE | Administrador da Rede | Admin |
| DIR_NUCLEO | Diretor de Núcleo | Diretor de Núcleo |
| DIR_ESCOLAR | Diretor Escolar | Diretor |
| COORDENADOR | Coordenador Pedagógico | Coordenador |
| PROFESSOR | Professor | Professor |
| ALUNO | Aluno | Aluno |

---

## Termos Técnicos

| Termo | Definição |
|-------|-----------|
| **API** | Aplicação Laravel que serve exclusivamente JSON. Responsável por autenticação, regras de negócio, OMR e relatórios. Reside em `apps/api`. |
| **WEB** | Aplicação Laravel que renderiza a interface administrativa (Blade views). Consome a API. Reside em `apps/web`. |
| **JWT** | JSON Web Token — mecanismo de autenticação stateless usado entre WEB/mobile e API. |
| **RBAC** | Role-Based Access Control — controle de acesso baseado em perfis, implementado na API. |
| **MariaDB** | Sistema de gerenciamento de banco de dados relacional. Único banco aprovado no projeto. |
| **Migration** | Arquivo Laravel que define a estrutura de uma tabela do banco de dados de forma versionada. |
| **Seed** | Arquivo Laravel que popula o banco com dados iniciais para desenvolvimento e testes. |
| **Eloquent** | ORM (Object-Relational Mapping) do Laravel, usado para interagir com o banco de dados. |
| **Blade** | Engine de templates do Laravel, usado no app WEB para renderizar as views. |
| **MP** | Micro-Passo — unidade mínima de implementação descrita no plano de execução (`docs/14-plano-de-execucao.md`). |
