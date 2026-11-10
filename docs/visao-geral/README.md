# Visão geral do Gabarito360

**Atualizado em:** 10/11/2026, por lucasgenial

Este documento descreve o que é o Gabarito360, que problema ele resolve, quem o usa e como as peças se ligam. A leitura leva cerca de 15 minutos e dispensa conhecimento técnico.

## 1. O que é

O Gabarito360 é uma plataforma web de gestão de avaliações educacionais. Ela atende redes de ensino, escolas e professores.

A plataforma cobre o ciclo completo de uma avaliação:

1. A pessoa cria a prova e cadastra o gabarito oficial.
2. O sistema gera os cartões-resposta em PDF, um por aluno.
3. A escola aplica a prova em papel.
4. Alguém fotografa cada cartão preenchido, pelo celular ou pelo computador.
5. O sistema lê as marcações (leitura OMR, definida no [glossário](../glossario.md)).
6. Uma pessoa revisa as leituras duvidosas.
7. O sistema calcula as notas e monta os resultados e relatórios.
8. Os painéis mostram o desempenho por aluno, turma, escola, núcleo e rede.

O Gabarito360 não é um sistema genérico de gestão escolar. Ele não é continuação de versões anteriores do produto. É uma versão nova, construída sobre mockups e documentação própria.

## 2. Problema que resolve

| Problema | Como o Gabarito360 responde |
|---|---|
| A correção manual é lenta e sujeita a erro. | O sistema lê a foto do cartão e calcula a nota. |
| Cartões mal preenchidos travam a correção. | Uma fila de revisão leva a pessoa às leituras duvidosas. |
| A gestão não enxerga o desempenho. | Painéis por perfil mostram os números do escopo de cada pessoa. |
| A rede tem vários níveis de gestão. | Cada pessoa vê só a sua parte da hierarquia: rede, núcleo, escola, turma. |
| Os relatórios são montados à mão. | O sistema gera relatórios consolidados e PDFs. |
| O professor sem escola formal não tem onde trabalhar. | O professor autônomo tem um espaço pessoal próprio (seção 5.1). |

Os mockups e a documentação inicial declaravam duas metas de produto: 12 segundos por cartão lido e 98,6% de precisão de leitura. Ninguém mediu essas metas no sistema atual. Trate-as como intenção, não como garantia. A medição de desempenho é a funcionalidade FN-077.

## 3. Para quem é

| Público | Exemplos |
|---|---|
| Redes públicas de ensino | Secretarias municipais e estaduais, com núcleos regionais e muitas escolas. |
| Escolas | Públicas e privadas, ligadas a uma rede ou independentes. |
| Professores | Vinculados a uma escola ou autônomos. |
| Equipe interna do produto | Desenvolvimento, suporte e financeiro, que atendem os clientes. |

O contexto é brasileiro. O sistema trata dados de estudantes, inclusive menores de idade. Por isso a proteção de dados (LGPD, Lei Geral de Proteção de Dados) orienta várias decisões. As funcionalidades dessa área estão no domínio "LGPD e portal" do [catálogo](../funcionalidades/README.md).

## 4. Perfis de uso

O sistema reconhece 15 perfis. Cada pessoa age conforme o perfil do seu vínculo. A descrição de cada perfil e a jornada típica estão em [Perfis e jornadas](perfis-e-jornadas.md).

| Grupo | Perfis |
|---|---|
| Gestão da rede | Secretário de Educação, Administrador da Rede, Diretor de Núcleo |
| Gestão da escola | Diretor Escolar, Vice-diretor escolar, Coordenador |
| Secretaria da escola | Secretário Escolar, Auxiliar de secretaria |
| Sala de aula | Professor, Aplicador, Aluno |
| Autônomo | Professor autônomo |
| Equipe interna do produto | Desenvolvedor, Suporte, Financeiro |

A regra central é esta: **o perfil define o que a pessoa pode fazer, e o escopo define onde.** Uma permissão nunca amplia o escopo.

## 5. Como as peças se ligam

### 5.1 Quem é cliente e onde os dados ficam

Três ideias explicam a organização dos dados:

