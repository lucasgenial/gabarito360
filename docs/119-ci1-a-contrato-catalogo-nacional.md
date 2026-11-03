# 119 — CI1-A: inventário e contrato do catálogo nacional

03/11/2026. Derivado dos planos115–117 e autorizado pelo pedido de continuidade após CP1. Skill engineering:system-design já aplicada nesta conversa. Este contrato fecha o inventário/desenho CI1-A e orienta CI1-B..F, que são subdivisões internas sem novas autorizações rotineiras. CI1 completo ainda não está implementado/publicado. Preservar a condição vigente de fechamento integral de L4 antes do deploy.

## 1. Inventário real e correção operacional solicitada

Consulta somente leitura no CT111 em03/11/2026, 01:32 Brasília, com transação READ ONLY; release a90fbf51d1ddc89a34b808c9de1c2997d1705914. censo_escolas contém19.604 registros, todos BA, ano2025,417 municípios. Existem7 escolas operacionais;6 não possuem correspondência por INEP nessa carga. Não classificar essas6 como inválidas nem excluí-las: verificar cadastros manuais/de teste e outras localidades antes do de-para.

A instituição INEP29428254 está ativa no catálogo e em escolas (ID8, município IBGE2925303, núcleo4/rede2), com nome Colégio Estadual do Campo Doutor Eraldo Tinoco. Diretor Escolar, Coordenador Pedagógico, Professor Exemplo e Lucas Matos já possuem vínculos ativos no espaço escolar13. Gestões de núcleo/rede têm os vínculos superiores correspondentes; professor mantém somente as duas turmas atribuídas. Perfis internos Desenvolvimento/Suporte/Financeiro possuem acessos internos separados, sem vínculo escolar implícito. Aluno Exemplo não tem matrícula/escopo de aluno associado: não inventar associação a um estudante real.

O usuário informou que o app mostrava Escola8. Causa confirmada: espacos.nome ainda tinha o rótulo provisório Escola 8, apesar do cadastro escolar correto. Correção pontual explicitamente solicitada, aplicada em03/11/2026 01:35 Brasília: apenas nome/updated_at do espaço13, com snapshot anterior e recibo privado no servidor. Manifestos SHA-256 antes/depois de usuários, vínculos, escopos, acessos internos, escolas, turmas, alunos, provas, gabaritos, questões, cartões, respostas e notas iguais. Mantidos4 gabaritos,180 questões,47 cartões,2.115 respostas,47 notas,414 alunos e14 turmas. Não houve deploy/migration nem criação de vínculos adicionais. Sessões web antigas guardam o nome recebido na seleção; nova seleção busca o rótulo corrigido.

## 2. Fontes efetivamente inspecionadas

