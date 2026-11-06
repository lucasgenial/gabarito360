# AP1 — contrato HTTP e operação independente

## Fronteira existente e preservada

A Web/BFF e a API têm `composer.json`, lock, bootstrap, configuração e runtime próprios. A Web usa `ApiClient` via HTTP; não importa os modelos, os controladores ou o banco de negócio da API. O ambiente de homologação executa pools FPM distintos. Sua origem HTTP é definida por `API_BASE_URL`, sem vincular as URLs de navegação `/app/*` aos caminhos da API.

`API_BASE_URL` é a raiz anterior à versão: desenvolvimento local `http://127.0.0.1:8001/api`; em um futuro host separado, por exemplo, `https://api.example.test/servico/api`. Os consumidores acrescentam `/v1`. Não acrescentar `/v1` à variável. O valor explícito já configurado no servidor é preservado nesta entrega. O exemplo anterior e o fallback duplicavam a versão; corrigem-se apenas os valores padrão/exemplo.

## Contrato do painel

| Elemento | Contrato preservado |
|---|---|
| Recurso | GET `/api/v1/dashboard/coordenador` |
| Seleção | `escola_id` opcional, validado contra o escopo; múltiplas opções exigem seleção |
| Autenticação | Bearer no cabeçalho `Authorization`, somente servidor Web→API; nunca credencial em query |
| Sucesso | 200 JSON com `data.escola`, `data.kpis`, `data.provas`, `data.alunos_atencao`, `data.agenda` |
| Provas | Até oito, ordenadas por atualização descendente; mesmos campos e tipos, formatos de turmas e contagens |
| Agenda | Até quatro entradas; mesma janela e ordenação de datas |
| Dados | Mesmo tratamento de notas ausentes/zero, alunos inativos, cartões ambíguos e turma histórica da aplicação |
| Recusas | 401 sem autenticação; 403 sem perfil/contexto válido; 404 para seleção alheia; 422 para seleção necessária/inválida |

Os demais métodos/caminhos e envelopes não são alterados. Empates sem critério secundário no contrato anterior não recebem silenciosamente uma ordenação nova. A comparação de dados distingue variação de ordem e mudança de conteúdo.

## Separação de validação e publicação

- CI mantém jobs independentes de API e Web, cada um com suas dependências; o gate MariaDB inclui os novos testes de hierarquia e contrato/desempenho do painel.
- Os testes de consumo Web usam origem/prefixo HTTP distintos e verificam métodos, Bearer, query de filtro, JSON e preservação de erro. Não dependem de uma rota `/app` na API.
- O artefato de homologação identifica explicitamente os arquivos alterados da API; os demais arquivos, manifestos Composer, rotas e configurações do ambiente são comparados com a base. Não se exige API byte-idêntica quando a mudança autorizada é sua implementação interna; exige-se o contrato preservado.
- A publicação desta entrega usa a release atômica já existente, com backup e rollback de código. Não altera banco/esquema nem publica produção. O fato de o artefato agrupar os apps não autoriza acoplar seu contrato ou suas credenciais.
- Separar fisicamente servidores, reorganizar diretórios e criar automação de publicação de cada app exigem mudanças operacionais próprias. Não antecipar essas migrações nem afirmar que já foram realizadas.

## Evidência

`DashboardDesempenhoContratoTest` verifica payload, tipos, limites, histórico, relações incoerentes, seleções e crescimento de consultas entre 1/4/8 provas e 260 alunos. Seus casos de contrato também são executados contra o código anterior em QA isolada. `HierarquiaAcessoConsultasTest` compara o algoritmo anterior e prova que revogação/mudança de alcance continuam visíveis com o mesmo ator. `ContratoApiIndependenteTest` verifica a fronteira HTTP da Web e a configuração padrão.

Medições e recibos operacionais ficam fora do Git, sem tokens, senhas, SQL com bindings ou dados pessoais exportados. Relatar valores observados e os limites da amostra; não converter uma melhoria no painel em promessa de SLA de produção.
