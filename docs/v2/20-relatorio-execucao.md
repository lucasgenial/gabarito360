# Relatório de Execução — Fase de Planejamento V2

- **Data:** 2026-06-13
- **Branch:** `v2/mockup-canonico`
- **Escopo desta fase:** análise dos artefatos e produção dos contratos de
  planejamento da V2 (não inclui implementação de telas/código de produto).

## 1. Resumo executivo

O replanejamento V2 confirmou e formalizou a direção já iniciada no repositório:
o mockup `style-system/` (30 telas) é o **contrato funcional e visual integral**
do produto ([ADR-D014](../decisoes/ADR-D014-v2-mockup-canonico.md)). Sobre essa
base, esta fase aplicou três decisões do replanejamento:

1. **Banco de dados:** MariaDB (confirmado; o pedido citou PostgreSQL, mas a
   direção manteve MariaDB, coerente com a modelagem e o ambiente já existentes).
2. **Documentação:** padrão pt-br, nomes e conteúdo, integrada a `docs/v2/`.
3. **Aplicativo móvel:** **React Native** substituindo Flutter
   ([ADR-D015](../decisoes/ADR-D015-mobile-react-native.md)).

A análise mostrou que a maior parte da documentação V2 já existia; o trabalho
desta fase foi **completar lacunas** (GAP, reaproveitamento, estratégia de Git,
relatório) e **propagar a virada para React Native** pelos documentos canônicos,
sem duplicar conteúdo nem criar um conjunto paralelo em inglês.

## 2. Decisões arquiteturais

- **Produto único, sem legado (ADR-D016):** a V2 é construída do zero. Não há
  reaproveitamento da fundação R0–R7, `/api/v1`, app Flutter ou páginas legadas.
- Stack de backend: Laravel 12 + API **exclusivamente `/api/v2`** + Sanctum +
  MariaDB (esquema único, `migrate:fresh` baseline) + Redis + Reverb +
  Storage S3 + Docker/Nginx — todos montados para servir somente a V2.
- Mobile: React Native (TypeScript) novo, com câmera via Vision Camera e OMR
  híbrido (app garante qualidade; processamento pesado no dispositivo ou backend).
- Mesmo repositório, branch `v2/mockup-canonico`; o legado é arquivado/removido.
- Web reconstruída fiel ao mockup; tokens oficiais e tema claro padrão.

## 3. Divergências encontradas

| Divergência | Resolução |
|---|---|
| Pedido citou PostgreSQL | Mantido MariaDB por decisão de direção |
| Pedido pedia `docs/V2/` com nomes em inglês | Seguido o padrão pt-br de `docs/v2/` (decisão do usuário) |
| Pedido pedia avaliar Flutter ou React Native | Decidido React Native (ADR-D015) |
| Base mobile existente era Flutter | Descartada como produto; vira referência histórica |
| R0–R7 interpretaram o mockup parcialmente | Reafirmado o mockup como contrato integral |
| Estratégia inicial reaproveitava R7 e mantinha V1 | **Revogada (ADR-D016):** produto único, sem legado nem `/api/v1` |

## 4. Riscos identificados

- **Reescrita mobile:** trocar para React Native exige novo projeto; mitigado por
  a base Flutter ser mínima e os contratos de API serem reaproveitáveis.
- **Paridade visual:** atingir as 30 telas em 9 viewports é o maior esforço;
  exige testes visuais automatizados desde V2-03.
- **OMR real:** depende de dataset rotulado e dispositivos homologados; risco de
  calibração mantido sob o gate de métricas.
- **Escopo amplo:** onboarding, agenda, integrações, plano/uso e LGPD ampliam o
  domínio; controlados pelas etapas V2-02 a V2-06.
- **Ambiente:** o shell de automação não estava disponível nesta sessão; os
  comandos Git foram entregues para execução manual (ver abaixo).

## 5. Pendências

- Executar os commits Git desta entrega (comandos prontos em
  [`19-estrategia-git.md`](19-estrategia-git.md), seção 5).
- Iniciar **V2-01** (mapa executável tela por tela) conforme o plano.
- Executar a remoção do legado (API v1, app Flutter, páginas/rotas/tabelas V1)
  conforme [`18-analise-reaproveitamento.md`](18-analise-reaproveitamento.md).
- Inicializar o projeto React Native novo (V2-07) e recriar o esquema V2 (B0).
- Revisar `09-web-design-system.md` e `docs/design/` para garantir tokens fiéis.

## 6. Arquivos gerados e alterados nesta fase

**Criados:**

- `docs/decisoes/ADR-D015-mobile-react-native.md`
- `docs/decisoes/ADR-D016-v2-sem-legado.md`
- `docs/v2/10-android-react-native.md`
- `docs/v2/17-analise-gap.md` (reescrito sem legado)
- `docs/v2/18-analise-reaproveitamento.md` (reconstrução/remoção)
- `docs/v2/19-estrategia-git.md`
- `docs/v2/20-relatorio-execucao.md`
- `docs/v2/21-plano-backend.md` (sem legado)

**Alterados (restrição sem legado):**

- `docs/v2/10-android-flutter.md` (marcado como substituído)
- `docs/v2/README.md` (status, precedência e índice)
- `docs/v2/06-arquitetura-e-reaproveitamento-v1.md` (plano de reconstrução/remoção)
- `docs/v2/07-modelagem-dados-mariadb.md` (esquema único V2)
- `docs/v2/08-api-e-integracoes.md` (somente `/api/v2`)
- `docs/v2/16-plano-executavel-v2.md` (V2-00/02 sem legado; V2-07/09 React Native)
- `README.md` (status, stack, docs, sem `/api/v1`)
- `AGENTS.md` (stack mobile React Native)

## 7. Estrutura criada

```text
docs/
|-- decisoes/
|   |-- ADR-D015-mobile-react-native.md   (novo)
|   \-- ADR-D016-v2-sem-legado.md         (novo)
\-- v2/
    |-- 10-android-react-native.md        (novo)
    |-- 17-analise-gap.md                 (reescrito)
    |-- 18-analise-reaproveitamento.md    (reescrito)
    |-- 19-estrategia-git.md              (novo)
    |-- 20-relatorio-execucao.md          (novo)
    \-- 21-plano-backend.md               (novo)
```

## 8. Status atual do projeto

- Branch ativa: `v2/mockup-canonico`.
- Documentação canônica V2 completa para iniciar a reconstrução.
- Decisões estruturais (mockup integral, MariaDB, React Native) registradas em ADR.
- Implementação de produto **não iniciada** nesta fase (é o próximo passo).

## 9. Próximos passos recomendados

1. Rodar os commits e o push da seção 5 de `19-estrategia-git.md` e criar a tag
   `v2.0.0-alpha.1`.
2. Executar **V2-01**: decompor cada tela do mockup em contrato verificável e
   criar o baseline visual nos 9 viewports.
3. Seguir o plano executável (V2-02 → V2-09), atualizando a matriz de
   rastreabilidade a cada etapa.

> Observação: os comandos Git desta fase **não foram executados automaticamente**
> (regra do `AGENTS.md` e indisponibilidade do shell nesta sessão). Eles estão
> prontos para execução manual em `19-estrategia-git.md`.
