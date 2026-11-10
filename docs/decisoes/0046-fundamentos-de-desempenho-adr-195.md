# ADR-0046 — Fundamentos de desempenho e aceitação das funcionalidades (ADR-195)

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-09
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** docs/195-adr-fundamentos-desempenho.md (ADR-195); docs/192-fundamentos-desempenho.md; docs/194-planejamento-desempenho-integral.md
**Afeta:** [FN-077](../funcionalidades/077-desempenho/planejamento.md); [FN-007](../funcionalidades/007-escala-infraestrutura/planejamento.md); [FN-053](../funcionalidades/053-paineis-turmas/planejamento.md); [FN-054](../funcionalidades/054-paineis-editor/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

O App Web/BFF e a API Laravel já têm fronteiras e runtimes próprios. As medições da homologação mostraram consultas repetidas, assets sem reaproveitamento e carregamento em duas etapas. A baseline reunia métodos distintos: o kernel da API pela CLI, com identidade em memória, e a navegação HTTP real. Não se pode subtrair seus tempos nem anunciar percentis de uma amostra única. Consultas e preferências não concedem acesso.

O usuário aprovou planejar e executar o conjunto por etapas. Uma melhoria isolada, como reduzir statements de um resolver, pode ser experimento da etapa F2 e não a encerra.

## Decisão

Dez decisões do ADR-195:

1. **Fronteira API e BFF.** Banco e regras pertencem à API. A WEB consome HTTP e compõe a apresentação. Contratos descrevem método, versão ou opt-in, DTO, erros, limites e compatibilidade. O navegador usa UR4 (G360Requisicao). IDs, credenciais e referências de acesso não entram nas URLs públicas.
2. **Arquitetura evolutiva.** Mantenha a API modular, com serviços de aplicação e queries de leitura explícitas. Prepare a operação para um servidor de API separado. Não adote SPA, React, microserviços nem serviços de push por inferência de desempenho.
3. **Acesso atual.** O servidor revalida usuário, vínculo, espaço, perfil, recurso e histórico. Consolide predicados e leituras sem guardar concessões no ator, por requisição ou por TTL. Preferência, recibo, hash, canal entre abas e cache não autorizam acesso.
4. **Leitura proporcional.** Projete só os campos necessários. Filtre, ordene e pagine no banco antes de materializar. Totais do recorte independem da página. Substitua SQL por item por agregação ou lote autorizado. Escolha índice por query, plano e volume medidos.
5. **Cache por classe de dado.** Assets técnicos classificados podem usar URL por hash e política imutável. HTML, JSON, foto e documento pessoal não recebem regra genérica de cache. O cache estrutural pessoal usa contexto, versão, catálogo, capacidades, schema, padrão, projetor e autoridade corrente. TTL limita retenção e custo e não define autorização.
6. **Privacidade em todos os stores.** Não coloque senha, token, decisão de permissão, dado escolar nem texto livre em cache técnico persistente do navegador **ou do servidor**. Títulos pessoais podem conter informação escolar. Estrutura sem títulos ainda pode ser metadado pessoal associável: não declare anonimização.
7. **Concorrência e jobs.** Use transação curta, versão ou CAS e chave idempotente. Retry não repete comando em silêncio. Jobs tratam entrega repetida, timeout, `retry_after`, prioridade e rollback, e revalidam o contexto na execução e na entrega.
8. **Frontend progressivo.** Carregue uma região inicial pequena. Opções, abas, fotos e módulos vêm sob demanda. Preserve conteúdo anterior, foco, scroll, impressão e mobile. Descarte respostas antigas e desmonte listeners, observers e blobs. Loader global não substitui tratamento regional.
9. **Medição privada e por camada.** Identifique release, método, cenário, volume, população e estado de cache. Separe rede, BFF, SQL, render e fila, e tempos inclusivos de exclusivos. Nenhum corpo, SQL literal, binding, aluno, título, e-mail, cookie ou credencial entra na evidência. Não exporte HAR bruto.
10. **Evidência e publicação.** Cada etapa fecha implementação, testes proporcionais, revisão independente e CI antes do merge e da publicação somente na homologação. F0 e F1 podem fechar em documentos sem deploy.

**Checklist proporcional de entrada e aceite:**

| Área | Definir na entrada | Evidência de aceite |
|---|---|---|
| Contrato e volume | Consumidor, DTO, status, erros, opt-in, universo e limites | Compatibilidade, campos necessários e cenário pequeno e grande |
| Acesso e concorrência | Contextos, revogação, CAS, idempotência, retry | Isolamento e revogação; disputa e duplicata sem sobrescrita |
| Consulta e plano | Filtros, ordem, página, agregações, índices | Sem query por item; plano dos caminhos críticos; totais preservados |
| Cache | Classe de dado, chave, versão, invalidação, TTL ou "sem cache" | Hit e miss; alteração e revogação; sem dado pessoal vedado |
| Interface e lifecycle | Conteúdo inicial, módulos sob demanda, erro, cancelamento | Trace de chamadas e bytes; callbacks antigos descartados |
| Medição e telemetria | Cenário, release, método, orçamento por camada | Resultado separado de meta; evidência sanitizada fora do Git |
| Jobs e operação | Efeito, prioridade, timeout, retry, rollout, rollback | Falha, duplicata, rollback e revogação seguros |

Aplique cada item ao risco. "Não aplicável" exige razão concreta.

## Consequências

- Alternativas rejeitadas: cache de concessões ou de HTML pessoal (desloca o problema para revogação e retenção); mais hardware ou nova plataforma primeiro (sem evidência; capacidade será avaliada em F6); experimento pontual como entrega integral.
- Metas de latência, queries e bytes precisam de baseline e calibração. Não são SLA automático.
- Invariantes iniciais: a contagem não cresce por item onde deve haver lote; coleção nova é limitada; o runtime não duplica; a carga secundária não antecipa; o custo obrigatório de segurança permanece medido.
- Não compare CLI com HTTP como a mesma medida. Não produza P95 de amostra insuficiente.
- O ADR não declara as etapas F0 a F6 implementadas. Evoluir o ADR exige decisão explícita do responsável.
- O modelo de PR em `.github/pull_request_template.md` implementa o checklist.

## Verificação

- O modelo de PR cita o ADR-195 e pede contrato, acesso, consulta, cache e validação.
- Relatórios e protocolos operacionais ficam em `operacao-20261102`, fora do Git quando sensíveis.
