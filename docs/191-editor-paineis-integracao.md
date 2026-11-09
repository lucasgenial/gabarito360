# Editor dos painéis — integração aprovada

**Decisão de09/11/2026:** responsável aprovou o editor e suas funcionalidades, integração real em Turmas/Turma, validação, CI/merge e publicação somente na homologação. Produção permanece fora do escopo.

## Planejamento e arquitetura

Fluxo obrigatório: navegador → App Web/BFF → API → banco. A Web não lê nem grava as tabelas de preferências. Modelo visual, quadro de distribuição, grade e lifecycle aprovados serão compartilhados com a prévia; conteúdo operacional reutiliza os nós Blade autorizados, sem clonar dados, gráficos ou formulários. Nenhum seletor fictício, dado demonstrativo novo ou localStorage de layout será transplantado.

| Etapa | Escopo | Situação da candidata local |
|---|---|---|
| EI1 | Contrato opt-in, persistência aditiva, projeção e testes de isolamento | Implementado; revisão preliminar favorável e negativos no CI |
| EI2 | Modelo compartilhado, integração dos nós reais, duas abas e leitura mobile | Implementado; Chrome local exercitado com Blade e fixtures reais |
| EI3 | Testes API/Web/JS e revisão independente funcional/segurança/UI | Suítes locais passaram; CI/MariaDB e pareceres finais pendentes |
| EI4 | PR, CI e merge da entrega completa | Pendente |
| EI5 | Backup, migration aditiva e publicação somente na homologação | Pendente |
| EI6 | Conferência Chrome, API e preservação de dados após publicação | Pendente |

**Contrato:** extensão opcional no endpoint de preferências existente, negociada explicitamente pelo cliente do editor. Cliente antigo continua recebendo sua representação anterior e não apaga a composição nova ao salvar geometria/apresentação legadas. Não achatar árvore em layouts antigos nem fundir geometrias ambíguas. Mesma transação, versão e chave de idempotência; eventos anteriores permanecem imutáveis. Coluna nullable para composição, sem backfill ou reescrita de eventos. Reversão de código preserva a coluna e os registros.

**Autorização:** contexto, perfil e recurso resolvidos pela API. Catálogo técnico e padrão vêm do servidor. Payload não aceita usuário, vínculo ou caminhos/classes/HTML. Rejeitar catálogo obsoleto, IDs estranhos/duplicados, ciclos, profundidade, limites, sobreposições e filhos cortados. Composição armazenada pode preservar preferências dormentes, mas a projeção não expõe widgets/títulos revogados. Não alterar notas, cartões, alunos, turmas ou arquivos ao salvar uma disposição.

**Desenho aprovado:** distribuição em colunas hierárquicas; ajustes finos em grades mistas de raiz e seções; três painéis configurados por padrão; criação/remoção/nomes pessoais, avulsos, visibilidade e pin; remover contêiner promove filhos. KPIs fixos. Edição apenas desktop elegível; mobile lê a mesma composição salva ou padrão sem controles nem gravações. Viewport menor suspende gesto preservando rascunho. Salvar/Cancelar/Restaurar padrão e conflito sem replay automático.

**Correção adicional humana:** até767CSSpx o rodapé da versão é ocultado, com reserva de espaço reduzida em44px; navegação inferior e área segura permanecem. Desktop/tablets mantêm o rodapé. Consulta prévia independente e capturas pós-ajuste registradas no diretório operacional.

**Composição e cache:** bootstrap completo e validado na requisição que já entrega a lista/turma. Nenhum GET extra de preferência na abertura normal; árvore somente em memória no navegador, sem cookies/localStorage/sessionStorage. API mantém versão/títulos da leitura indexada atual; cache derivado remove títulos e usa contexto+versão+catálogo+schema+projector+configuração em chave imutável. Não usar cache do banco para acrescentar consultas. ETag reautoriza antes do304; BFF UR4 transforma304 solicitado/correlacionado em200 JSON explícito. Focus/visibilidade revalidam de forma coalescida; BroadcastChannel transporta só invalidação. Conflito preserva rascunho, sem repetir gravação. Contexto/pagehide/desmontagem invalidam callbacks inclusive após parse assíncrono; bfcache exige recarga autenticada.

**Privacidade:** revisar transparência e inventário de cookies/armazenamentos do App e página pública na homologação, sem declarar conformidade LGPD integral por um banner. Preferências de composição não armazenam senhas, tokens, dados de aluno nem conteúdoHTML. Cookies necessários da sessão são independentes desse cache.

**Sequência de desempenho aprovada pelo responsável:** aplicar agora os critérios essenciais ao editor; concluir EI1–EI6 e publicar/validar somente na homologação; depois executar PERF-01 sobre essa release estável, medindo browser, Web/BFF, API e SQL separadamente. Só então priorizar otimizações com os resultados medidos. Relatório e checklist de entrada/aceite para futuras funcionalidades: `operacao-20261102/relatorio-desempenho-homologacao-20261109.md`. PERF-01..15 e FUND-01..10 são propostas identificadas; não apresentá-las como melhorias já entregues ou SLA atingido. Produção permanece excluída.

Pré-consultas independentes: `operacao-20261102/editor-paineis-integracao-design-previo.md` e `paineis-editor-integracao-arquitetura-seguranca.md`. Aprovações da prévia não substituem revisão operacional desta integração. Não pedir nova autorização por subdivisões internas já abrangidas por esta decisão.
