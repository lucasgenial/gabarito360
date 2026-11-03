# 120 — CI1-B2: preparação nacional retomável

03/11/2026. Execução interna do CI1 aprovado, após119. Sem deploy ou migration no servidor até fechar L4. Esta parte persiste somente observações públicas em staging; promoção, consultas e seleção nacional continuam em B3..F.

## Plano revisado antes da implementação

1. Extrair do validador o serviço compartilhado de manifesto: checksum dos três arquivos antes/depois, schema versionado, geografia, duplicidade e cobertura27UFs. Mantém comando de validação sem banco.
2. Migration aditiva isolada: catalogo_geografias contém snapshot normalizado versionado por hashes IBGE; catalogo_importacoes contém identidade da ingestão, manifesto, origem oficial/edição/schema, cursor e lease; catalogo_observacoes contém ordem, identificador, payload por lista positiva e SHA-256. Presença de colunas é guardada uma vez no manifesto, não repetida nas214mil linhas.
3. g360:catalogo-preparar exige --todas-ufs e SHA-256 esperado para gravar; --dry-run somente valida, sem banco. Fontes/edições e proveniência são as duas verificadas no119. Arquivos ficam fora do Git; não há download automático nem caminho/credenciais persistidos.
4. Validação integral precede gravação. Identidade da ingestão usa fonte/ano/schema/encoding, hashes do CSV e das duas geografias. Repetir a mesma entrada retoma ou retorna a mesma importação validada. Mudança de arquivo/geografia gera outra identidade; não substitui observações antigas.
5. Escrita em lotes de até1.000 sob transação: observações e cursor avançam juntos, com índices únicos por identificador e ordem na importação. Lease com token/expiração e revalidação sob lock impedem dois executores de avançar a mesma carga; não manter transação aberta durante leitura integral do CSV. Executor antigo não grava após perda/expiração do token.
6. --max-lotes permite pausar de maneira explícita, sem declarar validação concluída. Estado validando/lease liberado, cursor confirmado; retomada relê/valida a fonte e ignora ordens já confirmadas. Não retomar por offset de byte, pois CSV pode conter campos multilinha.
7. Ao final, conferir hashes novamente, totais/contagens e continuidade antes de marcar validado e liberar lease. Falha deixa staging isolado e checkpoint anterior; não marca publicado. Não há alteração de censo_escolas/escolas, nem criação de espaços, contratos, vínculos, turmas ou dados pessoais. Validado significa apto para próxima revisão, não catálogo publicado.

## Concorrência, integridade e limites

Lease com validade30min renovada a cada lote; as operações verificam token e prazo sob lock. Exceção após adquirir lease marca falhou e libera somente seu próprio token; exceção de concorrência na aquisição não altera o trabalho de outro executor. Uma retomada pode partir de falhou/validando; não modifica observações confirmadas. Depois de validado, reexecução retorna recibo existente sem reabrir a carga. Promoção futura verificará hashes das observações, estado e manifesto; não poderá promover estados parciais. O serviço atual não expõe edição em produção.

Snapshot geográfico é separado para não carregar centenas de KiB a cada lock do cursor. Mesmo par de arquivos IBGE reutiliza a mesma geografia. Alterações futuras serão outra versão, sem reescrever localização histórica. Campos públicos selecionados do leitor, situação censitária e derivação de UF preservados. Sem CPF, pessoas, contatos ou estatísticas de estudantes; sem preservar colunas desconhecidas do CSV.

Limites iniciais: lote1..1.000; memória do leitor nacional alvo128MiB. Bulk insert no banco e dados normalizados limitados por lote; teste de volume integral/desempenho de escrita permanece parte do ensaio CI1-F. Contagens oficiais conferidas no119 não substituem ensaio da ingestão persistida.

## Aceite

Testar: simulação sem banco; escrita nacional com tipos/sigla e geografia; pausa/retomada sem duplicação; repetição validada idempotente; conteúdo/geografia diferentes não sobrescrevem carga anterior; rollback de lote e recuperação; lease concorrente/expirado e token antigo recusados; hash alterado durante execução impede validação final; preservação de tabelas operacionais; índices/FKs em MariaDB. Cobertura incompleta e fonte inválida recusadas antes da primeira gravação. Migration sem down destrutivo.

Integração Git após verificações aprovadas; nenhuma aprovação adicional por esta subdivisão. Depois: B3 promoção versionada e de-para, preservando todos os IDs/ajustes operacionais; CI1-F fecha recuperação, carga, isolamento e conferência visual.

## Implementação e verificações

Geografia/observações recebem triggers de imutabilidade contra UPDATE/DELETE em MariaDB e SQLite, desde a inserção; correções serão novas observações/edições. Manifesto inclui digest ordenado dos payloads normalizados; fechamento relê a tabela por paginação de chave (sem OFFSET crescente), confere sequência, hash individual e digest total. Uma inserção espúria com hash individual coerente também é recusada. UFs/códigos do IBGE precisam corresponder às27 combinações oficiais, não apenas somar27 entradas arbitrárias.

Testes locais iniciais:22 testes/123 asserções no recorte API;10 testes/57 asserções no MariaDB descartável, incluindo triggers, FKs/índices usados, pausa/retomada, falha no checkpoint, lease e fonte modificada. Instância local isolada, sem rede, socket privado em /tmp/g360-ci1-b2-20261103/mysql.sock; não é o banco/servidor da aplicação. Simulação com2.561 IES reais/27UFs/5.571 municípios concluída sem gravação, pico PHP56.623.104bytes.

## Ensaio integral em MariaDB local — 03/11/2026

Banco descartável g360_ci1_volume_test, sem tabelas operacionais, com schema/constraints/triggers desta migration. Fonte básica pausada em5.000 registros (99,91s, RSS95.648KiB), retomada pelo mesmo UUID até214.192/214.192, estado validado (125,83s, RSS96.040KiB). A retomada revalidou o arquivo e concluiu a comparação do digest de todas as observações persistidas. IES:2.561/2.561, estado validado (19,85s, RSS91.304KiB). Total216.753 observações, duas importações, uma geografia reutilizada. Todos os processos abaixo de128MiB de RSS; tempos locais observados com execuções concorrentes, sem promessa de latência no servidor.

As5.000 ordens iniciais permaneceram presentes; triggers impedem reescrever suas linhas. O UUID da carga básica foi preservado entre pausa e retomada. Isso valida a preparação nacional persistida; não comprova ainda promoção/consulta/publicação ou desempenho de busca. Ainda faltam B3 e demais partes até CI1-F. Sem deploy, migration, carga ou leitura adicional de dados privados do servidor neste incremento.

Após paginação por chave/seleção somente dos campos públicos, recorte API20 testes/110 asserções aprovado; leitor final7/42 aprovado (há sobreposição entre recortes). Testes de sigla anteriores2/13 permanecem no CI. MariaDB10/57 aprovado, incluindo imutabilidade no banco. Verificações completas do PR continuam necessárias antes da integração.
