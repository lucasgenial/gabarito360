# Arquitetura V2 (produto único, sem legado)

> **Atualizado por [ADR-D016](../decisoes/ADR-D016-v2-sem-legado.md):** não há
> reaproveitamento de legado nem compatibilidade retroativa. A V2 é o único
> produto; a "matriz de reaproveitamento" abaixo foi convertida em **plano de
> reconstrução e remoção** (ver [`18-analise-reaproveitamento.md`](18-analise-reaproveitamento.md)).

## Decisão estrutural

A V2 é construída do zero no mesmo repositório, isolada na branch
`v2/mockup-canonico`, sem herdar implementação da V1. A arquitetura alvo é:

```text
Web Blade/Livewire ------\
React Native Android -----> API/Aplicação Laravel 12 ---> MariaDB
Integrações externas ----/           |  |  |
                                     |  |  +--> Storage privado/S3
                                     |  +-----> Redis, filas e cache
                                     +--------> Reverb/WebSockets
App/Workers ----------------------------------> OpenCV/OMR
```

> Atualização: a tecnologia mobile passou de Flutter para **React Native**
> ([ADR-D015](../decisoes/ADR-D015-mobile-react-native.md)).

## Plano de reconstrução e remoção

> Sem reaproveitamento de legado (ADR-D016): cada área é **Reconstruir** (do zero
> sob os contratos V2) ou **Remover**. Detalhamento em
> [`18-analise-reaproveitamento.md`](18-analise-reaproveitamento.md).

| Área | Decisão V2 | Motivo |
|---|---|---|
| Estrutura Laravel 12 (Actions, Requests, Resources, Policies, Services) | Reconstruir | Reaplicar o padrão em projeto V2 limpo, sem herdar implementação |
| Esquema MariaDB | Reconstruir | Esquema único V2; `migrate:fresh` baseline; sem tabelas legadas |
| Sanctum, escopos, auditoria e idempotência | Reconstruir | Definidos como base V2 |
| Aplicações, leituras, resultados e relatórios | Reconstruir | Modelados conforme o mockup, não herdados |
| Docker, Nginx, Redis, Reverb, CI, backup/restauração | Reconstruir | Montados para servir somente a V2 |
| API `/api/v1` | Remover | A V2 expõe apenas `/api/v2` |
| Tokens e componentes UI anteriores | Reconstruir | Extrair tokens do mockup; biblioteca nova |
| Páginas Blade anteriores | Remover | Não implementam o mockup; reconstruir fiel |
| Matriz funcional, rotas e planos R1-R7 | Remover (arquivar) | Não definem escopo V2 |
| App Flutter R6 | Remover | V2 adota React Native (ADR-D015); sem base legada |
| OMR pré-homologação | Remover | Substituído pelo pipeline OpenCV V2 homologado |

## Lacunas que exigem novas estruturas

- Onboarding/cadastro, recuperação de senha e aluno autenticado.
- Agenda, reuniões, visitas, notificações e central de atividades.
- Configurações completas, integrações, plano/uso e solicitações LGPD.
- Relatórios e exportações integrais do mockup.
- Paridade visual automatizada para todas as telas.
- Android com câmera, revisão, sincronização e operação real.
- OMR OpenCV homologado em cartões e dispositivos reais.

## Limites da reconstrução

- A reconstrução é guiada pelo mockup e pelos contratos `docs/v2/`, não por
  código legado.
- Não copiar dados estáticos ou JavaScript do protótipo como regra de negócio.
- Cada capacidade entregue deve ter validação, autorização por escopo, auditoria
  quando aplicável e testes proporcionais ao risco.
- Nenhum artefato legado (rota v1, página, app Flutter, tabela V1) permanece no
  produto após a etapa correspondente.
