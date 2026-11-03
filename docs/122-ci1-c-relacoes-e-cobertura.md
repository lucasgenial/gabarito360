# 122 — CI1-C: relações declaradas, cobertura e lacunas

03/11/2026. Continuidade autorizada dos planos116/119/121, após PR154 integrado. Planejamento fechado antes do código. Subdivisões internas não exigem autorização adicional. Sem deploy/migration no servidor até fechar L4; gabaritos e demais dados operacionais preservados.

## Semântica conferida e limites

Dicionário básico2025, Tabela_de_Escola: CO_ESCOLA_SEDE_VINCULADA é Código da escola sede, Num8; CO_IES_OFERTANTE é Código da IES vinculada à escola, Num14. Ambos não aplicáveis a escolas sem vínculo. Dicionário Superior2024: CO_IES é Código único de identificação da IES, Num8. Isso descreve relações entre referências, não comprova que a escola seja campus da IES ou a mesma identidade institucional.

Tipos de relação: sede_escolar e ies_vinculada. Preservar o código informado e sua origem; para IES, normalizar representação numérica removendo zeros à esquerda, mantendo o valor bruto de até14dígitos. Código canônico fora da cobertura IES2024 permanece como lacuna, sem inventar registro. Vazio/zero não referencia entidade. Código escolar exige8dígitos, sem preenchimento artificial. Relações não alteram tipo, unidade, autoridade, vínculos, clientes ou dados acadêmicos. Não classificar instituição pelo nome ou pela oferta vinculada.

Inspeção nacional prévia:3.545 vínculos de sede (3.543 alvos presentes,2 ausentes) e391 vínculos IES (385 presentes,6 ausentes), total3.936.187 códigos IES e1.394 sedes distintas;23 relações com escola e sedeIES em UFs diferentes. Ausência/UF distinta não prova erro, campus, criação ou extinção. Os anos das fontes são2025 e2024. Confirmar totais na implementação, não assumir completude antes do ensaio.

## Plano de execução C1–C5

| Parte interna | Entrega |
|---|---|
| C1 | Leitor por cabeçalho, checksum do mesmo arquivo básico usado na publicação, ano/contagem/códigos, proveniência e detecção de autorreferência/ciclos |
| C2 | Conjunto versionado por par de publicações básica/IES, extração e projeção em lotes, lease/retomada e ativação atômica |
| C3 | Fila técnica de lacunas de fontes e alertas de consistência, sem cadastros fictícios ou resolução humana automática |
| C4 | Consulta interna da relação e declaração explícita da cobertura das unidades disponíveis; integração visual/solicitações permanece D/E |
| C5 | Ensaios nacionais, concorrência/retomada/isolamento e recuperação, com Git/CI antes de integrar |

## Dados e preservação

catalogo_relacao_conjuntos referencia duas publicações já finalizadas, schema relacoes_2025_v1, hashCSV, manifesto, contagens/cursor, estado preparando/publicado/falhou e lease30min. Chave única usa par de edições/schema/hashCSV. catalogo_relacoes guarda ordem, código de origem, tipo, código bruto/canônico do alvo, entradas públicas de origem/alvo quando existentes, publicação exata de cada entrada, UFdistinta, estado referenciada/alvo_ausente/autorreferencia/ciclo e hash da projeção. Relação inexistente no catálogo não ganha identidade nova.

catalogo_relacao_lacunas é fila técnica append-only por relação com o motivo público da pendência; não é protocolo privado de professor. Resolução humana e manutenção de unidades entram no CI1-E com capacidades próprias e decisões versionadas. Conjuntos antigos não são reescritos quando surgir nova edição do alvo. catalogo_relacao_eventos sela o fechamento; ponteiro único em catalogo_relacao_vigencia é trocado na transação final.

Snapshots/relacionamentos/lacunas/eventos são imutáveis por triggers; conjunto finalizado também é imutável. Inserção de filhos só é permitida enquanto o conjunto está preparando. Complementar também as proteções B2/B3 para bloquear acréscimo de observações/entradas depois que o pai foi selado, preservando os registros existentes e sem editar migrations já integradas. Migration exclusivamente aditiva; sem down destrutivo.

## Validação e concorrência

Leitor faz uma passagem do CSV, valida hash antes/depois e total de linhas contra a importação básica; seleciona apenas campos públicos necessários. Pares normalizados ficam em arquivo temporário que usa até2MiB de memória antes de ir ao disco. Análise de ciclos usa o grafo escolar, sem criar hierarquia operacional. Autorreferência e ciclos são preservados e enviados à revisão, não descartados nem tratados como relações confirmadas. Fim da análise calcula digest determinístico das relações, incluindo alertas.

