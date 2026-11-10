# Perfis e jornadas

**Atualizado em:** 10/11/2026, por lucasgenial

Este documento descreve cada perfil do Gabarito360, o que ele faz e a jornada típica de uso. A matriz completa de permissões pertence à [arquitetura](../arquitetura/README.md) (acesso e permissões).

## 1. Como o acesso funciona

Quatro regras valem para todos os perfis:

1. A pessoa entra com e-mail e senha. Depois escolhe o vínculo ativo, se tiver mais de um (FN-011, FN-015).
2. O **perfil** do vínculo define as ações permitidas. Cada ação é uma permissão com chave, por exemplo `prova.ver`.
3. O **escopo** do vínculo define onde as ações valem: rede, núcleo, escola ou turmas próprias. Permissão nunca amplia o escopo.
4. O gestor pode conceder uma permissão extra, com data de validade, ou retirar uma permissão padrão. Ele só concede o que ele mesmo tem (FN-012).

O menu mostra um item só se a pessoa tem a permissão exigida pela tela de destino. Por isso nenhum item de menu leva a erro 403. A API sempre confere a permissão e o escopo, mesmo que a tela esconda o botão.

## 2. Quadro dos perfis

| Perfil | Escopo | Painel inicial | Situação |
|---|---|---|---|
| Secretário de Educação | Rede (leitura gerencial) | Secretaria | Painel existe |
| Administrador da Rede | Toda a rede | Administrador | Concluído |
| Diretor de Núcleo | Escolas do núcleo | Diretor de núcleo | Concluído |
| Diretor Escolar | Uma escola | Diretor escolar | Concluído |
| Vice-diretor escolar | Uma escola | Diretor escolar | Concluído (FN-014) |
| Coordenador | Uma escola | Coordenador | Concluído |
| Secretário Escolar | Uma escola (área administrativa) | Administrativo | Concluído (FN-014) |
| Auxiliar de secretaria | Uma escola (área administrativa) | Administrativo | Concluído (FN-014) |
| Professor | Turmas próprias | Professor | Concluído |
| Professor autônomo | Espaço pessoal | Professor | Parcial (FN-022, FN-027) |
| Aplicador | Escola, no dia da prova | Aplicador | Concluído |
| Aluno | Próprios resultados | Aluno | Parcial (FN-020 pendente) |
| Desenvolvedor | Clientes, acessos internos | Interno | Concluído (FN-013) |
| Suporte | Clientes | Interno | Concluído (FN-013) |
| Financeiro | Clientes | Interno | Concluído (FN-013) |

Os painéis por perfil estão em FN-052. Os painéis de turmas estão em FN-053.

## 3. Quem cadastra quem

Cada perfil cria apenas os perfis abaixo dele. A regra vive na API (`Cadastro`).

| Quem cadastra | Pode cadastrar |
|---|---|
| Administrador da Rede | Todos os perfis escolares, de núcleo e de rede |
| Diretor de Núcleo | Diretor escolar, vice-diretor, secretário escolar, auxiliar de secretaria, coordenador |
| Diretor Escolar | Vice-diretor, secretário escolar, auxiliar, coordenador, professor, aplicador, aluno |
| Vice-diretor escolar | Secretário escolar, auxiliar, coordenador, professor, aplicador, aluno |
| Coordenador | Professor, aplicador, aluno |

Os acessos internos (Desenvolvedor, Suporte, Financeiro) seguem outro caminho. O Desenvolvedor concede esses acessos na tela de acessos internos (FN-013).

A incompatibilidade entre grupos funcionais é uma regra de vínculo. Uma pessoa não acumula, ao mesmo tempo e na mesma instituição, um perfil pedagógico e um administrativo. O Suporte pode registrar uma exceção auditada (FN-014).

## 4. Jornadas por perfil

### 4.1 Administrador da Rede

Função: gerir a rede inteira.

1. Entre e abra o painel da rede: escolas, alunos, provas e média geral.
2. Abra **Escolas** e cadastre uma escola. Informe o código INEP e o sistema preenche os dados do Censo.
3. Abra **Equipe** e cadastre diretores e coordenadores.
4. Ajuste as permissões de um membro, quando precisar.
5. Abra **Relatórios** e veja a rede, um núcleo ou uma escola.

