# Aplicativo Android React Native V2

> Contrato vigente do cliente móvel da V2. Substitui
> [`10-android-flutter.md`](10-android-flutter.md), conforme
> [ADR-D015](../decisoes/ADR-D015-mobile-react-native.md).

## Objetivo

Entregar a superfície operacional Android para professores e aplicadores, com
identidade visual coerente com a aplicação web (mesmos tokens) e foco em captura
rápida, revisão confiável e sincronização segura dos cartões-resposta.

## Stack

- **React Native** com **TypeScript**.
- Navegação: React Navigation (stack + tabs).
- Estado/servidor: React Query (cache, retry, sincronização) + estado local leve.
- Câmera: `react-native-vision-camera` (frame processors, foco/exposição).
- Armazenamento seguro: Keychain/Keystore via `react-native-keychain`;
  cache offline com MMKV ou SQLite.
- Tema: tokens derivados de `docs/ui_token_gov_brasil.json`, tema claro padrão e
  escuro opcional persistido por dispositivo.

## Jornadas obrigatórias

1. Login, recuperação de sessão e seleção de contexto (núcleo/escola/turma).
2. Lista de aplicações e dashboard operacional do aplicador.
3. Lista/busca de alunos lidos e pendentes.
4. Solicitação de permissão e captura orientada pela câmera.
5. Validação de qualidade, recaptura e disparo do processamento OMR.
6. Conferência das respostas, alertas (branco, dupla, baixa confiança) e
   identificação do cartão/aluno (código impresso e código do sistema).
7. Correção manual com motivo e confirmação **idempotente**.
8. Resultado, próxima captura e histórico recente.
9. Fila offline temporária, reenvio e resolução de conflitos.

## Arquitetura

Organização por funcionalidades (feature-first), com camadas:

```text
src/
|-- core/        (config, http, auth, erros, observabilidade)
|-- design/      (tokens, tema, componentes base derivados do mockup)
|-- features/    (acesso, aplicacoes, alunos, captura, revisao, resultado, fila)
|-- data/        (clientes de API v2, repositorios, modelos, cache offline)
\-- platform/    (camera, permissoes, armazenamento seguro, ponte OMR)
```

- Cliente de API versionado (`/api/v2`), com Sanctum/token e idempotência por
  chave de confirmação.
- Pipeline de imagem isolado do thread de UI (frame processor / worklet ou
  módulo nativo) para não travar a interface.
- Observabilidade sem dados pessoais sensíveis em logs.

## Estratégia OMR no app

Híbrida, conforme `11-omr-opencv.md`:

- O app garante **qualidade da imagem** (enquadramento, foco, iluminação,
  detecção dos marcadores de referência) antes de aceitar a foto.
- O processamento OMR pesado pode rodar via **ponte nativa OpenCV** no
  dispositivo para retorno rápido, ou ser **delegado ao backend** quando o
  dispositivo for limitado. A decisão de execução não muda os contratos.
- Resultado sempre passa por **revisão humana** quando houver baixa confiança,
  branco ou dupla marcação.

## Reaproveitamento

- **Contratos de API, regras e identificadores**: reaproveitados integralmente
  da documentação V2 (não dependem da tecnologia do app).
- **Tokens de design**: portados de `docs/ui_token_gov_brasil.json` e do mockup.
- **Base Flutter (`mobile/`)**: descartada como produto; serve apenas como
  referência das jornadas já validadas (ADR-D015).

## Gate Android (React Native)

- `npm run lint` e `tsc --noEmit` sem erros;
- testes unitários e de componentes (Jest + Testing Library);
- testes de fluxo ponta a ponta (Detox) das jornadas críticas;
- APK de homologação assinado (`./gradlew assembleRelease`);
- fluxo completo validado nos dispositivos Android homologados;
- métricas de captura/OMR aprovadas (tempo, taxa de recaptura, confiança).

## Não fazer

- Confirmar leitura ambígua automaticamente.
- Persistir imagens de cartão fora da política de retenção LGPD.
- Introduzir estilos hardcoded fora dos tokens oficiais.
