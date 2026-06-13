# ADR-D015 - React Native como tecnologia oficial do aplicativo móvel da V2

- **Status:** aceita
- **Data:** 2026-06-13
- **Branch:** `v2/mockup-canonico`
- **Substitui parcialmente:** ADR-D014 (item 7, no ponto que assumia Flutter como base mobile)
- **Impacta:** `docs/v2/10-android-react-native.md`, `docs/v2/06-arquitetura-e-reaproveitamento-v1.md`, `docs/v2/16-plano-executavel-v2.md`, `README.md`, `AGENTS.md`

## Contexto

A documentação V2 e a base R6 assumiam **Flutter** como tecnologia do aplicativo
Android. Durante o replanejamento da V2 (versão orientada integralmente pelo
mockup `style-system/`), a direção do produto definiu **React Native** como a
tecnologia oficial do cliente móvel.

A base Flutter existente (`mobile/`) entrega apenas login, listas e um snapshot
operacional; as jornadas reais de captura, OMR, revisão, confirmação idempotente
e fila offline ainda precisam ser construídas. Ou seja, o trabalho mobile pesado
da V2 ainda não foi feito, o que reduz o custo de troca de tecnologia agora,
antes de V2-07.

## Decisão

1. O aplicativo móvel oficial da V2 será desenvolvido em **React Native**
   (com TypeScript), substituindo o plano anterior baseado em Flutter.
2. O alvo permanece **Android** como plataforma prioritária, mas o React Native
   preserva o caminho para iOS futuro sem reescrita do núcleo de jornadas.
3. A base Flutter em `mobile/` é **descartada como produto** e mantida apenas
   como referência histórica de jornadas e contratos de API já validados. Não
   será evoluída.
4. Os **contratos** continuam estáveis: API `/api/v2`, modelo de dados MariaDB,
   regras de negócio, idempotência e o pipeline OMR OpenCV não mudam por causa
   desta decisão. Muda apenas a camada de cliente móvel.
5. O contrato mobile da V2 passa a ser `docs/v2/10-android-react-native.md`. O
   documento `docs/v2/10-android-flutter.md` fica como histórico marcado como
   substituído.

## Justificativa técnica

A escolha de React Native sobre Flutter, neste projeto e neste momento,
sustenta-se em:

- **Continuidade de stack e tooling.** O painel web da V2 usa Tailwind e um
  design system derivado de tokens em `docs/ui_token_gov_brasil.json`. Com React
  Native + TypeScript, os mesmos tokens de cor, tipografia e espaçamento podem
  ser portados para um tema compartilhado, e a equipe trabalha em um único
  ecossistema JavaScript/TypeScript em vez de manter Dart em paralelo.
- **Ecossistema de câmera e visão.** O fluxo central do app é captura orientada
  de cartão-resposta. `react-native-vision-camera` oferece frame processors,
  controle de foco/exposição e integração madura com bibliotecas nativas,
  cobrindo bem a etapa de qualidade de imagem antes do OMR.
- **Integração OpenCV.** O OMR é executado preferencialmente como pipeline
  híbrido (retorno rápido no app, validação/reprocessamento no backend). Tanto
  o caminho de bridge nativo (JNI/C++) quanto o de delegar o processamento
  pesado ao backend são bem suportados em React Native, sem prender a decisão
  ao runtime do app.
- **Reuso de conhecimento e contratação.** O time já mantém JavaScript/TypeScript
  no front web; padronizar o mobile no mesmo idioma reduz custo cognitivo,
  facilita revisão cruzada de código e amplia o mercado de contratação.
- **Custo de troca baixo agora.** Como apenas login/listas/snapshot existem em
  Flutter, trocar antes de V2-07 evita reescrever um app já maduro. As jornadas
  ainda não construídas seriam o grosso do esforço em qualquer tecnologia.

## Trade-offs e mitigação

- **Perda da base Flutter.** Mitigado pelo fato de a base ser mínima e pelos
  contratos de API já documentados, que são reaproveitáveis diretamente.
- **Performance de processamento de imagem no dispositivo.** Mitigado pela
  estratégia híbrida: o app garante qualidade e envia ao backend; o OMR pesado
  pode rodar no servidor quando o dispositivo for limitado.
- **Fragmentação de dependências nativas.** Mitigado fixando versões, usando
  bibliotecas mantidas (Vision Camera, MMKV, etc.) e validando nos dispositivos
  homologados antes do gate.

## Consequências

- `docs/v2/10-android-react-native.md` torna-se o contrato mobile vigente.
- A etapa **V2-07** do plano executável passa a ter comandos e gates de React
  Native (`npm`, Metro, Gradle/`assembleRelease`, Detox/Jest), não mais Flutter.
- A matriz de reaproveitamento reclassifica a base Flutter de "reaproveitar
  fundação" para "descartar (referência histórica)".
- README, AGENTS e o índice `docs/v2/README.md` passam a citar React Native.
- O diretório `mobile/` atual (Flutter) deve ser substituído por um projeto
  React Native em etapa dedicada; até lá, permanece marcado como legado.

## Alternativas rejeitadas

- **Manter Flutter** apenas para preservar a base mínima existente: o ganho é
  pequeno e contraria a direção de stack unificada em TypeScript.
- **Kotlin/Android nativo puro**: maior fidelidade de plataforma, mas fecha o
  caminho multiplataforma e duplica o esforço de UI frente ao web.
- **PWA/Capacitor**: insuficiente para o controle fino de câmera e para o
  desempenho de captura exigido pelo fluxo de OMR.
