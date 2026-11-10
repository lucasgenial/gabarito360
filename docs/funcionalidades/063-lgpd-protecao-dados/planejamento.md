# 063 — Proteção dos dados (cifragem) — Planejamento

**Estado no tronco:** Concluída
**Prioridade no plano mestre:** sem onda (entregue em produção); nenhuma pendência no §3
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/28 Fase 2, pacote L3; requisitos RL8 e RL9 do docs/33
**Atualizado em:** 2026-11-09, por agente de documentação

## 1. Objetivo
Reduzir o dano de um acesso indevido ao banco, à fila ou aos logs: CPF e contatos cifrados com busca por hash, e-mails cifrados na fila, logs sem conteúdo livre e respostas sem cache nem referrer. Atende titulares e instituições.

## 2. Escopo
- **Inclui (entregue):** cifra autenticada com prefixo `g360:v1:` em `usuarios.email`, `usuarios.cpf`, `usuarios.telefone`, `alunos.cpf`, `leads.email`, `leads.telefone`, `escolas.email`, `escolas.telefone` e `trocas_email.novo_email`; índices HMAC-SHA256 com chave dedicada `DADOS_HASH_KEY` para pesquisa exata e unicidade; links de recuperação com conta opaca; e-mails cifrados enquanto enfileirados; logs de API e web com lista positiva; cabeçalhos `Referrer-Policy: no-referrer` e `Cache-Control: private, no-store`; logs do Nginx protegidos em produção.
- **Não inclui:** nomes, matrículas, notas e datas de nascimento (sujeitos a escopo e minimização); contatos dos responsáveis (FN-035) e código do portal (FN-067), que devem usar proteção própria quando existirem; retenção e descarte de logs históricos (FN-062); fotos de alunos (FN-066).

## 3. Fontes de verdade
[docs/28 L3](../../28-plano-mestre-proximos-modulos.md), [docs/33 §6](../../33-lgpd-privacidade-e-consentimento.md), [docs/operacao/protecao-dados.md](../../operacao/protecao-dados.md). Código: `apps/api/app/Casts/DadoProtegido.php`, `apps/api/app/Support/DadoProtegido.php` (hash por campo), `apps/api/app/Logging/` e `apps/web/app/Logging/` (`ProtegerDados`, `GerenciadorProtegido`), middlewares `ProtegerRespostas` e `RecusarCredenciaisNaUrl` na API e na web. Casos de uso: [casos-de-uso.md](casos-de-uso.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-002 B1 backup verificado | Restauração com chaves antes da migração | Concluída |

**Funcionalidades que dependem desta:** FN-064 (hash `DadoProtegido` em idempotência e assinaturas de exportação); FN-035 (contatos dos responsáveis cifrados); FN-067 (código do portal cifrado, RL8); FN-016 (índices de CPF; risco do hash dependente de máscara registrado em FN-016).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 3.1 (L3 passo 1) | Cifra de CPF e contatos com busca por hash | Campos convertidos; login e unicidade pelo índice | Entregue (produção) | PR #69, commit `90b7d3e`; docs/operacao/protecao-dados.md |
| 3.2 (L3 passo 2) | Logs sem dados pessoais (API, web, fila, Nginx) | Testes com marcadores fictícios ausentes dos logs | Entregue (produção) | PR #69; registro de 31/10/2026 |
| 3.3 (L3 passo 3) | Dados pessoais fora de URLs e e-mails | Recuperação sem e-mail na URL; CPF fora de modelos novos | Entregue (produção) | PR #69 |
| 3.4 (L3 passo 4) | Restauração com chaves | Restauração isolada conferida | Entregue (produção) | docs/operacao/protecao-dados.md |

## 6. Contrato técnico (proporcional ao risco)
- `DADOS_HASH_KEY` é estável e separada da `APP_KEY`; sua troca exige reindexação explícita. Sem fallback para `APP_KEY` fora de `testing`.
- `migrate:rollback` não descriptografa; retorno exige restauração consistente de banco e chaves.
- Novos campos pessoais que precisem de cifra usam o cast `DadoProtegido`; novos eventos de log entram na lista positiva sem dados pessoais.

## 7. Interface
Sem mudança visual.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| Logs históricos, caixas de e-mail e backups em texto simples anteriores a L3 | Descartar pelo pacote de retenção | Tratar em FN-062 (§4 item 11) | Pendente (fora desta FN) |

## 9. Ramos desta entrega
Nenhum.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | agente de documentação |
