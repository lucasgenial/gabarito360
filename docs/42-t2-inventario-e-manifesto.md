# 42 — T2-A: inventário e conciliação do manifesto

31/10/2026. T1 aprovado pelo responsável, PR #73 incorporado. T2 iniciado com autorização. Esta é a primeira entrega da sequência T2-A a T2-E definida em [docs/40](40-clientes-espacos-e-vinculos.md); não conclui T2 e não implementa clientes/espaços ainda.

## Diagnóstico reproduzível

`php artisan g360:inventariar-espacos` executa consultas de leitura e retorna JSON com IDs institucionais, contagens por escola, inconsistências e pendências. Implementação: `apps/api/app/Services/InventarioEspacos.php` e `apps/api/app/Console/Commands/InventariarEspacos.php`.

Não lê nomes, CPF, e-mail, matrícula, conteúdo de notas, arquivos, payloads da fila ou detalhes livres de sincronização. Os IDs são identificadores internos, não evidência de titularidade jurídica. Não cria clientes, não altera acesso e não gera uma autorização de migração.

Saídas: 0 = diagnóstico sem inconsistências detectadas; 2 = inconsistências detectadas; 1 = falha de execução, com mensagem genérica sem SQL/bindings. `migracao_autorizada` permanece false mesmo com código 0. As contagens de inconsistências podem se sobrepor; a soma representa ocorrências, não pessoas/registros distintos.

Para uma fotografia consistente no MariaDB, usar conexão de diagnóstico com transação REPEATABLE READ e READ ONLY; não alterar configuração global do banco. O serviço abre uma transação, mas não impõe a política de isolamento da conexão. Repetir a conferência imediatamente antes do backfill em janela controlada. Evitar transação longa em bases grandes; medir duração e interromper de forma segura se necessário.

## Resultado observado

Execução somente de leitura no banco de produção, código publicado `dc9949e3690744b72c5071d919955c09ad2be9b8`, em 31/10/2026. Serviço carregado de arquivo temporário, fora do checkout publicado, com sessão MariaDB READ ONLY e REPEATABLE READ. Não houve deploy, migração, envio de e-mail ou alteração de registros.

| Estrutura | Resultado |
|---|---|
| Redes | 2; IDs 1 e 2, ambas com modalidade institucional, sem secretaria e sem titular informado |
| Núcleos | 4; IDs 1/2/3 na rede 1, ID 4 na rede 2 |
| Escolas | 7; IDs 1/2 no núcleo 1, 3/4 no núcleo 2, 5/6 no núcleo 3, 8 no núcleo 4 |
| Turmas | 14; 12 na escola 1 e 2 na escola 8 |
| Alunos | 414; 360 na escola 1 e 54 na escola 8 |
| Provas/gabaritos | 4 de cada, todos na escola 8; 8 associações prova-turma e 180 questões |
| Cartões/notas | 47 de cada, todos na escola 8; 2.115 respostas e 28 registros de ambiguidade |
| Envios B6/visitas/sincronizações SEGES | Zero em cada grupo |
| Integridade verificada | Zero ocorrências nos testes de órfãos, hierarquia e relações cruzadas implementados |
| Contas sem escopo | 2: um administrador de rede e uma conta de aluno |
| Concessões individuais | Zero registros a conciliar |

Não foram listados usuários ou alunos. A conta de aluno sem escopo não será vinculada por adivinhação nem desativada por este inventário. A identificação autorizada da pessoa e do aluno correspondente pertence à conciliação V1, antes de conceder acesso. O administrador atual exige decisão explícita sobre redes administradas, sem perpetuar acesso global implícito.

## Manifesto proposto para decisão

| Chave de planejamento | Cliente proposto | Espaços e origem | Situação |
|---|---|---|---|
| LEGADO-REDE-1 | Cliente provisório interno para a rede 1 | Rede 1; núcleos 1/2/3; escolas 1/2/3/4/5/6 | Aguardando aprovação do responsável |
| LEGADO-REDE-2 | Cliente provisório interno para a rede 2 | Rede 2; núcleo 4; escola 8 | Aguardando aprovação do responsável |

Essas chaves não são IDs futuros do banco. A proposta cria 2 clientes e 13 espaços institucionais. Não cria espaços pessoais, não presume contrato jurídico ou modalidade pagante, não ativa cobrança. Cada dado escolar fica no espaço da escola hoje identificada pelas relações. A aprovação deve ser registrada antes de fixar esse backfill.

Perguntas apresentadas ao responsável separadamente: aprovação dos dois clientes provisórios internos; redes que o administrador atual deve gerir na futura conversão de vínculos. Ausência de resposta não autoriza o mapeamento. Mudanças de contrato, proprietário ou acesso exigem atualização do manifesto.

## Limites e próximos passos

- A inspeção detecta órfãos e divergências estruturais selecionadas; não comprova identidade, contrato, base legal ou correção pedagógica das notas. Uma relação válida no banco pode estar cadastrada incorretamente no negócio.
- O inventário usa a turma atual do aluno para conferir a escola; divergência histórica deve ser investigada, não corrigida automaticamente.
- Autoria ausente continua desconhecida; não atribuir ao executor. Não coletar dados pessoais para preencher o relatório.
- Após aprovação do manifesto: T2-B cria estruturas/colunas opcionais com teste MariaDB e procedimento de retomada; T2-C preenche de forma idempotente; T2-D adapta escritas/leituras; T2-E aplica constraints. Cada entrega informa limites e exige aprovação para merge/deploy/migração.
- Gates de produção preservados: backup recente/restauração com chaves L3, manutenção/fila drenada pelo B5, conferência de contagens e plano de recuperação. Não executar o preenchimento enquanto existirem pendências do manifesto.

## Validação desta entrega

Testes com dados fictícios verificam: banco vazio não autoriza migração; saída sem conteúdo pessoal; apenas SELECTs durante o serviço; agrupamento por escola; relações entre escolas distintas; cartão/nota divergentes; escopo polimórfico inexistente; códigos de saída. A consulta ao MariaDB de produção validou a compatibilidade das consultas, somente para leitura, sem ensaiar migração.