- **Cliente.** É a organização que contrata o sistema, por exemplo uma rede, uma escola independente ou um professor autônomo.
- **Espaço.** É a área de trabalho de um cliente. O espaço pode ser de rede, de núcleo, de escola ou pessoal. Os dados de um espaço não se misturam com os de outro.
- **Vínculo.** É a ligação de uma pessoa com um espaço, com um perfil e um período. A mesma pessoa pode ter vários vínculos. Ela escolhe o vínculo ativo ao entrar.

A hierarquia pedagógica continua sendo: rede, núcleo, escola, turma, aluno. O catálogo nacional de instituições alimenta o cadastro de escolas com dados do Censo (código INEP).

Funcionalidades relacionadas: FN-021, FN-022, FN-023, FN-015, FN-031.

### 5.2 O ciclo da avaliação

```
Prova + gabarito -> Cartões em PDF -> Aplicação em papel
   -> Foto do cartão -> Leitura OMR -> Revisão humana
   -> Nota e resultado -> Relatórios e painéis
```

- **Provas e gabaritos** ficam em FN-040. A autoria e o "Meu espaço" estão em FN-041. A duplicação está em FN-042.
- **Leitura e correção** ficam em FN-045 (motor OMR e acompanhamento), FN-046 (revisão das leituras) e FN-047 (qualidade, tentativas e documentos verificáveis).
- **Resultados e relatórios** ficam em FN-049. Os painéis ficam em FN-052 a FN-054.

O motor de leitura é um componente trocável. Hoje existe um driver nativo, escrito no próprio sistema. Um driver externo é uma candidata futura (FN-048).

### 5.3 As duas aplicações

O sistema tem duas aplicações Laravel, ambas em PHP:

| Aplicação | Papel em linguagem simples |
|---|---|
| API (`apps/api`) | Guarda os dados, aplica regras e permissões, lê os cartões e devolve apenas dados (JSON). |
| WEB (`apps/web`) | Mostra as telas no navegador e conversa com a API em nome da pessoa. Não acessa o banco de dados. |

O banco de dados MariaDB é usado só pela API. Tarefas pesadas, como ler um cartão, correm em segundo plano numa fila. Os aplicativos para Android e iOS estão adiados (FN-010).

A arquitetura técnica completa está em [Arquitetura](../arquitetura/README.md).

### 5.4 Segurança em uma frase

A API decide quem vê o quê. A WEB só esconde botões para facilitar o uso. Os endereços (URLs) não carregam identificadores de recursos nem credenciais (FN-018). O detalhe está no documento de segurança da [arquitetura](../arquitetura/README.md).

## 6. Módulos principais

O [catálogo](../funcionalidades/README.md) tem 77 funcionalidades em 14 domínios.

| Domínio | O que contém | FN principais |
|---|---|---|
| Plataforma e operação | Base do sistema, backup, filas, publicação, auditoria, integração contínua | FN-001 a FN-010 |
| Identidade e acesso | Login, permissões, acessos internos, vínculos, identidade pessoal, segurança de URLs | FN-011 a FN-020 |
| Clientes, espaços e vínculos | Clientes, espaço pessoal, cadastro, transferências, catálogo de instituições | FN-021 a FN-032 |
| Cadastros escolares | Escolas, turmas, alunos, disciplinas, imagens | FN-033 a FN-039 |
| Provas e gabaritos | Provas, autoria, duplicação, editor e banco de questões | FN-040 a FN-044 |
| Correção e OMR | Leitura de cartões, revisão, qualidade | FN-045 a FN-048 |
| Resultados e relatórios | Resultados, relatório da turma, análises | FN-049 a FN-051 |
| Painéis | Painéis por perfil, painéis de turmas, editor | FN-052 a FN-055 |
| Agenda e notificações | Calendário, notificações, visitas, aplicação de provas | FN-056 a FN-059 |
| Comunicação e e-mail | E-mail transacional e Fale Conosco | FN-060 |
| LGPD e portal | Termos, retenção, cifragem, pedidos dos titulares, portal dos responsáveis | FN-061 a FN-067 |
| Financeiro | Contratos, faturas, selo dos documentos | FN-068, FN-069 |
| Público e marca | Identidade visual, marca oficial, site público | FN-070 a FN-072 |
| Qualidade transversal | Layout responsivo, tabelas, Tailwind, carregamento, desempenho | FN-073 a FN-077 |

