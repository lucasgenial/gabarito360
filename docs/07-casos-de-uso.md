# 07 — Casos de Uso

---

## UC-001 — Autenticar no Sistema

**Ator:** Todos os perfis  
**Pré-condição:** Usuário cadastrado e ativo no sistema  
**Pós-condição:** Usuário autenticado redirecionado ao seu dashboard

**Fluxo Principal:**
1. Usuário acessa a tela de login
2. Informa e-mail institucional e senha
3. Opcionalmente marca "Manter conectado"
4. Clica em "Entrar no painel"
5. Sistema valida credenciais
6. Sistema identifica o perfil do usuário
7. Sistema redireciona para o dashboard correspondente ao perfil

**Fluxos Alternativos:**
- 5a. E-mail inválido → exibe mensagem de erro "Informe um e-mail válido"
- 5b. Senha < 6 caracteres → exibe "A senha deve ter ao menos 6 caracteres"
- 5c. Credenciais incorretas → exibe mensagem de erro de autenticação

**Tela:** login.html

---

## UC-002 — Cadastrar Escola

**Ator:** Administrador da Rede  
**Pré-condição:** Usuário autenticado como ADMIN_REDE  
**Pós-condição:** Nova escola cadastrada e visível na listagem

**Fluxo Principal:**
1. Usuário acessa Escolas → clica em "Nova escola"
2. Sistema abre modal de cadastro
3. Usuário preenche: Nome (obrigatório), INEP, Tipo de rede
4. Usuário preenche endereço: Logradouro, Cidade, UF
5. Usuário preenche contato: Telefone, E-mail, Diretor(a)
6. Usuário define status (Escola ativa marcada por padrão)
7. Clica em "Salvar escola"
8. Sistema valida e salva
9. Sistema exibe toast "Escola salva com sucesso!"

**Fluxos Alternativos:**
- 8a. Nome vazio → foco no campo nome + toast de aviso

**Tela:** escolas.html

---

## UC-003 — Criar Prova e Gabarito

**Ator:** Professor, Coordenador  
**Pré-condição:** Usuário autenticado com permissão de criação de provas  
**Pós-condição:** Prova com gabarito publicado disponível para uso no OMR

**Fluxo Principal:**

**Passo 1 — Dados:**
1. Usuário clica em "Nova prova" (em provas.html ou dashboard)
2. Preenche: Título, Disciplina, Série/Ano, Nº de questões
3. Avança para Passo 2

**Passo 2 — Gabarito Oficial:**
4. Sistema exibe grid de bolhas (N questões × 5 alternativas)
5. Usuário clica em cada bolha para marcar a alternativa correta
6. Contador e barra de progresso atualizam em tempo real
7. Opcionalmente expande "Padrões desta prova" para configurar:
   - Alternativas por questão (3/4/5)
   - Nota máxima
   - Tipo de pontuação
   - Escola
   - Checkboxes de comportamento
8. Usuário pode "Salvar rascunho" (status: Rascunho)
9. Com gabarito 100% preenchido, clica "Publicar gabarito" (status: Publicada)

**Passo 3 — Cartão & Turmas:** (não detalhado no MVP mockado)

**Fluxos Alternativos:**
- 8a. Rascunho salvo com gabarito parcial → status permanece "Rascunho"
- 9a. Gabarito incompleto → botão "Publicar gabarito" desabilitado ou exibe aviso

**Telas:** criar-prova.html → gabarito.html

---

## UC-004 — Acompanhar Correção de Prova

**Ator:** Professor, Coordenador  
**Pré-condição:** Prova com status "Em correção", cartões sendo processados  
**Pós-condição:** Todos os cartões lidos, prova com status "Corrigida"

**Fluxo Principal:**
1. Usuário acessa acompanhar-correcao.html (via provas.html ou dashboard)
2. Sistema exibe: cartões lidos, pendentes, ambíguos, total
3. Sistema exibe gráfico donut e barra de progresso
4. Usuário clica "Atualizar leitura" para puxar novos cartões processados
5. Sistema atualiza contadores e feed de atividade
6. Repete até todos os cartões estarem lidos

**Sub-fluxo — Resolução de Ambíguos:**
1. Lista de cartões ambíguos exibida à direita
2. Cada cartão mostra: ID, questão problemática, opções possíveis
3. Usuário clica na alternativa correta
4. Sistema move o cartão de ambíguo → lido
5. Contadores atualizados em tempo real

**Estado Final:**
- Quando pendentes = 0: botão desativado, texto "Leitura concluída"
- Status da prova atualizado para "Corrigida"

**Tela:** acompanhar-correcao.html

---

## UC-005 — Visualizar Resultado Individual do Aluno

**Ator:** Professor, Coordenador, Diretor, Aluno (apenas seus dados)  
**Pré-condição:** Prova com status "Corrigida"  
**Pós-condição:** Resultado visualizado (sem alteração de estado)

**Fluxo Principal:**
1. Usuário acessa resultado.html via:
   - relatorio-prova.html (botão "Ver prova")
   - aluno-detalhe.html (botão "Ver Resultado")
   - turma-detalhe-2.html
