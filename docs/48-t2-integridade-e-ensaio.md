# 48 — T2-E: integridade e ensaio do corte

Base: PR #79 incorporado em `718472178aa6229689326dfeb2ca108480f5a785`. O responsável autorizou implementar T2-E, sem deploy. Esta implementação deriva do T1 aprovado (doc. 40, seções 4 e 9) e do roteiro D/E (doc. 44).

## Entrega em revisão — PR #80

[PR #80](https://github.com/lucasgenial/gabarito360/pull/80), aguardando aprovação de merge.

## Escopo autorizado

Preparar auditoria somente de leitura, DDL retomável e ensaio fictício em MariaDB. A auditoria cobre as 13 tabelas pedagógicas, espaço/autoria, relações compostas, referências exclusivas e unicidades. A aplicação das restrições é uma operação explícita de corte, separada de `migrate`: exige manifesto exato, manutenção, confirmação de escritores parados e adaptador ativo. Esta entrega mantém `espacos.ativo=false`; não executa o corte real.

As validações de matrícula e CPF do aluno passam a usar o espaço autorizado no modo novo, inclusive CSV, preservando identidade global de usuários. Duplicidade no destino é recusada; coincidência entre espaços não transfere nem mescla pessoas. No legado, a validação continua global. O banco só aceita a nova unicidade depois da operação de corte.

DDL: `espaco_id` e `origem_autoria` obrigatórios; autoria desconhecida permanece explicitamente marcada, sem autor inventado. FKs de espaço/autoria e compostas entre registros; referências de `espacos` exclusivas conforme tipo; índices únicos `(espaco_id,matricula)`, `(espaco_id,cpf_hash)` e `(espaco_id,usuario_id,chave)` dos envios. Campos opcionais como aluno do cartão continuam opcionais. A integridade SQL complementa autorização e verificações de relações funcionais; não libera espaços pessoais ou vínculos V1/V2.

DDL MariaDB confirma passos separadamente. Reexecução verifica definições e retoma passos faltantes, nunca desativa FKs, usa IGNORE ou apaga dados. Falha mantém manutenção, registra o passo técnico e requer correção progressiva; não oferece rollback destrutivo. Referência: [commits implícitos do MariaDB](https://mariadb.com/docs/server/reference/sql-statements/transactions/sql-statements-that-cause-an-implicit-commit).

## Publicação futura (não autorizada)

1. Aprovar release/corte e manifesto atualizado; conferir backup restaurável, arquivos e chaves L3.
2. Ensaiar na versão e no volume representativo de produção, registrar duração por passo, disco livre e orçamento de manutenção. Testes fictícios não dimensionam a janela real.
3. Manter API/web em manutenção e parar agendador, workers, importadores e outros escritores; drenar trabalhos antigos. Conferir processos e filas externamente: a opção CLI é uma declaração do operador, não prova de ausência de escritores.
4. Aplicar expansão aditiva, preencher manifesto e conciliar administrador explicitamente. Executar auditoria e comparar contagens antes/depois; resolver todos os bloqueios.
5. Preparar o release com adaptador ativo ainda em manutenção, após aprovação específica. Simular o corte, aplicar restrições e repetir a auditoria/inspeção de esquema. Não retomar código legado depois de relaxar unicidades globais.
6. Validar dois contextos, recusas cruzadas, importações e fila; só então retomar serviços e acompanhar erros. Em falha, manter manutenção e retomar o mesmo plano revisado. Restauração exige análise de escritas posteriores e aprovação própria.

## Comandos e retomada

- `php artisan g360:auditar-espacos`: contagens, cobertura por espaço/origem de autoria e ocorrências de inconsistências; sai com falha se houver bloqueios. Uma linha pode produzir mais de uma ocorrência. Não imprime nomes, matrículas, CPF/hash, imagens, notas ou payloads.
- `php artisan g360:restringir-espacos`: valida o manifesto, o administrador conciliado, dados e definições de esquema; lista passos pendentes sem DDL.
- Aplicação futura autorizada: `php artisan g360:restringir-espacos --aplicar --manifesto=legado-20261031-v1 --escritores-parados`. Requer adaptador ativo e manutenção. Nenhum desses requisitos é ativado automaticamente.

O plano verifica todos os objetos existentes antes do primeiro DDL, preserva contagens/cobertura e informa início/fim e duração de cada passo. Colisão de nome com definição diferente bloqueia. Lock de conexão MariaDB impede dois cortes simultâneos e não expira durante DDL; o lock operacional também exclui preenchimento/conciliação por até uma hora. Nunca ultrapassar essa janela nem iniciar preenchimento concorrente. Espera por metadata lock é limitada a 15 segundos, sem promessa de limite da duração de reconstrução de tabela. Interrupção preserva os passos confirmados: retomar o mesmo comando, com manutenção e escritores parados. Não usar `migrate:rollback` nem `foreign_key_checks=0`.

## Fronteira das constraints

Todos os 13 grupos recebem espaço obrigatório, FK de dono/autoria, origem de autoria obrigatória e verificação dos marcadores. Autoria `registrada` exige autor existente; `legado_desconhecido` conserva autor nulo; `sistema` aceita execução sem ator humano. Cartão sem aluno e recibo sem cartão continuam válidos.

Raízes escolares conferem `(espaco_id,escola_id)` com o espaço. Filhos conferem `(espaco_id,id)` dos pais, logs conferem também o cartão da resposta, visitas conferem escola/núcleo. Instituição/usuário não disparam exclusão de dados pedagógicos pelas FKs substituídas; cascatas internas já usadas na exclusão autorizada de provas/cartões e substituição de respostas permanecem. Não há rotina nova de eliminação.

Relações funcionais mutáveis (aluno/turma da prova, nota versus identificação atual do cartão) continuam verificadas pelos consumidores e pela auditoria; não se introduz FK que impeça a correção autorizada de identificação. O recibo de entrada e o cartão podem apontar para provas diferentes **do mesmo espaço** após leitura de QR; ambos são reautorizados. O inventário passa a bloquear o cruzamento de escola nesse caminho, preservando o redirecionamento já implementado. A chave B6 e sua consulta mudam juntas para espaço/usuário/chave.

Não há transferência automática, nova deduplicação entre escolas, liberação de espaço pessoal, exclusão por retenção, alteração de contrato ou mudança de TTL.

## Evidências

Ensaio local em MariaDB 10.11.14 isolado: seis testes, 78 asserções, passaram. Incluem auditoria sem dados pessoais; aplicação/repetição do DDL; nulos, órfãos e cruzamentos recusados; unicidade por espaço na API/CSV/B6; OMR com QR, revisão, substituição e exclusão; interrupção entre passos e retomada; bloqueio por falta de manutenção/adaptador e por esquema divergente. A aplicação em base fictícia pequena (oito registros pedagógicos antes do corte) somou 11.479,6 ms de DDL; a suíte levou 174,51 segundos incluindo recriação das bases. Isso não estima a duração em produção nem certifica desempenho em 100 instituições.

O ensaio identificou e corrigiu dois pontos específicos do MariaDB: substituição de FK precisa de nome distinto no mesmo ALTER, e remoção do índice global B6 exige manter índice de suporte à FK do usuário. O plano cria os índices novos antes de remover os antigos. O CI passa a ter job próprio MariaDB 10.11 para essa suíte; a regressão comum mantém o esquema expandido com adaptador desligado. Resultado final de regressão/CI será registrado no PR.

Não houve deploy, migração real, ativação ou alteração de acessos em produção. Para concluir a fase operacional de T2 ainda faltam ensaio representativo na versão de produção, orçamento de manutenção, revisão do release de ativação e autorização específica para publicação/corte. V1/V2 continuam pendentes; não anunciar isolamento por vínculo/aba como entregue.