## 7. Estado atual

Os números abaixo vêm do [catálogo](../funcionalidades/README.md) em 10/11/2026.

| Implementação | Quantidade |
|---|---|
| Concluída | 20 |
| Em homologação | 9 |
| Parcial | 18 |
| Pendente | 20 |
| Candidata | 6 |
| Adiada | 2 |
| Substituída | 2 |

**O que funciona:** provas e gabaritos, geração de cartões, leitura OMR com revisão, resultados e relatórios, painéis por perfil, motor de permissões, acessos internos, clientes, espaços e vínculos.

**O que ainda não existe:** agenda, notificações, portal dos responsáveis, financeiro, acesso próprio do aluno, importação de alunos por tela e a maior parte da área de dados pessoais (pedidos dos titulares completos, retenção).

Em 10/11/2026, nenhuma funcionalidade está em execução. Os documentos de planejamento estão em rascunho e aguardam a aprovação do responsável. A ordem de trabalho está no [plano mestre](../planejamento/00-plano-mestre.md). As regras de trabalho estão na [governança](../planejamento/01-governanca.md).

## 8. Princípios que orientam o produto

Estas regras vêm da constituição original do projeto e continuam vigentes:

- A API é a única fonte de dados para as interfaces. A WEB nunca acessa o banco diretamente.
- A API devolve apenas JSON. Nenhuma rota da API devolve HTML.
- O banco de dados oficial é o MariaDB.
- Nenhuma funcionalidade nasce sem registro no [catálogo](../funcionalidades/README.md) e sem planejamento aprovado.
- O código do sistema anterior não é reutilizado. Só regras de negócio validadas podem ser consultadas.
- A arquitetura deve comportar a evolução: banco de questões, várias versões de prova, OMR externo e aplicativo móvel offline.

A regra "nenhuma funcionalidade sem mockup" da constituição original foi substituída pelo fluxo de planejamento da [governança](../planejamento/01-governanca.md). Os mockups e o `style-system/` continuam como referência de design.

## 9. Para continuar

| Se você quer… | Leia |
|---|---|
| Ver cada perfil e sua jornada | [Perfis e jornadas](perfis-e-jornadas.md) |
| Entender um termo | [Glossário](../glossario.md) |
| Saber o que uma funcionalidade faz | [Catálogo](../funcionalidades/README.md) |
| Saber o que vem a seguir | [Plano mestre](../planejamento/00-plano-mestre.md) |
| Conhecer a arquitetura | [Arquitetura](../arquitetura/README.md) |
| Entender uma decisão | [Decisões](../decisoes/README.md) |

## Divergências encontradas

Esta tabela compara os documentos antigos com o código da branch atual. O código prevalece.

| Documento antigo | Afirmava | Situação atual no código |
|---|---|---|
| 00-project-rules, 02-visao-geral | Seis perfis oficiais. | Existem 15 perfis: inclui Secretário de Educação, vice-diretor, secretaria escolar, aplicador, professor autônomo e três perfis internos. |
| 05-requisitos-nao-funcionais | Autenticação por JWT com refresh token. | A API usa o Laravel Sanctum (guarda `sanctum`). A WEB usa sessão. |
| 02-visao-geral, 04 (RF-011.2) | GovBar e Padrão Digital de Governo em todas as telas. | A interface usa identidade visual própria (FN-070, FN-071) e Tailwind (FN-075). As views não contêm GovBar. |
| 04 (RF-001.3) | Integração com o gov.br. | O código não integra com o gov.br. |
| 02-visao-geral | Integração SEGES no escopo do MVP. | Existe o modelo `SincronizacaoSeges`, mas nenhuma funcionalidade do catálogo entrega a sincronização. |
| 00-project-rules | Apps `apps/android` e `apps/ios`. | Só existem `apps/api` e `apps/web`. Os aplicativos móveis estão adiados (FN-010). |
| 02-visao-geral | Hierarquia rede, núcleo, escola. | Além dela, o sistema tem clientes, espaços (inclusive o pessoal) e vínculos por pessoa. |
| 00-project-rules | Só os mockups definem as telas. | O fluxo vigente é o planejamento por funcionalidade, com revisão de interface. |
