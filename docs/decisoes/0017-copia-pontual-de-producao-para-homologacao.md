---
id: ADR-0017
titulo: Cópia pontual de dados de produção para a homologação
estado: Aceita
substituida_por: []
data: 2026-11-03
afeta_funcionalidades: [FN-003]
afeta_caminhos: []
verificacao: processo
regras: []
---
# ADR-0017 — O responsável autorizou copiar os usuários e dados atuais de produção para a homologação, mantendo as senhas

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-03
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** docs/127-h1-homologacao-isolada.md, seção "Revisão autorizada em 03/11/2026"; AGENTS.md, seção "Continuidade e publicação"; docs/128 e docs/129
**Afeta:** [FN-003](../funcionalidades/003-publicacao-e-homologacao/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

Após a instalação inicial com 9 usuários, 20 alunos e 2 gabaritos fictícios, o responsável pediu os mesmos usuários e dados existentes em produção. A instrução substituiu a restrição inicial a dados fictícios.

## Decisão

Posteriormente o responsável "autorizou expressamente copiar os usuários e dados atuais de produção para homologação, mantendo suas senhas de acesso."

Regras da cópia:

1. A cópia é pontual, protegida e independente.
2. Use um snapshot somente de leitura da origem, um banco alternativo novo na VM e uma cópia privada dos arquivos.
3. Verifique a integridade por contagens agregadas antes da troca.
4. Preserve a origem. Preserve a base fictícia anterior para retorno.
5. Preserve os hashes de senha e os índices de busca.
6. Proteja as chaves de leitura necessárias aos dados cifrados. A homologação recebe uma nova chave corrente.
7. Descarte na cópia as sessões, os tokens, a recuperação de senha e os jobs pendentes.
8. Mantenha o histórico acadêmico e a auditoria.

## Consequências

- É proibido compartilhar o banco operacional.
- É proibido enviar e-mails ou pagamentos reais, reaproveitar sessões, tokens de acesso ou trabalhos pendentes.
- É proibido copiar o `.env` de produção de forma indiscriminada. SMTP, pagamentos, URLs, conexão de banco, Redis e sessões continuam próprios.
- A agenda da homologação fica desativada para não executar cobranças e rotinas herdadas.
- Não há sincronização automática nem alteração da produção. Mantenha a proteção adicional da homologação.

## Verificação

- Confira o recibo da cópia (docs/128) e o da release com carga nacional (docs/129). Os dados e recibos privados ficam fora do Git.
- A homologação exige acesso protegido adicional.