2. Sistema exibe:
   - Gráfico donut com % de acerto
   - Nota final, acertos, total de questões
   - Badge: Aprovado ou Recuperação
   - Comparativo com média da turma
   - Gráfico de acertos por tema
   - Folha de respostas corrigida (verde/vermelho/amarelo)
3. Usuário pode:
   - Exportar PDF
   - Clicar "Revisar leitura" para corrigir uma leitura incorreta

**Tela:** resultado.html

---

## UC-006 — Consultar Relatório de Prova

**Ator:** Professor, Coordenador, Diretor  
**Pré-condição:** Prova com status "Corrigida"  
**Pós-condição:** Relatório visualizado

**Fluxo Principal:**
1. Usuário acessa relatorio-prova.html (via provas.html → "Ver resultados")
2. Sistema exibe:
   - KPIs: média, aprovação %, cartões corrigidos, pendências
   - Gráfico de acertos por tema
   - Donut de aproveitamento médio com meta da rede
   - Tabela de resultados por aluno com status
3. Usuário pode clicar em um aluno para ver o resultado individual

**Tela:** relatorio-prova.html

---

## UC-007 — Monitorar Desempenho (Dashboard por Perfil)

**Ator:** Qualquer perfil autenticado  
**Pré-condição:** Usuário autenticado  
**Pós-condição:** Dashboard exibido com dados atualizados do escopo do usuário

**Fluxo Principal:**
1. Usuário acessa o sistema (após login ou clicando em "Painel")
2. Sistema identifica o perfil
3. Sistema exibe o dashboard correspondente com dados do escopo do usuário

**Dashboards por Perfil:**
- ADMIN_REDE → dashboard-admin.html
- DIR_NUCLEO → dashboard-diretor-nucleo.html
- DIR_ESCOLAR → dashboard-diretor-escolar.html
- COORDENADOR → dashboard-coordenador.html
- PROFESSOR → dashboard-professor.html
- ALUNO → dashboard-aluno.html

---

## UC-008 — Gerenciar Equipe da Escola

**Ator:** Diretor Escolar, Administrador  
**Pré-condição:** Usuário com permissão de gestão de equipe  
**Pós-condição:** Membros cadastrados/editados com acesso ao sistema

**Fluxo Principal:**
1. Usuário acessa perfis-equipe.html
2. Visualiza lista de membros com perfil e status
3. Clica "Cadastrar membro" → abre membro-cadastrar.html
4. Preenche dados do membro (nome, e-mail, CPF, perfil)
5. Salva → membro recebe acesso ao sistema

**Fluxos Alternativos:**
- Editar membro existente → membro-editar.html
- Desativar membro → remove acesso

**Telas:** perfis-equipe.html, membro-cadastrar.html, membro-editar.html

---

## UC-009 — Agendar Visita Pedagógica

**Ator:** Diretor de Núcleo  
**Pré-condição:** Usuário autenticado como DIR_NUCLEO  
**Pós-condição:** Visita registrada e visível no dashboard do núcleo

**Fluxo Principal:**
1. Usuário no dashboard-diretor-nucleo.html clica "Agendar Visita"
2. Seleciona escola de destino
3. Define data, tipo de visita e urgência
4. Salva visita
5. Visita aparece na lista "Próximas agendas em campo"

**Tela:** dashboard-diretor-nucleo.html

---

## UC-010 — Exportar Relatório / Documento PDF

**Ator:** Professor, Coordenador, Diretor, Aluno  
**Pré-condição:** Documento disponível (gabarito, resultado, relatório, ficha do aluno)  
**Pós-condição:** PDF gerado e disponível para download/impressão

**Fluxo Principal:**
1. Usuário clica no botão de exportação (ex.: "Exportar PDF", "Ficha do Aluno (PDF)")
2. Sistema gera PDF via window.print() ou endpoint da API
3. PDF disponível para impressão ou download

**Documentos exportáveis (identificados nos mockups):**
- Gabarito oficial (gabarito.html)
- Resultado individual (resultado.html)
- Ficha do aluno (aluno-detalhe.html)
- Visão executiva da rede (dashboard-admin.html)
- Relatório da escola (dashboard-diretor-escolar.html)

---

## UC-011 — Editar Perfil do Usuário

**Ator:** Todos os perfis  
**Pré-condição:** Usuário autenticado  
**Pós-condição:** Dados do perfil atualizados

**Fluxo Principal:**
1. Usuário clica em seu avatar no header → seleciona "Meu Perfil"
2. Sistema abre perfil.html com dados atuais
3. Usuário edita campos disponíveis
4. Salva alterações

**Tela:** perfil.html

---

## UC-012 — Configurar Sistema

**Ator:** Administrador da Rede, Diretor Escolar (configurações limitadas)  
**Pré-condição:** Usuário com permissão de configuração  
**Pós-condição:** Configurações salvas e aplicadas

**Fluxo Principal:**
1. Usuário clica em seu avatar → seleciona "Configurações"
2. Sistema abre configuracoes.html
3. Usuário ajusta parâmetros disponíveis para seu perfil
4. Salva configurações

**Tela:** configuracoes.html