| Fonte | Arquivo/edição observada | Inspeção |
|---|---|---|
| [Censo Escolar INEP](https://www.gov.br/inep/pt-br/acesso-a-informacao/dados-abertos/microdados/censo-escolar) | Pacote2025 revisado: microdados_censo_escolar_2025_v2/dados/Tabela_Escola_2025_V2.csv | Download/CRC/checksum integral conferidos:214.192 códigos únicos, nenhum duplicado, nas27 UFs (uma linha com SG_UF ausente); MD5oficial7b0d5882b39619f138fc01ed7abea9b0 |
| [Censo Superior INEP](https://www.gov.br/inep/pt-br/acesso-a-informacao/dados-abertos/microdados/censo-da-educacao-superior) | MICRODADOS_ED_SUP_IES_2024.CSV |1.000.086 bytes;2.561 linhas/CO_IES únicos,27 UFs,698 municípios de sede; MD5dbe25e67857aa68b12beaeffa194f53f corresponde ao manifesto oficial, embora este use o nome CADASTRO_IES |
| Dicionários dos pacotes | Básica: Tabela_de_Escola; Superior: cadastro_ies/cadastro_cursos | Leitura das definições e domínios, não inferência a partir dos nomes de coluna |
| [Localidades IBGE](https://servicodados.ibge.gov.br/api/docs/localidades) | API v1/localidades/estados e /municipios, consulta03/11/2026 |27 UFs e5.571 municípios com códigos oficiais; nenhum sem UF. Tratar hierarquia geográfica imediata/intermediária quando microrregião/mesorregião vier nula |

URLs dos pacotes obtidas nos links oficiais: https://download.inep.gov.br/dados_abertos/microdados_censo_escolar_2025_.zip e https://download.inep.gov.br/microdados/microdados_censo_da_educacao_superior_2024.zip. Originais/dicionários ficam fora do Git. Acesso por intervalos HTTP206 com ETag e CRC do ZIP permite obter apenas os membros necessários, sem baixar dados de docentes/matrículas/gestores nem todo o pacote de cursos. Registrar URL, membro, tamanho, data, hash e revisão; MD5 do publicador verifica integridade, não autenticidade por si só. SHA-256 local da tabela IES: aaa37fb9433d005686616bb9e48c5d7083526fdcc171973e787eed35a2ee349d.

### Descobertas que mudam o importador

- O CSV básico2025-V2 não traz DS_ENDERECO, NU_ENDERECO, DS_COMPLEMENTO, NO_BAIRRO, CO_CEP, NU_DDD, NU_TELEFONE, LATITUDE e LONGITUDE, exigidos por Censo::COLUNAS atual. Não simplesmente exigir todas as colunas antigas nem interpretar ausência como ordem para limpar campos existentes.
- TP_SITUACAO_FUNCIONAMENTO possui4 valores:1 atividade,2 paralisada,3 extinta no ano,4 extinta em anos anteriores. O mapa atual só cobre1..3. Preservar o valor original e impedir seleção nova das extintas, sem eliminar seu histórico.
- CO_ORGAO_REGIONAL é código de órgão regional de ensino, não autorização nem identificação nacional única de núcleo. Não inferir jurisdição por ele sem UF e de-para comprovado.
- CO_ESCOLA_SEDE_VINCULADA e CO_IES_OFERTANTE são relações de vinculação/oferta. Não provam que duas entidades são a mesma pessoa institucional e não autorizam fusão.
- No Superior, CO_MUNICIPIO_IES identifica sede administrativa/reitoria. Não criar campi fictícios nesse município nem prometer busca de todos os campi a partir da tabela IES. Cadastro de campus/polo faltante segue revisão manual ou fonte complementar verificada.
- CO_IES é identificador único da IES na fonte. Usar namespace inep_ies; não assumir equivalência numérica com outro sistema sem conferência explícita. INEP escolar e IES não compartilham a mesma chave de negócio.

Validação integral local das fontes: CSVs Windows-1252, sem linhas com quantidade irregular de campos; básica214.192 registros e superior2.561, códigos únicos em cada namespace. SHA-256 básico f737d25dc5e80879e08f868d06d55f2bc1178cbb893dc14589a2123151f5b523. Uma ocorrência básica (INEP43145833, município4319901) não informa SG_UF; normalização deve usar o município oficial do IBGE e registrar a derivação, sem alterar a observação original nem inferir pelo nome. Divergência entre códigos de UF/município deve bloquear o registro para revisão. Superior não apresentou divergências geográficas. Manifesto detalhado fora do Git em operacao-20261102/auditoria-fontes-ci1-20261103.json; nenhum desses dados nacionais foi carregado no servidor.

### Tipos e sigla, conforme complementos do produto

Filtros combináveis por UF, município e tipo de instituição. Tipos normalizados: escola, faculdade, centro_universitario, universidade, instituto_federal, cefet; instituto/outro somente por cadastro validado quando não houver classificação oficial correspondente. No Superior, TP_ORGANIZACAO_ACADEMICA1→universidade,2→centro universitário,3→faculdade,4→Instituto Federal,5→CEFET. Educação básica permanece escola; etapa/modalidade/oferta não muda automaticamente esse tipo. Dependência administrativa (federal/estadual/municipal/privada) é filtro separado; categoria especial de IES é preservada, sem forçar classificação incorreta. Tipo nunca é inferido pelo nome.

Campo sigla opcional (até40 caracteres), com busca por nome ou sigla, sem unicidade. Na tabela IES, SG_IES está preenchida em2.102 registros e possui até20 caracteres na edição conferida; importar quando presente, preservando ajuste manual. Na básica não há coluna equivalente: não gerar sigla pelas iniciais. Catálogo e unidades podem possuir sigla própria, preservando nome completo. Incremento solicitado já prepara sigla nullable em escolas operacionais, formulário de criação/edição e exibição/busca, com regras de escopo existentes. Migration aditiva; não executada no servidor. Rótulo do espaço não passa a ser sigla automaticamente.

## 3. Fronteiras e identificação

Catálogo é referência nacional compartilhada. Espaço operacional contém dados privados e contratação. Atuação declarada do professor é associação pessoal à referência pública. Vínculo institucional depende de convite/aceite/vigência. Selecionar referência ou possuir código INEP não cria contrato, acesso, gestor, turma, usuário ou jurisdição.

Cada instituição tem ID interno estável. Identificador externo tem namespace + código normalizado; UNIQUE(namespace,codigo). Escola: inep_escola,8 dígitos. IES: inep_ies, código numérico canônico válido conforme o dicionário, sem confundir com o INEP escolar. Registro manual recebe UUID interno e nenhum número oficial inventado. CNPJ, nome e endereço não são chaves de fusão: mantenedora e unidades podem compartilhar esses valores.

A seleção referencia uma unidade/localização conhecida da instituição. Para educação básica, representa a escola identificada no Censo. Para IES, a tabela inicial oferece referência da sede administrativa, rotulada dessa forma, sem alegar ser campus. Unidades adicionais exigem identificação/conferência próprias. Relações sede/vinculada/ofertante preservam IDs e proveniência; nunca fundem cadastros automaticamente.

## 4. Estrutura aditiva fechada para CI1-B/C

| Estrutura proposta | Campos e invariantes principais |
|---|---|
| catalogo_importacoes | UUID, fonte, ano, revisão, URL/membro, SHA-256, schema_versao, estado, totais, cursor/lotes e instantes. Mesmo hash/fonte/schema identifica a mesma ingestão. Estados recebido/validando/validado/promovendo/publicado/falhou; só publicado é edição vigente |
| catalogo_observacoes | importacao_id + chave_fonte únicos, payload público sob lista positiva, campos_presentes, hash da linha e rejeição/motivo. Imutável depois de validar; reprocessamento é outra revisão rastreável |
| catalogo_instituicoes | ID, nome oficial, sigla opcional e busca normalizada, tipo, dependência/fonte original, situação e validação. Estado comercial não faz parte da situação censitária |
| catalogo_identificadores | namespace/codigo únicos, instituicao_id e origem da comprovação. Identificadores não são reciclados nem reatribuídos em importações automáticas |
| catalogo_unidades | ID, instituicao_id, chave_unidade, natureza/localização, nome, sigla opcional e município/UF por IBGE, proveniência. UNIQUE(instituicao_id,chave_unidade); evitar UNIQUE nullable que permita duplicar unidade principal |
| catalogo_ajustes | Instituição/unidade, campo, valor autorizado, justificativa, ator/contexto e versão; preserva observação oficial separada. Ajuste não autoriza alterar hierarquia operacional |
| escola_catalogo_vinculos | escola_id operacional único, unidade_id conciliada, ator/método/instante/versão. Não trocar escolas.id nem reatribuir suas FKs; ambiguidade bloqueia associação |
| referencias_instituicoes_pessoais | espaço pessoal + unidade únicos, proprietário/contexto, situação e datas. Declaração não confirma emprego nem acesso escolar |
| solicitacoes_instituicoes + eventos | UUID/protocolo, autor/espaço, origem pessoal ou institucional, dados mínimos, escopo comprovado quando aplicável, estado/versão, destino interno e decisão rastreável |

Geografia local: catalogo_ufs(codigo_ibge,sigla,nome) e catalogo_municipios(codigo_ibge,uf_id,nome,normalizado), versionados por carga; não duplicar geografia por instituição. Usar IBGE como referência e cotejar nomes/códigos censitários da edição. Alteração territorial não reescreve retrospectivamente a localização de uma observação antiga. Cache não substitui publicação validada.

Reusar censo_escolas como legado de consulta/compatibilidade e fonte para conciliação. Não inserir214mil linhas em escolas operacionais nem transformar cada referência em espaço contratado. Importador novo não chama o modo legado --atualizar-escolas; a migração de consumidores será explícita. Identificar campos ausentes de verdade separadamente de campos presentes vazios, inclusive na proteção dos valores manuais.

## 5. Contratos de leitura

GET /api/v1/catalogo/ufs retorna lista pública mínima local. GET /api/v1/catalogo/municipios?uf=BA&busca=... pagina por código IBGE; UF inválida gera422. GET /api/v1/catalogo/instituicoes aceita uf, municipio_ibge, busca, namespace/codigo, tipo, dependencia, situacao, page e per_page (padrão20, máximo50). Exigir localização ou termo/código com comprimento mínimo para buscas textuais amplas. Código exato funciona sem UF. Município de outra UF retorna422, não resultados fora do filtro.

Ordem determinística nome_normalizado/id; escapar curingas SQL e normalizar acentos/caixa. Índices iniciais: identificador único, município/situação/tipo/id e UF/nome_normalizado/id. Busca por substring deve ser medida em carga real antes de adotar full-text. Resposta contém itens (unidade_id, instituicao_id, nome, sigla, natureza, tipo, dependência, município/UF, identificadores públicos, situação e edição), metadados de paginação e cobertura. Não expor espaço/cliente/contrato/usuário/aluno ou contatos privados. Ausência de resultados200; serviço indisponível503; dados/filtro inválidos422. Não tratar falha de rede como instituição ausente.

Autenticação continua obrigatória, leitura pública mínima por capacidade catalogo.consultar. Professor independente pode consultar nacionalmente sem escola.criar. Capacidade não lê dados acadêmicos. Referência pessoal só é gravada por proprietário com contexto pessoal vigente, referindo unidade validada/elegível; duplicidade retorna a mesma associação. Instituição paralisada/extinta continua no histórico e em consulta explícita, mas não é elegível para novo uso corrente sem política/revisão própria.

## 6. Solicitação e manutenção

POST /api/v1/catalogo/minhas-solicitacoes recebe chave UUID, nome/tipo, UF/município, unidade, código se conhecido, site público e observação limitada. Sem anexos/CPF de responsáveis/dados de alunos na primeira versão. GET do mesmo recurso e detalhe por UUID exigem autor e contexto de origem; conhecer protocolo não concede leitura. Reenvio mesmo payload é idempotente; payload diferente com mesma intenção409. Professor não cria registro oficial diretamente.

Fila interna usa catalogo.solicitacao.analisar; manutenção global usa catalogo.manter_global, somente Suporte/Desenvolvimento explicitamente habilitados para o catálogo. São poderes cadastrais, não acesso acadêmico universal. Solicitações pessoais não entram em filas, notificações, exportações ou consultas da escola/núcleo/secretaria, mesmo quando a instituição já existir. Não enviar automaticamente pedido ao diretor encontrado.

Gestores usam catalogo.manter_escopo: diretor/responsável mantém sua instituição; núcleo/rede/secretaria somente a abrangência comprovada. Geografia/código conhecido não comprovam autoridade. Primeira vinculação de nova instituição à gestão exige comprovação/revisão interna, impedindo autoapropriação de referência pré-cadastrada. Perfil ainda não compatível com o regime de vínculos não será habilitado por atalho; integrar contrato de perfis/VI1 antes de conceder capacidade ao secretário ou novo papel.

Estados enviada → em_analise → aguardando_complementacao → em_analise → aprovada/indeferida; cancelada quando permitido antes da decisão final. Aprovar resolve para nova referência ou existente, sem revelar outros solicitantes. Decisão exige versão atual, motivo, ator e evento transacional. Concorrência suporte/gestor/importador usa identificadores únicos e locks consistentes, nunca busca por nome seguida de inserção desprotegida. Aprovação notifica solicitante com link autenticado; não cria vínculo institucional nem transfere dados automaticamente. E-mail mínimo; outbox e recibo idempotentes. Sem envio nesta etapa.

## 7. Carga, conciliação e publicação

Fluxo CI1-B: arquivo oficial rastreável → leitor streaming por cabeçalho/schema → validação nacional/UF e relatório → observações em staging → revisão de colisões/de-para → promoção retomável → publicação de edição completa. Falha em lote não marca edição completa. Edição antiga não substitui mais nova automaticamente. Usar --todas-ufs explícito para a carga nacional, evitando herdar BA do comando legado. Preservar20xx/versões/overrides e ausências de campo. Nunca apagar instituição por ausência na nova fonte.

O de-para das7 escolas existentes compara identificador exato no namespace, depois confere município/UF e divergências. A escola29428254 tem correspondência comprovada. Demais6 exigem relatório/revisão; não preencher identidade por similaridade de nome. Nenhuma mudança de vínculo operacional é efeito colateral da importação. Rótulos do contexto devem acompanhar nomes conciliados sem alterar identidade/autoridade; sessões antigas não podem ficar como fonte canônica do nome institucional.

Metas iniciais de homologação (não resultados já medidos): memória do leitor limitada a128MiB com lotes até1.000; leitura nacional em streaming; repetição não duplica IDs; busca p95 até500ms em ambiente equivalente, com20consultas concorrentes e cache quente, medir também frio. Ajustar meta por evidência antes do aceite. Nenhum limite de tempo autoriza publicar dados parcialmente validados.

## 8. Critérios de saída e continuidade

CI1-A: inventário real registrado; arquivos/dicionários oficiais e integridade conferidos; contrato de namespace/unidades, sigla/tipos, preservação, leitura, solicitações e capacidades definido. Bases nacionais conferidas localmente, sem confundir19.604 BA do servidor com214.192 registros básicos nacionais. CI1-B implementa leitor/staging/promoção básica e conciliação; CI1-C IES/cobertura de unidades; CI1-D/E seleção pessoal e manutenção/solicitações; CI1-F fecha teste integrado/Chrome/recuperação/desempenho antes de publicação. Não pedir autorização a cada subdivisão interna.

Aceite integrado deve comprovar homônimos separados, filtros cruzados inválidos recusados, código exato, sede/campus distintos, preservação manual, catálogo sem contrato selecionável no espaço pessoal, ausência de acesso acadêmico implícito, privacidade dos protocolos pessoais, gestor confinado à jurisdição, idempotência/concorrência, edição atômica, backup restaurável e conteúdo escolar igual antes/depois. CI1 não está concluído apenas por aprovar este contrato.

## 9. Incremento entregue: sigla e validador de entrada

Sigla opcional (40 caracteres) implementada no modelo/API/formulário/detalhe e busca operacional, mantendo permissões e isolamento existentes. Siglas repetidas são aceitas sem fusão. Migration exclusivamente aditiva, ainda não executada no servidor.

CI1-B1 implementa apenas o leitor e g360:catalogo-validar. Comando sem banco, com hash antes/depois, duplicidade, versão de esquema e conciliação IBGE. --nacional exige geografia e registros nas27 UFs. Não é importação, promoção, endpoint nem filtro já disponível no app. Staging/publicação e filtros da interface continuam pendentes em CI1-B..F.

Execução local integral em03/11/2026: básica214.192 registros,27 UFs,5.571 municípios na referência IBGE, pico72.351.744 bytes; superior2.561 registros,27 UFs, mesmo IBGE, pico56.623.104 bytes. Ambas abaixo da meta128MiB. Básica teve uma UF derivada do código IBGE, com CO_UF coerente e proveniência preservada. Superior:206 universidades,417 centros universitários,1.897 faculdades,39 institutos federais,2 CEFETs. Hashes SHA-256: básica f737d25dc5e80879e08f868d06d55f2bc1178cbb893dc14589a2123151f5b523; superior aaa37fb9433d005686616bb9e48c5d7083526fdcc171973e787eed35a2ee349d. Não há medição de desempenho de busca, pois a consulta nacional ainda não foi implementada.

Validação do incremento: API sigla/leitor7 testes/50 asserções; web completa117 testes/1.019 asserções; verificador29 scripts/0 erros. Casos incluem sigla repetida sem fusão/acesso cruzado, sigla removível/limite, escape HTML, tipos IES desconhecidos bloqueados, UF divergente recusada, derivação rastreada e geografia IBGE com hierarquia nova. Testes do comando verificam ausência de uso do banco, hash divergente, duplicidade e recusa de falsa cobertura nacional.
