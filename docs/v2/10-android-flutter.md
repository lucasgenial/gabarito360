# Aplicativo Android Flutter V2

> **DOCUMENTO SUBSTITUÍDO (histórico).** A V2 adotou **React Native** como
> tecnologia oficial do aplicativo móvel. Consulte o contrato vigente em
> [`10-android-react-native.md`](10-android-react-native.md) e a decisão em
> [ADR-D015](../decisoes/ADR-D015-mobile-react-native.md). O conteúdo abaixo é
> mantido apenas como referência das jornadas já mapeadas.

## Objetivo

Entregar a superfície operacional Android para professores e aplicadores,
com identidade coerente com a aplicação web e foco em captura rápida, revisão
confiável e sincronização segura.

## Jornadas obrigatórias

1. Login, recuperação de sessão e seleção de contexto.
2. Lista de aplicações e dashboard operacional.
3. Lista/busca de alunos lidos e pendentes.
4. Solicitação de permissão e captura orientada pela câmera.
5. Validação de qualidade, recaptura e processamento OMR.
6. Conferência das respostas, alertas e identificação do cartão/aluno.
7. Correção manual com motivo e confirmação idempotente.
8. Resultado, próxima captura e histórico recente.
9. Fila offline temporária, reenvio e resolução de conflitos.

## Arquitetura

- Flutter com camadas `core`, `design`, `features`, `data` e `platform`.
- Cliente API versionado, armazenamento seguro e observabilidade sem dados sensíveis.
- Isolamento do pipeline de imagem para não bloquear a interface.
- Tokens derivados das fontes oficiais e tema claro inicial.

## Reaproveitamento

Reutilizar tema, tokens, cliente API e base Android existentes. Reconstruir
`main.dart` e jornadas para uma arquitetura por funcionalidades; adicionar
câmera, permissões, OMR, fila offline e testes de dispositivo.

## Gate Android

`flutter analyze`, testes unitários/widgets/integrados, APK assinado de
homologação, fluxo completo em dispositivos definidos e métricas de captura/OMR
aprovadas.
