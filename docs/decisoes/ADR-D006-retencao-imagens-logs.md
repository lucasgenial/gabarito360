# ADR-D006 - Retencao de imagens, exportacoes e logs

- **Status:** aceita para o MVP
- **Data:** 2026-06-10
- **Responsaveis:** Seguranca + Produto
- **Prazo:** resolvida; revisar antes da homologacao e a cada 12 meses

## Contexto

Imagens e logs apoiam auditoria e melhoria do OMR, mas criam risco de privacidade e custo quando mantidos sem prazo.

## Decisao

O MVP aplicara os seguintes prazos a partir do evento indicado:

| Classe | Prazo padrao | Inicio da contagem |
|---|---:|---|
| Imagem original de leitura confirmada | 180 dias | Finalizacao da aplicacao |
| Imagem original de tentativa nao confirmada | 30 dias | Captura |
| Imagem processada e artefatos de diagnostico | 30 dias | Processamento |
| Exportacao gerada | 7 dias | Geracao |
| Logs tecnicos de aplicacao | 90 dias | Evento |
| Logs de sincronizacao mobile | 180 dias | Processamento |
| Auditorias de negocio e seguranca | 5 anos | Evento |

A imagem original e obrigatoria no fluxo online do MVP para rastreabilidade e avaliacao do OMR. O acesso deve ser privado, autorizado e auditado.

Retencao legal ou investigacao formal pode suspender o descarte por prazo documentado. Ao fim do prazo, o descarte deve ser automatico e auditado, sem manter copias acessiveis.

## Justificativa

Os prazos equilibram suporte ao piloto, contestacao, seguranca e minimizacao de dados.

## Impactos

- `arquivos.retencao_ate` deve ser preenchido por classificacao.
- Jobs de descarte e testes de restauracao devem respeitar a politica.
- Logs nao podem conter imagens, tokens, senhas ou dados pessoais desnecessarios.
