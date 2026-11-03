# CI1-D — seleção nacional e instituições do espaço pessoal

03/11/2026. Planejamento revisto antes da implementação, após CI1-C/PR155 e a entrega urgente isolada PR156. Sem deploy, SSH operacional, importação ou migration no servidor até concluir L4. O servidor permanece no hotfix83dd5ae; esta entrega parte da main integrada.

## Escopo e sequência interna

| Parte | Entrega |
|---|---|
| D1 | Leitura autenticada mínima, geografia local, filtros e paginação, fixando edições por resposta |
| D2 | Referência pessoal canônica, confirmação explícita, idempotência e histórico auditável |
| D3 | Minhas instituições → Buscar → Conferir → Adicionar; filtros encadeados e código direto |
| D4 | Testes de isolamento, edições, revogação, falhas, banco e interface; Git/CI/integração |

Não criar instituições, escolas operacionais, espaços, turmas, vínculos institucionais ou contratos por selecionar uma referência. Não associar turmas existentes automaticamente. O uso da referência no cadastro de turmas/disciplinas pertence ao PA1, previsto no115. Solicitações privadas, resolução de lacunas, ajustes e poderes de gestores continuam no E. Não exibir botão de solicitação que ainda não execute nada; informar esse limite honestamente na ausência de resultado.

## Contratos e autorização

Flag CATALOGO_INTERFACE_ATIVA=false na API e web por padrão. Capacidade catalogo.consultar e catalogo.referencia_pessoal.gerir atribuídas nesta entrega somente ao professor_autonomo. A primeira não concede escola.criar nem acesso acadêmico; os outros perfis/capacidades são integrados no E. Exigir vínculo pessoal vigente do proprietário e as capacidades em cada requisição. Identidade ou contexto interno/institucional não substitui contexto pessoal.

GET catalogo/ufs, catalogo/municipios?uf=...&busca=..., catalogo/instituicoes e catalogo/instituicoes/{UUIDunidade}. Resposta somente com projeções públicas permitidas e metadados de paginação; nunca dados de contratação, contatos privados ou IDs operacionais. Município paginado e pesquisável, não baixar5.571 opções no navegador. Geografia da edição básica vigente, ou superior quando só essa existir; informar geografia_id e manter resultado fixado durante COUNT/itens. Busca institucional usa as duas edições capturadas na mesma resposta. Sem publicação utilizável:503, ausência:200 vazio, filtro inválido:422. Código exato exige namespace e dispensa geografia. Busca normalizada/curingas literais reaproveita B3.

GET catalogo/referencias-pessoais; POST com unidade_id, edicao_id e confirmacao aceita; POST /{UUIDreferencia}/arquivar com versao. Escopo derivado do ator, nunca do formulário. Seleção valida edição ainda vigente sob lock da fonte, dados validados e elegibilidade; básica em atividade(código1), IES com situação não informada permitida como referência pública validada, explicitamente sem afirmar atividade regulatória/comercial. Básica paralisada/extinta não admite nova seleção, mas aparece na busca histórica explícita. Mudança de edição entre busca/confirmar:409, exigir conferir novamente.

## Persistência e histórico

Migration aditiva000006: referencias_instituicoes_pessoais(UUID, espaço/proprietário, vínculo original, unidade, entrada/publicação escolhidas, situação ativa/arquivada, versão, datas) e eventos append-only com ator/vínculo, ação, versões e edições anterior/nova. UNIQUE(espaço,unidade), FKs compostas para comprovar proprietário pessoal/vínculo e entrada/unidade/edição. Não reescrever projeções públicas. Guardar entrada imutável como evidência do que foi confirmado, em vez de duplicar um snapshot de nome livre.

Repetir seleção mesma unidade/edição ativa retorna mesma referência sem evento duplicado. Arquivar preserva histórico; reativar exige nova confirmação de uma edição corrente elegível. Reconfirmar edição nova é explícito e gera evento; não atualizar silenciosamente a declaração antiga. Histórico lista nome/edição escolhidos e sinaliza se a unidade mudou ou saiu da edição corrente, sem descartar referência nem sugerir que o contrato escolar acabou. Eventos imutáveis; identidade da associação não pode ser reatribuída. Falha ao auditar reverte a operação inteira.

