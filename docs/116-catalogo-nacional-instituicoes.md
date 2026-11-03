# 116 — Catálogo nacional de instituições e solicitações de cadastro

03/11/2026. Complemento solicitado pelo responsável do produto ao plano115. Planejamento, sem importação real, alteração do ECOAR, migrations, notificações ou deploy. Aplicação do desenho de sistemas do plano115. As capacidades abaixo são propostas a implementar; não descrever o catálogo nacional completo como disponível antes de medir a carga.

## 1. Investigação: reaproveitar o que existe

ECOAR consultado somente para leitura em D:/projetos/ECOAR-MQA. Evidências: docs/10-integracao-censo-escolar-inep.md; mqa-api/config/censo.php; app/Console/Commands/ImportEscolas.php; app/Support/Censo/ArquivoCenso.php; app/Services/Escolas/EscolaService.php; ecoar-react/apps/web/src/pages/escolas/EscolaFormPage.tsx. Há importação por código INEP, leitor streaming por cabeçalho, configuração anual, lotes, simulação, filtros de rede/UF/município/situação, proteção de edição manual e geografia IBGE. O formulário encadeia UF e município. Não houve teste funcional do ECOAR nesta investigação, nem confirmação de suas contagens atuais de produção.

O Gabarito360 já reaproveita parte desse trabalho: apps/api/app/Console/Commands/ImportarCenso.php, app/Support/Censo.php, app/Http/Controllers/Api/CensoController.php e migration2026_10_31_000001_escola_censo.php. Já separa censo_escolas (consulta) de escolas (operacionais). O comando possui filtro padrão BA, aceita conjunto de UFs, simulação e upsert por código; consulta atual exige escola.criar, busca texto/município e limita a30, sem contrato de navegação nacional paginada por UF/município codificado. Portanto, evoluir e conciliar, não reconstruir nem importar todas as referências como espaços contratados. Não foi consultado o banco do servidor; cobertura nacional atual não confirmada.

## 2. Fontes oficiais e cobertura

Fontes verificadas em03/11/2026:

