# TU1-R3 — painel analítico da turma e arquivamento

Replanejamento de 06/11/2026, após a avaliação do responsável sobre TU1-R2. Esta revisão tem prioridade sobre AP1-L. Publicação somente na homologação ao fechar a etapa; produção e gabaritos existentes devem ser preservados.

## Direção previamente revisada

Consulta UI/UX e Product Design registrada em `operacao-20261102/tu1r3-direcao-design.md`, fora do checkout. As seis referências fornecidas orientam hierarquia e densidade; cores, tipografia e componentes continuam sendo os do Gabarito360. A aprovação prévia não substitui a conferência visual final.

1. Cabeçalho compacto: editar, arquivar, imprimir e gerar PDF; impressão/PDF apenas por ícones identificados por legenda acessível. Filtro também por ícone, sem card de instruções.
2. Mostrar recorte aplicado somente quando existir filtro efetivo, em texto discreto e itálico sob a identificação da turma. Cancelar o modal mantém o recorte anterior.
3. Seis KPIs: avaliações aplicadas, resultados concluídos, alunos com resultado, acerto médio, mediana e faixa observada. Informar a unidade/base; registros de avaliações diferentes não são alunos distintos nem diagnóstico de aprendizagem.
4. Quatro gráficos Apache ECharts: distribuição por faixas de acerto, média/mediana por avaliação, situações registradas e acerto por questão de uma avaliação selecionada. Não inventar frequência, habilidades, tendências ou notas específicas por disciplina quando a prova é multidisciplinar.
5. Gráficos responsivos e acessíveis, com cores semânticas do produto, JavaScript externo, biblioteca local com versão fixada, estados sem dados/erro, contraste e respeito a movimento reduzido. Não inicializar gráficos com dimensão zero em abas ocultas.
6. Imprimir/PDF abrem diálogo de orientação explícita; retrato e paisagem precisam funcionar nos dois modos. Preservar os filtros, assinatura, identidade institucional e paginação. Não depender do botão submitter para enviar o modo ao fluxo UR4.
7. Substituir desativação por arquivamento explícito e reversível de turma/cadastro de aluno no código, rotas Web, formulários e mensagens. Não representar encerramento temporal de matrícula. Manter o contrato legado da API por compatibilidade, sem toggle cego na nova interface.

## Subdivisões internas

| Parte | Entrega | Aceite |
|---|---|---|
| R3-A | Auditoria, referências e definição pedagógica | Direção prévia revisada e métricas sustentadas pelos dados |
| R3-B | DTO analítico aditivo e consultas | Escopo da turma/perfil; sem duplicação N:N; itens anulados/língua/sem registro explícitos |
| R3-C | ECharts, KPIs e filtro discreto | Desktop/celular; um exame sem curva falsa; quatro análises úteis |
| R3-D | Ações de relatório e modal | Orientação e modo transmitidos corretamente; documentos coerentes com o recorte |
| R3-E | Arquivamento de turma e cadastro | Estado explícito/esperado, lock, auditoria, autorização de todos os escritores e preservação histórica |
| R3-F | Revisões, testes, CI, integração e homologação | Parecer visual e segurança independentes; backup/conferência dos dados e smoke real |

## Regras de dados e segurança

- Acrescentar campos à resposta de análise preservando os campos e a comunicação existentes. IDs internos da API não voltam às URLs de acesso Web.
- O conjunto esperado de alunos da aplicação não possui snapshot confiável. Matrícula atual não será chamada de participação/frequência histórica.
- A distribuição usa resultados válidos vigentes, não tentativas arquivadas; o total e as exclusões devem ser identificados.
- Questões pertencem a uma única avaliação explicitamente autorizada. Não usar estatística de outras turmas para inferir a desta turma.
- O arquivamento precisa definir leitura histórica sem ampliar a autoridade do professor. Todas as entradas capazes de mudar o estado — inclusive cadastro e edição — devem respeitar a permissão correspondente e conferir o estado sob lock.
- Não executar arquivamento/transferência em dados reais apenas para obter screenshots. Verificar transações e concorrência em bases descartáveis.

O fechamento exige conferir documentos a partir do DTO real e dos botões publicados, incluindo ausência de filtros. CI e mocks isoladamente não comprovam essa integração.

## Conferência do candidato

Retomada autorizada em07/11/2026. Implementação mantém os campos anteriores da API e acrescenta os agregados analíticos. Apache ECharts6.1.0 é distribuído localmente, em bundle com módulos utilizados; o CI reconstrói o artefato.

- Análise: testes SQLite e MariaDB, mediana com janela SQL, ausência de dados, contagem N:N, idiomas, anuladas e restrições por turma/perfil.
- Arquivamento: recibo transacional imutável, chave idempotente, versão monotônica, autorização sob lock, dois processos concorrentes, preservação histórica e bloqueio de novas associações a cadastros arquivados. As rotas toggle e as chaves legadas de permissão permanecem somente por compatibilidade.
- Web: filtros inválidos preservam o recorte anterior; análise de uma avaliação não muda o agregado global; modos de documento e orientação são campos explícitos. Secretaria passa pela mesma permissão da API; respostas incoerentes não confirmam sucesso.
- PDF: seis testes e emissão PHP→Python real nas duas orientações; identidade, emissor, QR e paginação em todas as páginas; notas de acompanhamento e introduções mantidas com a seção correspondente.
- Revisões cruzadas de segurança e Product Design registradas na operação local. O aceite visual exige as evidências atuais de desktop/celular e os documentos regenerados; a limitação do controle nativo do Chrome não autoriza afirmar testes que não ocorreram.

A publicação será exclusivamente na homologação, após CI/merge. O operador fixa host, banco, release de origem e manifesto Git; rejeita caches/ambiente no pacote, valida backup e hashes dos dados antes da única migração aditiva, e compara todos os dados e arquivos anteriores antes de reabrir o tráfego. Nenhuma exclusão/backfill/alteração de produção é autorizada nesta entrega.

## Ajustes solicitados durante a conferência

Em07/11 o responsável aprovou a composição e solicitou cores semânticas, texto em Editar/Arquivar turma e modal próprio, sem confirmação nativa. O quarto quadro passa a acompanhar alunos: atenção por indicação de recuperação registrada, dentro do critério somente quando todos os resultados estão aprovados, e Top5 dos maiores acertos médios válidos. A análise por questão continua disponível nos resultados da prova; seus campos anteriores na API são preservados. Nomes/resultados dependem das permissões correspondentes e do mesmo escopo já autorizado.

Empates conservam posição e o corte do Top5 informa outros empatados; atenção permite expandir a lista. O documento atual preserva geração e orientações. O refinamento de diagramação e sua adequação ao novo acompanhamento nominal serão tratados após a aprovação final das telas, conforme orientação posterior do responsável.

A suíte MariaDB identificou transações artificiais de RefreshDatabase incompatíveis com a garantia de transação própria do serviço. Seis suítes passam a BancoCurriculoDescartavel, já utilizado nos testes de concorrência: mesmas assertivas, commits reais, sem bypass nem afrouxamento de proteção no código de produção.

TU-IMG é uma entrega posterior:36brasões originais em pixel art para turmas, foto/câmera/recorte e catálogo adulto de educadores para usuários. Avatares de alunos permanecem separados; os professores utilizam a imagem da própria conta. Essa entrega não integra o pacote de publicação TU1-R3.