Ordem de escrita: fonte → usuário → vínculo → espaço → cliente/permissões → referência; revalidar contexto/permissões sob locks e antes do commit. Locks no proprietário serializam seleções concorrentes do mesmo espaço; unique é a guarda final. Contexto/retirada de permissão não podem ser ignorados por cache. Arquivamento exige versão atual enquanto ativa, e repetir arquivamento já realizado não cria evento. Sem exclusão física, e-mails ou notificações.

## Interface e limites

Menu Minhas instituições somente no contexto pessoal habilitado. Página principal lista declarações pessoais e histórico, com Adicionar instituição. Busca em formulário próprio: tipo, UF, município pesquisável/paginado, nome/sigla ou código oficial com fonte. Trocar UF limpa município e seleção; limpar/filtros/paginação preservam contexto por aba. Resultados verticais e confirmação em tela própria, sem seleção silenciosa. Mostrar escola de referência versus sede administrativa IES e a cobertura limitada de campi. Mensagens curtas explicam atuação declarada e ausência de acesso escolar. Erros de rede geram indisponibilidade/tentar novamente, nunca falsa ausência.

## Validação e saída

Testar mínima projeção, paginação/limites/acentos/SQLliteral, UF/município cruzados, todas as UFs da fixture, fonte ausente, IES e código direto. Exercitar dois proprietários, instituição sem contrato, referência duplicada/arquivamento/reativação, edição antiga/inativa, revogação/permissão/ator falsos e rollback de evento. Demonstrar nenhum vínculo/escola/prova/gabarito alterado, com dados acadêmicos fictícios não vazios. MariaDB valida FKs/triggers além de SQLite; teste de duas seleções intercaladas e guarda unique. A carga nacional,20conexões e recuperação/Chrome integrado final continuam no F, sem transformar recortes unitários em evidência desse gate. Conferência visual D usa somente homologação local fictícia no Chrome, sem deploy. Atualizar esta tabela e a do usuário ao concluir; sem aprovações por partes internas.

## Estado da implementação D1–D4

D1–D3 implementados. API com nove testes/96 asserções em MariaDB; recorte SQLite29/276 inclui B3 e perfis (sobreposto). Web recorte final5/51 inclui integração pessoal (sobreposto ao4/34 inicial). JavaScript: dois testes de mudança rápida de UF/resposta atrasada e falha de rede, incluindo descarte de resultados antigos. Verificador de29scripts, zero erros. Migration000006 executada apenas em bancos de teste locais; nenhum SSH ou deploy no servidor. Ensaio real de processos concorrentes e CI são registrados ao concluir.

Conferência visual local D4 pendente: o controle do Chrome foi interrompido pela ferramenta por não conseguir confirmar a URL atual no Windows com segurança. Não foram feitas novas tentativas após esse bloqueio, nem alteradas configurações do navegador. O roteador fictício de homologação está foraGit em operacao-20261102/ci1-d-preview.php; seu servidor foi encerrado. Esse roteador não acessa o banco/API operacional. Não declarar o CI1-D totalmente encerrado antes de retomar essa conferência, mesmo se o código for integrado sob flag desligada. CI1-E/F e o deploy continuam pendentes.

Ensaio multiprocessos MariaDB concluído:1teste/13asserções,121,34s incluindo boot/migrations/workers no ambiente local. Dois processos PHP reais aguardam lock do proprietário, confirmam simultaneamente e retornam a mesma referência, com exatamente1evento. Revogação confirmada antes da liberação do lock faz terceiro processo recusar403, sem novo evento. Isso cobre escrita concorrente dirigida, não substitui o gate F de20consultas/desempenho/recuperação integrada. Casos D incluídos no job MariaDB e testes do encadeamento de municípios no job telas.