| Conjunto | Fonte e edição observada | Uso e limite |
|---|---|---|
| Educação básica e oferta profissional abrangida pelo Censo Escolar | [Microdados INEP](https://www.gov.br/inep/pt-br/acesso-a-informacao/dados-abertos/microdados/censo-escolar), edição2025, página informa atualização do arquivo em julho/2026 | Código da escola, localização, dependência e situação; conferir dicionário do arquivo antes da carga |
| Instituições de ensino superior | [Microdados do Censo Superior](https://www.gov.br/inep/pt-br/acesso-a-informacao/dados-abertos/microdados/censo-da-educacao-superior), edição2024 listada | Adaptador próprio para IES e geografia efetivamente disponível; não presumir que possui todos os campi/polos ou mesma estrutura do Censo Escolar |
| Cadastro regulatório de ensino superior | [Cadastro e-MEC — MEC](https://www.gov.br/mec/pt-br/politica-regulacao-supervisao-educacao-superior/cadastro-nacional-de-cursos-e-ies) | Referência oficial para conferência/complementação de IES e unidades; não foi confirmada API pública de carga em massa nem exportação completa nesta análise |
| UF e município | [API de Localidades IBGE](https://servicodados.ibge.gov.br/api/docs/localidades) | Códigos e geografia local versionada; cruzar com a edição censitária, sem presumir mesma quantidade/nomes em anos diferentes |
| Instituição ausente das bases | Cadastro manual verificado | Atendimento por Suporte/Desenvolvimento ou gestão competente no próprio alcance; não inventar código INEP/e-MEC |

Institutos federais e outras instituições podem ofertar educação básica e superior. Consolidar identidades somente com correspondência comprovada entre fontes; categoria não pode ser inferida apenas pela palavra instituto no nome. Cursos livres ou novas unidades podem faltar nas bases; manter rota de solicitação. O plano busca cobertura nacional das fontes disponíveis, não promete cadastro completo de toda entidade educacional do país.

Não usar credenciais do ECOAR, copiar seu banco operacional nem integrar dependência de sua API em tempo de uso. Reaproveitar desenho e, após revisão de compatibilidade, trechos técnicos; baixar/publicar pacotes oficiais rastreáveis próprios. Cada busca do usuário usa a base local, não consultas ao INEP/IBGE em tempo real. Eventual carga do arquivo local do ECOAR só após verificar edição, origem e checksum, pois o arquivo oficial2025 foi revisado.

## 3. Separar catálogo, contratação e vínculo

1. Catálogo nacional: identidade pública institucional/unidade, mesmo sem contrato na plataforma.
2. Espaço institucional operacional: ambiente com dados escolares e permissões, ligado à instituição/unidade do catálogo quando conciliado. Cadastro de referência não cria cliente, licença, administrador, turma ou espaço.
3. Atuação declarada do professor independente: referência escolhida do catálogo para agrupar suas próprias turmas e registros no espaço pessoal. Não concede acesso à escola, não cria vínculo institucional confirmado, não notifica automaticamente gestores.
4. Vínculo institucional aceito: convite e vigência do plano115, concedendo somente as capacidades previstas. Uma seleção pessoal do catálogo nunca substitui esse aceite.

Professor usa a mesma instituição canônica que outros professores, mantendo dados de seus espaços isolados. Se uma escola contratar depois, conciliar com a referência já existente; não fundir alunos, professores, turmas ou dados pessoais pelo simples compartilhamento de instituição. Corrige a proposta PA1-A de criar referências livres de escolas: o professor seleciona referência canônica e solicita ausências.

## 4. Modelo proposto e preservação do legado

Reusar censo_escolas como área de origem/adaptação, preservando seu contrato atual durante a transição. Proposta aditiva: instituicoes_catalogo (ID interno estável, nome oficial, nome de exibição/aliases verificados, natureza/categoria, situação, status de validação); unidades_institucionais (sede/campus/unidade e município, quando comprovados); instituicao_identificadores (fonte, tipo, código, entidade/unidade de destino); importacoes_catalogo (URL/edição/hash/data/schema/contagens/resultado); solicitacoes_instituicoes e eventos de decisão; referencias_instituicoes_pessoais. Nome/tabelas finais serão definidos no contrato CI1-A, evitando entidades redundantes.

Identificador único no namespace correto (ex.: inep_escola, emec_ies, emec_local_oferta), nunca tratar todos como o mesmo número. A chave externa identifica uma entidade ou unidade conforme o dicionário. Não relacionar IES/campus por um código local sem instituição e fonte quando o código não for global. CNPJ opcional e auxiliar, não chave única de escola: uma mantenedora pode ter várias unidades. Mesma instituição pode ter múltiplos identificadores; mesmo nome não autoriza fusão.

Usar códigos IBGE para UF/município, com nomes preservados para exibição/histórico. Migrar legado com de-para revisado: código oficial exato primeiro; casos sem código ou conflitantes ficam para revisão humana. Não inferir secretaria, núcleo, administração ou autorização pela cidade, dependência, nome ANEXO ou localização. Geografia, natureza administrativa e hierarquia de gestão são dimensões distintas.

escolas.id e demais FKs operacionais permanecem estáveis; acrescentar referência conciliada ao catálogo, sem reatribuir provas, gabaritos, cartões, notas ou alunos. Não excluir registro operacional quando instituição for extinta, perder contrato ou desaparecer da edição nova. Situação censitária, verificação manual, situação comercial e habilitação de acesso são estados separados. A importação não altera donos, permissões, vínculos ou contratos.

## 5. Experiência do professor independente

Em Minhas instituições → Adicionar instituição: UF → Município → Instituição/unidade, com busca por nome/alias/código e filtros opcionais de tipo e rede. Município depende da UF; mudar UF limpa município e resultado incompatível. Permitir busca direta por código oficial sem exigir percurso geográfico. Resultados paginados no servidor, ordenação estável, busca tolerante a caixa/acentos, sem descarregar o catálogo inteiro no navegador. Mostrar nome, município/UF, categoria/rede, código público e situação; detalhes da unidade distinguem campi homônimos. Instituições fora da cidade do professor continuam pesquisáveis.

Seleção exibe confirmação: atuação declarada para organização pessoal; não é acesso ao ambiente da instituição. Relacionar disciplinas/turmas pessoais à referência escolhida. Uma escola pode aparecer para vários professores, sem que vejam uns aos outros ou seus dados. Mostrar histórico das referências já usadas; alertar situação paralisada/extinta sem invalidar histórico e limitar nova seleção a referência validada e elegível pela política.

Não encontrou? Ação Solicitar cadastro, disponível inclusive quando a busca terminar vazia. Formulário: nome, tipo, UF/município, unidade, código oficial se conhecido, endereço/site institucional e observação; não exigir CPF de responsáveis ou enviar dados de alunos. Anexos somente se posteriormente houver armazenamento/varredura/retenção apropriados; primeira versão pode usar URL pública. Mostrar protocolo, situação e pedido de complementação na conta. Busca indisponível é erro com opção tentar novamente; não induzir solicitação duplicada como se não houvesse resultados.

## 6. Solicitações e responsabilidades

| Ator | Consultar catálogo | Cadastro/correção | Receber pedidos pessoais |
|---|---|---|---|
| Professor independente | Nacional, dados públicos mínimos | Solicita; não cria referência oficial diretamente | Somente acompanha os próprios protocolos |
| Suporte e Desenvolvimento | Conforme capacidade interna atribuída | Revisam, conciliam, criam e corrigem cadastro com justificativa/auditoria | Sim, fila interna da plataforma |
| Diretor/responsável institucional | Referências públicas e dados próprios autorizados | Cadastra/completa sua própria instituição/unidade no alcance comprovado | Não |
| Direção de Núcleo | Idem | Somente instituições formalmente abrangidas pelo núcleo | Não |
| Secretaria estadual/municipal ou responsável de rede | Idem | Somente instituições da jurisdição atribuída | Não |

Cadastro manual pela gestão passa por validações de código/duplicidade e escopo no servidor, assim como o interno. Escolher UF/município não cria jurisdição. Pedido para cadastrar nova unidade exige autoridade preexistente da mantenedora/rede ou verificação interna; usuário não pode atribuir a si a primeira direção apenas conhecendo código/nome/CPF. Quando falta comprovação de alcance, encaminhar à revisão interna. Descrever estes novos poderes como capacidades explícitas, não presumir que enum/perfil atual já os implementa.

Pedidos do professor são de origem pessoal e sempre ficam na fila interna Suporte/Desenvolvimento, mesmo quando a escola já tenha diretor ou secretário cadastrado. Não enviar notificações/protocolos ao gestor escolar, núcleo ou secretaria; dados da solicitação não entram nas filas deles. Gestão pode manter seus próprios cadastros independentemente. Se sua ação resolver uma pendência pessoal, a equipe interna concilia o protocolo e notifica o solicitante sem expor sua identidade à gestão.

Fluxo: enviada → em análise → aguardando complementação → aprovada (nova referência ou conciliada com existente) / indeferida com motivo / cancelada. Complementação retorna à análise, não cria novo protocolo. Reenvio/duplo clique é idempotente; pedidos coincidentes podem apontar à mesma instituição, preservando protocolos privados. Concorrência entre importador, gestor e suporte não cria duplicados: constraints por identificador + transação e revisão de conflito. Depois da aprovação, o professor recebe aviso para selecionar/confirmar a instituição; não criar silenciosamente vínculo institucional ou acesso. E-mail, se usado, traz aviso mínimo e link autenticado; não foi enviado nesta entrega.

Auditar autor, contexto/escopo, origem do pedido, analista, decisão/motivo, antes/depois, versão e data. Separar manutenção do catálogo público da capacidade de consultar dados escolares. Suporte/Desenvolvimento com catálogo não recebem automaticamente dados acadêmicos. Retenção de protocolos/evidências exige política própria, sem presumir a mesma dos logs técnicos.

## 7. Importação e atualização confiáveis

Contrato por fonte/edição, lendo campos pelo cabeçalho e verificando dicionário/encoding; campos opcionais não podem bloquear toda carga sem necessidade. Download validado, hash SHA-256 e manifesto, extração controlada fora do Git, validação em staging, dry-run nacional e por UF, relatório de rejeições/colisões/ausências geográficas, promoção em lotes com retomada. Falha parcial não publica edição como completa. Repetir o mesmo arquivo não duplica; não aplicar edição antiga sobre mais nova sem operação explícita de restauração.

Reusar padrão ECOAR de streaming/lotes e proteger ajustes manuais. Melhorar guard global para procedência por campo ou override separado: atualização oficial permanece rastreável e divergência vai à revisão, sem apagar correção autorizada nem congelar desnecessariamente todos os campos. Manter a edição anterior recuperável e a situação histórica. Nova publicação censitária gera lote controlado; não depender de robô de scraping e-MEC nem inventar periodicidade em tempo real. CI1-B usa todas as UFs explicitamente, com contagem por município/UF e validação de cobertura, evitando herdar --uf=BA por acidente.

Indexar município/UF/situação/tipo e identificadores; medir consultas de nome antes de escolher full-text ou busca normalizada. API autenticada de seleção somente retorna campos públicos permitidos, com paginação, limite de tamanho e rate limit; não expor as rotas antigas escola.criar ao professor nem conceder escola.criar apenas para que pesquise. Não retornar IDs de espaços, contratos, alunos, usuários, contatos privados ou indicadores comerciais. Proposta de contratos: GET catalogo/instituicoes, GET catalogo/ufs, GET catalogo/municipios?uf=, POST solicitacoes-instituicoes e GET minhas-solicitacoes; revisão/cadastro internos com capacidades distintas. Rotas concretas a fechar na implementação.

## 8. Etapa CI1 e dependências do plano115

CI1 — Catálogo nacional e cadastro institucional é etapa de nível1. Divisões internas CI1-A..F são técnicas, sem pedido de autorização para cada uma. Planejar antes de implementar; preservar gabaritos e a condição atual de não publicar antes da conclusão/validação do L4 e conciliação do gate.

| Divisão | Entrega/critério |
|---|---|
| CI1-A | Inventário real de censo_escolas/escolas, fontes/dicionários, de-para e contrato de catálogo/unidades/permissões |
| CI1-B | Geografia e importação nacional básica com manifesto, simulação e retomada; contagens verificadas de todas as UFs |
| CI1-C | Adaptador de IES, unidades quando disponíveis, conciliação entre fontes e fila para lacunas; declarar cobertura que de fato foi validada |
| CI1-D | Seleção nacional no espaço pessoal, filtros UF/município/tipo/nome/código e referência declarada isolada |
| CI1-E | Solicitações privadas ao suporte, manutenção por responsáveis no próprio escopo e auditoria |
| CI1-F | Ensaios de concorrência, desempenho, isolamento, atualização e migração preservando dados; conferência visual no Chrome |

R0 precede CI1. Contrato CI1-A precede CI1-B/C/D/E, CI1-F fecha a etapa. ID1 não é pré-requisito para importar dados públicos nem buscar o catálogo: aproveitar contas/contextos atuais; se a implementação depender de novos perfis/vínculos, integrar VI1 antes de habilitar suas capacidades. CI1 precede PA1-A e conciliação institucional TE1; CP1 pode avançar após R0 sem aguardar catálogo. PA1-B/C/D continuam dependentes da identidade/autorização do plano115. Não declarar o catálogo disponível nacionalmente apenas porque existe o importador.

## 9. Aceite e publicação

- Homônimos em cidades/UFs diferentes ficam distintos; trocar UF limpa cidade anterior; busca por código encontra a referência correta; campus não é confundido com mantenedora/sede.
- Professor seleciona escola sem contrato e organiza seus dados pessoais; não lê espaço institucional nem confirma vínculo de emprego. Outro professor não vê suas turmas ou solicitações.
- Ausência gera protocolo apenas interno; diretor, núcleo e secretário não recebem nem conseguem consultar pedidos pessoais, inclusive por IDs adivinhados e exportações.
- Gestão cadastra o próprio alcance; forjar município/núcleo/identificador não amplia jurisdição. Gestor sem autoridade comprovada não ocupa instituição pré-cadastrada.
- Suporte resolve como existente ou nova referência; duplo envio/importação simultânea não duplica código; mensagem de conflito não vaza solicitantes. Indeferimento possui motivo e trilha.
- Edição nova não apaga overrides, referências históricas ou escolas fora da edição; retomada não publica lote incompleto, nem edição velha sobrescreve atual.
- Comparação antes/depois preserva IDs, vínculos, gabaritos, questões, cartões, respostas, notas e arquivos. Migração aditiva; sem remoção de catálogo/operacionais usados ou down destrutivo.
- Homologar tempo/memória de importação, p95/paginação da busca nacional e erros em volume representativo; registrar metas antes do teste, não prometer latência sem medir.

Esta entrega registra pesquisa e plano, não executa importação de base nacional, criação de instituições, notificações, migração ou publicação. Próxima implementação: conciliação R0/CI1-A e CP1 segundo dependências do plano115.
