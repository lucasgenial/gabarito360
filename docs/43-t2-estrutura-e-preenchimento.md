# 43 — T2-B/C: expansão e preenchimento de espaços

Implementação incorporada pelo PR #75 em `7289fe1917bfceab7ab78c324e66c2b17602f8ea`, sem deploy. Continuidade: [T2-D/E](44-t2-adaptacao-e-corte.md). Base: PR #74 incorporado em `79eb72f5f950b03185de4dfbe4f9584b36e45395`. O responsável aprovou dois clientes internos provisórios e a futura gestão das duas redes pelo administrador com vínculos separados. [Manifesto aprovado](42-t2-inventario-e-manifesto.md). Nenhuma migração ou escrita em produção foi realizada nesta entrega.

## Escopo desta entrega

- Migração `2026_10_31_000006_expandir_clientes_e_espacos.php`: cria `clientes` e `espacos`, referências institucionais únicas e relações restritas à exclusão; acrescenta `espaco_id`, `criado_por` onde ausente e `origem_autoria` às 13 tabelas previstas. Colunas novas permanecem opcionais; autoria preexistente da prova é preservada.
- `EstruturaEspacos::MANIFESTO` versiona exclusivamente as relações aprovadas: redes 1/2, núcleos 1–4 e escolas 1–6/8. Não contém nomes de pessoas, contratos ou credenciais.
- `g360:preencher-espacos` simula o inventário/hierarquia sem alterar tabelas. `--aplicar --manifesto=legado-20261031-v1` solicita a aplicação explícita e exige manutenção fora de testes. Um lock compartilhado impede duas execuções concorrentes do comando durante até uma hora; a janela operacional deve ser menor que esse limite. Nunca iniciar segunda execução após expirar o lock sem confirmar que a primeira encerrou.
- Preenchimento cria 2 clientes internos e 13 espaços, preenche os dados escolares pelo caminho da escola e marca autoria inexistente como `legado_desconhecido`. Não atribui autores, não muda permissões, não cria vínculos e não ativa cobrança.
- A rotina compara hierarquia, modalidade e presença de titular/secretaria com o inventário aprovado. Órfãos, relações cruzadas ou sincronizações SEGES sem destino estruturado interrompem a execução.

A simulação valida origem e manifesto; não certifica que a estrutura expandida ou todos os destinos existentes estão íntegros. A aplicação confere as colunas e os clientes/espaços existentes dentro da transação e recusa divergências sem sobrescrever. Não publicar T2 como concluído com base somente na simulação.

## Integridade e repetição

Os registros de clientes usam chaves estáveis `LEGADO-REDE-1/2`; os espaços são identificados pela referência institucional única. Repetição reutiliza os mesmos IDs e não muda nomes, conteúdo escolar, cifras, fotos, notas, avatares, datas de atualização ou escolhas de usuários.

O preenchimento usa uma transação de dados única para este volume inicial. Percorre os registros em blocos de 200, inclusive `prova_turmas` com sua chave composta, mas só confirma ao final. Qualquer divergência desfaz os novos clientes/espaços e preenchimentos da execução. Retomar significa executar novamente, não descartar divergência. Contagens por tabela precisam ser iguais ao inventário anterior e nenhum registro das tabelas preenchidas pode ficar sem espaço.

`sincronizacoes_seges` recebe colunas na expansão, mas o manifesto atual só permite preencher com essa tabela vazia. Lotes existentes exigiriam conciliação própria, sem inferência a partir do conteúdo livre de `detalhes`.

O DDL não é parte dessa transação: a migração verifica existência de tabelas, colunas e índices, permitindo reexecução dos passos previstos sem apagar dados. Isso não repara esquema adulterado; se uma estrutura existente divergir do formato esperado, interromper e revisar. `down()` recusa exclusão automática, pois poderia perder propriedade e autoria.

## Continuidade obrigatória em T2-D/E

Esta entrega não ativa o novo isolamento. Os fluxos atuais ainda podem criar registros com espaço nulo, por isso o backfill deve ser repetido na janela final de corte, com escritores antigos parados. As colunas novas não são aceitas como autorização por endpoints atuais.

T2-D adapta todas as leituras/escritas e tarefas do [mapa T1](41-t1-mapa-de-consultas.md) ao contexto aprovado. T2-E acrescenta FKs simples/compostas de espaço/autoria onde faltam, valida a exclusividade das referências de `espacos`, torna os campos necessários obrigatórios e muda unicidades de alunos. Não liberar novos clientes, espaços pessoais ou acesso por vínculo antes dessas etapas e dos respectivos testes.

Documentos/contatos de clientes ainda não têm fluxo de cadastro nesta entrega: os clientes provisórios não recebem esses dados. T4 deve usar a proteção L3 ao oferecer cadastro de documentos; nunca preencher a coluna `documento` com texto simples.

## Procedimento para a futura publicação autorizada

1. Conferir SHA revisado e CI, manifesto e inventário atualizado. Se surgirem instituições novas, interromper; não ampliar o mapa automaticamente.
2. Conferir backup/restauração e chaves L3; ensaiar DDL na mesma versão de MariaDB de produção. Registrar duração e janela disponível.
3. Publicar pelo roteiro B5 autorizado, que aplica a migração aditiva e drena filas. O roteiro B5 não chama o comando de preenchimento automaticamente.
4. Para o preenchimento autorizado, abrir janela coordenada: manter API/web em manutenção, agendador e workers parados e nenhum importador ou outro escritor ativo. A verificação local de manutenção no comando não prova que todos esses processos pararam; o operador confere isso antes.
5. Executar simulação e conferir o manifesto. Aplicar com identificador exato, dentro da janela. Conferir contagens, IDs dos espaços, ausência de nulos e autoria; não imprimir dados pessoais.
6. Enquanto D/E não forem publicados, a aplicação continua no regime legado. Retomar serviços conforme roteiro e registrar essa limitação; preferir agendar o preenchimento definitivo junto do corte D/E.

Retorno: não executar `migrate:rollback`. Em falha do preenchimento, a transação reverte e a estrutura permanece. Em falha de DDL, manter janela e retomar/reparar os passos revisados. Antes de D/E, código legado tolera colunas adicionais, mas não deve ser usado como estratégia automática após novos espaços/pessoal/cópias serem liberados. Qualquer restauração destrutiva exige aprovação com análise das escritas posteriores.

## Validação

Testes fictícios cobrem simulação sem escrita, manifesto divergente, preenchimento repetido, preservação de conteúdo/autoria/permissões, 205 questões atravessando limite de bloco, associação de chave composta, espaço divergente com reversão integral, exigência de manutenção e repetição da migração sem exclusão. Cinco cenários de estrutura/preenchimento passaram em SQLite e MariaDB 10.11.14 isolado local (53 asserções em cada), com 41 migrações aplicadas no banco descartável e confirmação das FKs pelo DDL real. O teste adicional da guarda de manutenção e as regressões completas são conferidos no CI. Não houve conexão de escrita à produção. Resultado final e CI ficam registrados no PR.
