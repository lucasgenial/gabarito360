# DC1 — vínculos temporais entre áreas e disciplinas

04/11/2026. Revisão anterior à implementação, substituindo a seleção em lote do documento147. Direção prévia aprovada pelo agente UI/UX e Product Design após a solicitação do responsável.

O cadastro de nome, sigla e situação da área fica separado da vinculação. Depois de salvar a área, selecionar uma disciplina e usar Vincular no detalhe. Abaixo, manter tabela inclusive no celular, com exatamente duas colunas: **Nome/dados** e **Ações**. O nome aparece em destaque; abaixo, data/hora de vinculação e, quando houver, de desvinculação, no fuso de São Paulo. Vínculo ativo oferece Desvincular; encerrado permanece sem ação. Não transformar as linhas em cards nem confundir vínculo encerrado com disciplina inativa.

Cada vinculação abre um período. Desvincular encerra o período sem excluir o histórico; revincular abre outro. Vínculos anteriores sem data conhecida mostram Não registrada, sem inventar a data original. Manter a relação corrente como índice, com histórico próprio e auditoria. Classificação não concede acesso, altera lotação ou remove provas, notas e gabaritos.

Quando existir uso conjunto registrado de área e disciplina em prova, pedir confirmação antes do encerramento. O servidor confere novamente uso, versão, ator e contexto ao confirmar. Uso novo entre tentativa e confirmação exige nova conferência. As entradas antigas em lote também devem passar pela mesma proteção; não podem contornar a confirmação. Integração das provas deverá registrar a classificação utilizada no momento, preservando-a após encerramento do período. Não inferir uso por nomes ou pela existência isolada de uma área/disciplina.

| Parte | Situação antes do código |
|---|---|
| Direção UI/UX e Product Design | Aprovada previamente |
| Tabela de duas colunas e formulário Vincular | Em implementação |
| Períodos, datas, situação e auditoria | Em implementação |
| Confirmação condicional e proteção contra concorrência | Em implementação |
| Integração curricular das provas e quatro simulados | Pendente, parte do fechamento DC1 |
| Testes SQLite/MariaDB e revisão visual final | Pendentes |
| Publicação | Apenas homologação ao fechar DC1 completo; sem produção |

Validar período ativo/encerrado/revinculado, ausência de data antiga, inativação do catálogo, professor sem gestão, escopo por instituição, clique repetido, erro/conflito, uso conjunto e preservação dos registros anteriores. Conferir desktop,390px,320px, teclado e alternativa sem JavaScript.

Refinamentos solicitados durante a implementação: a tabela deve compartilhar o padding interno do card principal com título, seletor e Vincular; não ficar full-bleed nem parecer outro card. Avisos do componente compartilhado `x-aviso` precisam de separação antes e depois: `margin-block:16px`, com margens zeradas somente nos containers explícitos que já usam gap. Não alterar cores, bordas, conteúdo nem roles dos avisos. Ambas as direções receberam parecer prévio favorável do especialista.

Validação inicial:21 testes API/270 asserções em SQLite e MariaDB;21 testes web/243 asserções. Chrome com API real e dados exclusivamente fictícios confirmou encerramento direto sem uso, novo período na revinculação, confirmação condicional com uso, Cancelar em foco, Escape com retorno ao botão e preservação de sete tabelas existentes por hash após confirmação. A integração das provas reais com o registro do uso conjunto ainda faz parte do fechamento DC1; a preparação desse contrato não equivale a concluir tal integração. Refinamentos visuais finais, revisão independente e CI do novo head ainda pendentes.
