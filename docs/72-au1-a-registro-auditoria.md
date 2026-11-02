# AU1-A — base de registro de auditoria

## Revisão antes da implementação

PR #105 incorporado em `b441b91`, com aprovação. V1 e A1 já fornecem contextos separados. Os eventos específicos de clientes, vínculos e acessos internos continuam transacionais; o registro geral de requisições não os substitui.

1. Preparar tabela aditiva e registro assíncrono na fila `baixa`, desligado por padrão até aprovação da ativação. Capturar alterações e leituras da API, inclusive recusas, somente quando uma rota conhecida foi encontrada.
2. Guardar apenas identificadores, contexto, método, modelo de rota, horário UTC e status HTTP. Nunca copiar corpo, resposta, URL concreta, query, headers, mensagem de exceção ou credenciais. IP anonimizado por rede; job cifrado.
3. Registrar o resultado HTTP após a resposta. Falha de enfileiramento não desfaz a operação; emitir alerta técnico constante, sem payload. Retry idempotente por UUID. Não prometer entrega garantida se a fila estiver indisponível.
4. Sem endpoint de edição/exclusão. Modelo somente para leitura. A proteção não cobre administrador do banco com acesso SQL; privilégios e conservação operacional são parte da etapa seguinte.
5. Testar captura real, recusas, ausência de segredos, contexto, fila, repetição e indisponibilidade em SQLite/MariaDB.

## Divisão de AU1

- **AU1-A (este PR):** base, captura, privacidade e falhas, com mês UTC indexado.
- **AU1-B:** definir retenção específica de auditoria (os 30 dias aprovados eram para logs da aplicação), ensaiar volume/recuperação, política de preservação, privilégios e estratégia de particionamento físico no MariaDB. Nenhuma limpeza automática nesta etapa.
- **AU2:** consulta, filtros e exportação por perfil, em fase própria.

AU1 permanece parcial até AU1-B. T5 continua dependente dessa conclusão. Há migration nova somente preparada e testada localmente/CI; sem deploy, migration ou habilitação no servidor. Usuário real e visto são iguais nesta etapa; S1 deverá fornecer atribuição confiável quando existir, nunca por header/payload.
