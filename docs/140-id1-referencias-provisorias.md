# ID1-D1 — preparação retomável de referências provisórias

Planejamento revisado em04/11/2026, após o inventário agregado demonstrar ausência de documentos válidos para conciliação automática na base de testes da homologação. Esta parte pode avançar sem definir a aprovação de uma fusão: **não reconhece duas linhas como a mesma pessoa**.

Cada conta ou cadastro local de aluno pode receber uma referência provisória própria. A associação é guardada em mapas novos, sem acrescentar/atualizar campos de usuários, alunos ou fatos acadêmicos. Mesmo nome, CPF repetido/inválido ou ausência de CPF não aproxima registros. A mesma conta continua tendo uma referência, independentemente de seus vínculos escolares. Pessoa provisória não recebe documento fictício ou estado verificado.

`g360:identidades-provisorias` simula por padrão, com saída de contagens. `--aplicar` exige ainda `IDENTIDADE_PROVISORIOS_ATIVOS=true`, desligado por padrão; não habilitar na homologação acessível nesta subdivisão. Lote entre1e1000, compartilhado entre as duas fontes. Cada unidade trava a linha de origem, confere mapa atual e cria pessoa+mapa+evento na mesma transação. Reenvio/concorrência não cria pessoa órfã ou segundo mapa. Falha reverte a unidade afetada, conserva as unidades já confirmadas e permite retomada.

Mapas e eventos conservam a proveniência. O mapa contém referência de execução e instante; o evento identifica criação provisória técnica, com ator humano ausente, sem fingir aprovação institucional. Identificadores de origem não aparecem no relatório do comando. Imutabilidade dos mapas não define guarda infinita: retificação, associação canônica posterior e retenção precisam do contrato de conciliação/L4, preservando os IDs históricos. Não repontar mapas reais enquanto esse contrato estiver pendente.

| Parte | Estado/critério |
|---|---|
| Contrato de provisórios | Definido; sem fusão nem documentos verificados |
| Simulação e lote transacional | Implementados, com flag desligada |
| Validação | SQLite/MariaDB, falha/retomada, preservação de origem e dois executores concorrentes |
| Aplicação em base operacional | Não executada; fechamento ID1 e contrato de migração continuam necessários |
| Consulta global e conciliação real | Permanecem sob proposta139; sem novas rotas/permissões |

A migration cria somente os mapas e seus gatilhos, com FKs restritivas. O rollback conserva os dados e não derruba estruturas. Ensaiar em banco/clone descartável; comparar todos os campos de origem e acervo. Integrar após CI; não publicar esta letra interna como fechamento de ID1. A homologação acessível e a produção permanecem inalteradas nesta subdivisão.

Validação local:7 testes/41 asserções no SQLite;8 testes/52 asserções no MariaDB, incluindo dois processos PHP simultâneos bloqueados na mesma origem. Criaram uma pessoa, um mapa e um evento, preservando todos os campos da conta. Falha de evento reverteu a unidade, e a retomada não duplicou nada. A suíte CI inclui os testes de identidade em MariaDB. Não houve aplicação em cadastros reais.
