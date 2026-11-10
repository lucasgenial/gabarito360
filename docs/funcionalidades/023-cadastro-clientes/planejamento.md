# 023 — Cadastro de clientes (T4) — Planejamento

**Estado no tronco:** Concluída
**Prioridade no plano mestre:** sem posição nas ondas do §5 (concluída). Contratos completos remetidos a FN-068 (Onda 4).
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/28 Fase 3 (T4); docs/66, 71. Ao ser implementada revelou a dependência de A1 (FN-013), antecipado.
**Atualizado em:** 2026-11-09, por agente de documentação

## 1. Objetivo
Dar à equipe interna (Desenvolvedor, Suporte, Financeiro) um cadastro comercial dos clientes — quem contrata — com dados de contato protegidos, histórico de modalidade e consulta dos espaços de cada cliente, sem conceder acesso pedagógico.

## 2. Escopo
- **Inclui:** modelo `Cliente` (tipos professor, escola, núcleo, rede, secretaria, governo; modalidades pagante, presenteado, teste, interno); documento e contatos cifrados (L3) com índice HMAC do documento; `cliente_eventos` transacional; API paginada e telas de lista, cadastro, edição e detalhe; consulta paginada dos espaços do cliente com contagem de vínculos por situação; referência contratual como texto.
- **Não inclui:**
  - Contratos, cláusulas, faturas e cobrança (F1–F3) → FN-068.
  - Situação do cliente, suspensão e encerramento (T6) → FN-028.
  - Criação, transferência ou alteração de espaços e vínculos pela interface de clientes (docs/71 item 3) → sem dono no tronco; ver §8.
  - Auditoria geral de requisições → FN-004 (AU1 entregue).
  - Atendimento com acesso a dados pedagógicos (AU3) → FN-005.
  - Escola contratante direta (adesão, provisionamento) → FN-029.
  - Autocadastro e checkout → FN-030 (substituído por contratação por contato).

## 3. Fontes de verdade
- Documentos de origem: [docs/66](../../66-t4-a-base-cadastro-clientes.md), [docs/71](../../71-t4-b-interface-clientes.md); dependência A1: docs/67–70 (FN-013).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md) (tabela de acessos internos: Desenvolvedor, Suporte e Financeiro consultam e gerem clientes).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md). Sem UC correspondente no docs/07.
- Sem mockup: telas internas seguem o style system.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-021 clientes-espacos | Tabelas `clientes`/`espacos` | Concluída |
| FN-013 acessos-internos | Contexto interno e permissões `cliente.ver`/`cliente.gerir` | Concluída |
| FN-063 lgpd-protecao-dados | Cifra (L3) e índice HMAC | Concluída |
| FN-004 auditoria | AU1 para trilha geral | Parcial (AU1 entregue) |

**Funcionalidades que dependem desta:** FN-068 (contratos sobre o cliente), FN-028 (encerramento por cliente), FN-029 (provisionamento de cliente da escola autônoma, EI1-F), FN-064 (pedidos de titulares por cliente).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (T4-A) Persistência comercial | Migration `2026_11_01_000007_preparar_cadastro_clientes`; `Cliente` cifrado; `CadastroCliente`; eventos | `CadastroClienteTest` no CI MariaDB | Entregue (produção 02/11) | PR #100 (`a161e87`); docs/66 |
| 1.2 (T4-B) API e interface interna | `ClienteController` API e web; menu Clientes no contexto interno | 17 testes API / 11 web; ensaio Chrome | Entregue (produção 02/11) | PR #105 (`b441b91`); docs/71; docs/100 |
| 1.3 Contratos completos | Contrato, cláusulas, vigência | Ver FN-068 | Pendente (→ FN-068) | §3 do plano mestre |

## 6. Contrato técnico
- **API (contexto interno):** `GET/POST /api/v1/clientes`, `GET/PUT /api/v1/clientes/{id}`, `GET /api/v1/clientes/{id}/espacos`. Autorização: `TokenInterno::habilitado()` e `ContextoInterno::pode(..., 'cliente.ver' | 'cliente.gerir')`; serviço revalida `Gate gerir-clientes` e conta ativa dentro da transação. Throttle 20/min nas gravações.
- **Validação fechada:** campos `tipo`, `nome`, `documento`, `modalidade`, `contato_nome`, `contato_email`, `contato_telefone`, `contrato_referencia`; extras → `422 'O cadastro contém campos não permitidos.'`. Situação inicial sempre `ativo`; não aceita situação, espaço, vínculo ou autor.
- **Projeções:** lista sem documento/contatos (`id, nome, tipo, modalidade, situacao`), paginada de 20, busca por nome e filtros por tipo/modalidade; detalhe com campos de edição; hash nunca exposto. Espaços: `id, nome, tipo, situacao` + contagem de vínculos por situação, sem identidade de usuários.
- **Eventos:** `cliente_eventos` (`cadastro`, `cadastro_atualizado`, `modalidade_alterada`) na mesma transação; sem documento, contatos ou contrato no histórico.
- **Desempenho:** volume baixo (dezenas de clientes); medição com 1000 usuários não se aplica ao contexto interno, mas segue FN-077 se a regra exigir.

## 7. Interface
Rotas web `/app/clientes`, `/app/clientes/novo`, `/app/clientes/{id}`; item de menu "Clientes" (grupo equipe) para quem tem `cliente.ver`. Formulário único para cadastro/detalhe, com tabela paginada dos espaços. Conferência registrada por texto no docs/71 e PR #105.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 5 — autocadastro e checkout substituídos por contratação assistida; registrar no docs/06 | Declarar substituído / retomar autocadastro | A do plano mestre: declarar substituído; preços em FN-068 | Pendente |
| §4 item 14 — condições de lançamento comercial e regras financeiras de adesão | — | Tratar em FN-068 | Pendente |
| Provisionamento de espaços (rede, pessoal, escola sem núcleo) não tem caminho na aplicação: T4-B só consulta espaços. Novos clientes cadastrados aqui não ganham espaço nem vínculo | Subfase de FN-023, parte de FN-029 (EI1-F) ou candidata nova | Levar ao responsável; o plano mestre só lista "contratos completos" como pendência | Pendente |
| Situação do cliente sem ciclo (sempre `ativo`) | — | T6 (FN-028) | Pendente |

## 9. Ramos desta entrega
- A1 (contexto interno) antecipado como dependência: virou FN-013.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | agente de documentação |