Funcionalidades: FN-033, FN-012, FN-049.

### 4.2 Secretário de Educação

Função: acompanhar a rede sem operar o dia a dia.

1. Entre e abra o painel da secretaria.
2. Consulte os relatórios da rede.
3. Consulte a lista de escolas.

### 4.3 Diretor de Núcleo

Função: supervisionar as escolas do núcleo.

1. Abra o painel: escolas do núcleo, comparativo e média do núcleo.
2. Abra **Escolas** e cadastre uma escola ou um anexo de escola do núcleo.
3. Tente abrir uma escola de outro núcleo. O sistema responde "não encontrada".
4. Abra **Relatórios** para ver o comparativo entre as escolas.
5. Abra **Equipe** e cadastre diretores escolares e coordenadores.

### 4.4 Diretor Escolar e Vice-diretor

Função: gerir uma escola.

1. Abra o painel da escola: turmas, equipe e provas.
2. Abra **Turmas** e **Alunos** para manter o cadastro.
3. Abra **Equipe** e cadastre professores, coordenadores, aplicadores e alunos.
4. Conceda a um professor, por prazo, a permissão "Relatório da escola".
5. Abra **Relatórios** e gere o relatório da escola, em A4, para imprimir ou salvar em PDF.
6. Edite os dados da escola. O INEP e o núcleo aparecem bloqueados.

### 4.5 Coordenador

Função: conduzir a parte pedagógica.

1. Abra o painel: provas em andamento, cartões pendentes e alunos em atenção.
2. Crie uma prova, cadastre o gabarito e publique.
3. Abra **Corrigir**, acompanhe a leitura e feche a correção. O coordenador também reabre uma correção fechada.
4. Cadastre professores, aplicadores e alunos.
5. Abra **Turmas** para ver o desempenho.

### 4.6 Secretário Escolar e Auxiliar de secretaria

Função: manter o cadastro administrativo da escola.

1. Entre e abra o painel administrativo.
2. Abra **Alunos** e **Turmas** para manter o cadastro.
3. Não tenha acesso às ações pedagógicas, como provas e correção. Essas ações pertencem ao grupo pedagógico.

### 4.7 Professor

Função: aplicar e corrigir as próprias provas.

1. Abra o painel: minhas provas, cartões a corrigir e minhas turmas.
2. Abra **Turmas**. A lista mostra só as turmas vinculadas ao professor.
3. Abra **Provas**, cadastre o gabarito oficial e publique.
4. Gere os cartões em PDF, por turma, e imprima.
5. Aplique a prova em papel.
6. Abra **Corrigir**, escolha a prova e fotografe cada cartão. O aluno corrigido aparece na lista.
7. Revise as leituras duvidosas.
8. Abra o resultado do aluno e o relatório da prova.
9. Com a correção fechada, o professor não envia nem exclui cartões e não reabre a correção.

Funcionalidades: FN-040, FN-045, FN-046, FN-049.

### 4.8 Professor autônomo

Função: trabalhar sem escola formal.

1. Entre no espaço pessoal.
2. Cadastre as próprias turmas, alunos e provas.
3. Corrija e gere relatórios no mesmo ciclo do professor.

O espaço pessoal depende de uma chave de configuração. A funcionalidade está parcial (FN-022, FN-027). Os dados de um espaço pessoal não se misturam com os de uma escola.

### 4.9 Aplicador

Função: fotografar os cartões no dia da prova.

1. Abra o painel: a prova do dia.
2. Abra o modo câmera e envie os cartões.
3. O aplicador não vê notas, gabaritos nem relatórios.

### 4.10 Aluno

Função: ver os próprios resultados.

1. Entre e abra o painel do aluno: notas e próximas provas.
2. Consulte os resultados das próprias provas.

**Atenção:** a ligação entre a conta do usuário e o registro do aluno ainda não existe. Sem ela, o aluno não vê dados. O acesso próprio do aluno está pendente (FN-020). A decisão sobre ele depende do portal dos responsáveis (FN-067).