Resolução usa somente as entradas das duas publicações capturadas, inclusive dados históricos de situação censitária, sem consultas acadêmicas e sem inferência por nome. Lotes de até1.000 inserem relação/lacuna/checkpoint na mesma transação; apenas o token vigente avança. Comparar todas as projeções/contagens com a extração antes de ativar; repetir checksum do arquivo antes do fechamento. Falha deixa conjunto isolado, sem trocar edição.

Ordem de locks na aquisição/finalização: fontes em ordem de nome → vigência de relações → conjunto. No fechamento, as publicações capturadas precisam continuar vigentes e o ponteiro anterior não pode ter mudado. Depois de uma atualização B3, o conjunto anterior continua preservado; a consulta só o considera vigente se o par de publicações ainda corresponder às fontes atuais. Consulta fixa o conjunto antes de ler dados, como B3 fixa edições. Reexecução de conjunto finalizado retorna recibo sem reativar histórico.

## Comandos e consumidores

g360:catalogo-relacionar {arquivo} --basica=UUID --superior=UUID --sha256=HASH [--dry-run] [--lote=1000] [--max-lotes=N]. Destinado a operador autorizado do ambiente, sem endpoint público. Simulação valida sem gravação; max-lotes pausa sem ativar. A fila técnica e a cobertura têm consulta CLI mínima, sem avisos/e-mails a instituições ou solicitantes.

Consulta interna por unidade pode receber a edição básica que o consumidor já fixou; não misturar relações de outra edição. Resultado informa par de fontes e natureza declarada da relação. Cobertura separa escolas de referência, sedes administrativas IES e relações; não promete todos os campi/polos. Professor independente terá seleção emD e solicitação privada emE. Dados públicos importados continuam sem criar contratação, emprego ou acesso escolar.

## Aceite

Testar arquivo/cabeçalho/ano/checksum/contagem, largura14/8, zeros, duplicidade, dois tipos, ciclo/autorreferência, alvo ausente, UF/anos distintos, pausa/retomada, token/lease, fechamento concorrente, erro em evento, troca B3 invalidando vigência e leitura fixada; inserts após selar recusados no banco. Demonstrar preservação B2/B3 e dados acadêmicos fictícios. Executar base nacional em MariaDB descartável e restaurar com conteúdo/índices/chaves/triggers idênticos. Não antecipar gate CI1-F de20conexões concorrentes/performance/Chrome. Atualizar tabela ao concluir, conforme preferência expressa do usuário.

## Resultados locais de C1–C5

Leitor e serviços implementados, com snapshot técnico de lacunas, consulta mínima e comandos. Registro de fonte usa posição lógica do CSV (inclui cabeçalho), não offset físico. SQL também bloqueia inserts após selagem B2/B3/C. Testes: recorte SQLite43 testes/290 asserções e MariaDB42/269 (sobrepostos, incluem regressão B2/B3); ajuste final do recibo de vigência atômico conferido em2 testes/42 asserções. Sem endpoints/interface ou poderes novos a perfis.

Arquivo nacional íntegro:214.192 registros básicos lidos e3.936 relações. Pausa em1.000, estado preparando/vigente=false;50,49s eRSS69.332KiB. Retomada no mesmo conjunto08a508a7-8b7a-4796-aee4-b9661481c49f até3.936, estado publicado/vigente=true;59,24s eRSS69.172KiB. Totais abaixo128MiB; só ativação lógica no banco local de ensaio, não deploy.8 lacunas correspondem às duas sedes escolares e seis IES já identificadas. Cobertura continua214.192 escolas de referência e2.561 sedes IES; nenhuma unidade adicional inventada. Resolução humana das lacunas e inclusão comprovada de unidades ficam no E, conforme contrato.

Ainda requer CI completo antes de integrar. Validação formal CI1-F de20conexões/Chrome permanece pendente, mesmo com ensaios de integridade adiantados. Não publicar o aplicativo nesta subdivisão; permanece gate L4 integral. Ao concluir a tarefa, apresentar sempre tabela atualizada, preferência permanente do responsável.

Restauração local nacional concluída:17tabelas com conteúdo/SHA-256 idênticos, índices/chaves/triggers iguais. As12tabelas B3 originais permaneceram iguais ao manifesto anterior à etapaC. No restaurado, foram exercitadas imutabilidade e recusa de inserções após selagem em observações/entradas/relações/lacunas/eventos. Evidências agregadas em operacao-20261102/ci1-c-recuperacao-20261103.json e ci1-c-guardas-restauradas-20261103.json, foraGit. Ensaio usou bancos privados descartáveis, não os dados escolares do servidor.