### 4.11 Desenvolvedor, Suporte e Financeiro

Função: atender os clientes do produto.

| Perfil | Pode |
|---|---|
| Desenvolvedor | Ver e gerir clientes, gerir acessos internos, gerir exceções funcionais |
| Suporte | Ver e gerir clientes, gerir exceções funcionais |
| Financeiro | Ver e gerir clientes |

Os três perfis operam em um contexto interno, separado dos contextos de escola. O campo de perfil gravado no cadastro legado não concede acesso interno. O acesso nasce de uma concessão explícita (FN-013).

Planejadas e ainda pendentes: o "ver como" com atendimento auditado (FN-005) e o painel técnico (FN-006).

## 5. Telas por perfil

A tabela mostra quais itens de menu cada perfil costuma ver. A permissão efetiva decide o item real.

| Item de menu | Permissão que libera |
|---|---|
| Painel | Todos os perfis |
| Provas | `prova.ver` e `gabarito.ver` |
| Corrigir | `correcao.acompanhar` |
| Turmas | `turma.ver` |
| Alunos | `aluno.gerir`, `aluno.importar` ou `aluno.desativar` |
| Escolas | `escola.criar`, `escola.editar`, `escola.logo` ou `nucleo.ver` |
| Equipe | `usuario.ver` |
| Relatórios | `relatorio.rede`, `relatorio.nucleo` ou `relatorio.escola` |
| Cópias | `copia.autorizar_origem` ou `copia.autorizar_destino` |
| Pedidos de titulares | `pedido.atender` |
| Clientes | `cliente.ver` |
| Acessos internos | `acesso_interno.gerir` |

No celular, a navegação fica numa barra inferior. Os demais itens ficam em **Mais**. O layout responsivo é a funcionalidade FN-073.

## 6. Como conferir um perfil

Use este roteiro para conferir o acesso de um perfil, no computador e no celular:

1. Entre com um usuário do perfil, em ambiente de teste.
2. Confira o menu: ele mostra só os itens permitidos.
3. Abra cada item e confirme que nenhuma tela dá erro de permissão.
4. Tente abrir, por endereço, um recurso de fora do escopo. O sistema deve recusar.
5. Repita o roteiro em largura de celular.

Os testes automáticos da API cobrem as principais ações em cada perfil: permitido, negado, escopo, concessão extra, retirada, extra vencida e correção fechada. Eles ficam em `apps/api/tests/Feature/PerfisTest.php`.

## Divergências encontradas

| Documento antigo | Afirmava | Situação atual no código |
|---|---|---|
| 19-plano-telas-por-perfil | O menu é definido em cada tela e o professor vê "Escolas". | O menu é central e depende das permissões efetivas (`Acesso` na WEB). Superado pelo motor de permissões (FN-012). |
| 19-plano-telas-por-perfil | Botões do painel do professor apontam para `#`. Vincular professor à turma falha por SQL. | Item de diagnóstico de 29/10/2026. Não confirmado no código atual. Tratar como histórico. |
| 19-plano-telas-por-perfil | A tela de correção por turma não existe. | O repositório tem a rota de acompanhamento da turma e o painel de turma. A cobertura plena está em FN-053 (parcial). |
| 19-plano-telas-por-perfil | A decisão 6.1 propõe que o professor veja todas as provas das suas turmas. | A decisão final ficou com o motor de permissões e os vínculos docentes compostos (FN-012, FN-038). Consulte o planejamento dessas funcionalidades. |
| 19-plano-telas-por-perfil | O aplicador tem só o painel do dia. | Confirmado. O aplicador recebe permissão apenas de envio de cartões. |
| 22-roteiro-de-uso-por-perfil | Roteiro com seis usuários de teste em produção. | Os usuários de teste pertencem aos seeders e ao ambiente. Não os registre em documentação. O roteiro da seção 6 os substitui. |
| 22-roteiro-de-uso-por-perfil | 32 testes e 158 verificações passando em 30/10/2026. | Número histórico. O conjunto de testes cresceu desde então. Consulte a integração contínua (FN-008). |
