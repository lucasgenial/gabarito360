# 013 — Acessos internos (Desenvolvedor, Suporte, Financeiro) — Planejamento

**Implementação:** Concluída
**Etapa:** Concluída
**Conforme princípios:** versão 1
**Prioridade no plano mestre:** sem posição nas ondas do §5 (concluída). Nenhuma pendência funcional; a recuperação do Desenvolvedor segue manual.
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/28 (A1), docs/66–69, docs/100, docs/102
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Dar à equipe da plataforma (Desenvolvedor, Suporte e Financeiro) um contexto de trabalho separado dos vínculos escolares e do cliente pagador, com permissões próprias, vigência, suspensão e trilha de eventos, sem acesso a dados pedagógicos.

## 2. Escopo
- **Inclui:** tabela de atribuições internas com vigência, situação e versão; escolha explícita do acesso interno depois do login; painel interno sem dados pedagógicos; gestão de Suporte e Financeiro pelo Desenvolvedor, com motivo e eventos; bootstrap do primeiro Desenvolvedor por comando.
- **Não inclui:**
  - Cadastro de clientes (usa o contexto interno): FN-023.
  - Atendimento do catálogo de instituições: FN-031.
  - Pedidos de titulares: FN-064.
  - Exceção de grupos funcionais concedida por Suporte/Desenvolvedor: FN-014.
  - "Ver como" e atendimento com acesso pedagógico auditado: FN-005.
  - Painel técnico: FN-006.

## 3. Fontes de verdade
- Mockups: sem mockup próprio; telas aprovadas como extensão em docs/67 (`docs/67-a1-planejamento-e-acessos-internos.md`)–69 (`docs/69-a1-b2-gestao-interna.md`).
- Origem: docs/66 (`docs/66-t4-a-base-cadastro-clientes.md`), docs/67 (`docs/67-a1-planejamento-e-acessos-internos.md`), docs/68 (`docs/68-a1-b-contextos-internos.md`), docs/69 (`docs/69-a1-b2-gestao-interna.md`), docs/100 (`docs/100-publicacao-inicial-v1-t5.md`), docs/102 (`docs/102-planejamento-l4-t6.md`); docs/03 extensão A1 (`docs/03-perfis-e-permissoes.md`).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Código: `apps/api/app/Support/ContextoInterno.php`, `TokenInterno.php`, `apps/api/app/Services/GestaoAcessosInternos.php`, `apps/api/app/Http/Controllers/Api/SelecaoInternaController.php`, `GestaoAcessoInternoController.php`, `apps/api/app/Console/Commands/IniciarDesenvolvedor.php`, `apps/web/app/Http/Controllers/AcessoInternoController.php`, `apps/web/app/Http/Controllers/Auth/VinculoController.php`.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-011 autenticacao-conta | Sessão de identidade de origem do token interno | Parcial |
| FN-015 vinculos-contexto | Acesso interno só existe com o regime de vínculos obrigatório e usa a mesma tela de escolha | Concluída |
| FN-023 cadastro-clientes (T4-A) | Base de clientes separada da matriz pedagógica | Concluída |
| FN-004 auditoria | Captura do contexto interno nas requisições | Parcial |

**Funcionalidades que dependem desta:** FN-005, FN-006, FN-014 (exceção funcional), FN-023, FN-031 (atendimento), FN-064 (atendimento de pedidos).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (A1-A) | Tabela `usuario_acessos_internos`, contexto exclusivo, revogação | Vigência, situação e versão conferidas a cada pedido | Entregue (produção) | PR #101 |
| 1.2 (A1-B1) | Token interno ligado à sessão de identidade; seleção explícita; painel interno sem dados pedagógicos | Token interno inválido se a sessão de origem acabar | Entregue (produção) | PR #102 |
| 1.3 (A1-B2) | Gestão auditada de Suporte/Financeiro pelo Desenvolvedor; `g360:iniciar-desenvolvedor` | Eventos com motivo; sem autoelevação | Entregue (produção) | PR #103 |
| 1.4 | Contas internas com CPF fictício | Contas de operação criadas sem dado pessoal real | Entregue | docs/100, 102 |

## 6. Contrato técnico (proporcional ao risco)
- **Flags:** `ACESSOS_INTERNOS_ATIVOS` (`config/acessos_internos.php`) e `VINCULOS_OBRIGATORIOS`; sem as duas, as rotas internas respondem 404/403.
- **Permissões internas (fixas no código):** Desenvolvedor `cliente.ver`, `cliente.gerir`, `acesso_interno.gerir`, `usuario.excecao_funcional.gerir`; Suporte as mesmas sem `acesso_interno.gerir`; Financeiro `cliente.ver`, `cliente.gerir`. Acréscimos condicionados a flags: `catalogo.solicitacao.analisar` e `catalogo.manter_global` (catálogo) e `pedido.atender`/`pedido.abrir` (pedidos de titulares).
- **Isolamento:** em contexto interno, a API só aceita `auth/me`, `auth/imagem`, `auth/foto`, `auth/email` e os prefixos internos (`acessos-internos`, `clientes`, `catalogo/atendimento`, `pedidos-titulares`, `configuracoes/funcoes`); rotas escolares e respostas a convites são recusadas.
- **Token:** `interno-contexto` com habilidades `interno:{id}`, `versao:{n}`, `identidade:{token de origem}`; muda a versão → token inválido.
- **API:** `GET auth/acessos-internos`, `POST auth/acessos-internos/selecionar` (20/min); `GET|POST acessos-internos`, `PUT acessos-internos/{id}`.
- **Concorrência:** linha de controle `acessos_internos_controle` bloqueada em toda alteração; versão otimista (409).

## 7. Interface
Escolha do acesso na mesma tela de vínculos (`auth/vinculo`); painel `dashboard/interno`; gestão em `internos/index` (lista paginada de 20). Mensagens de erro curtas, sem revelar se a conta existe. Conferência por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| Recuperação do Desenvolvedor | Manter manual / criar fluxo | Plano mestre: segue manual, sem pendência funcional | — |
| §4 item 9 — D9 ("ver como" só leitura) | Afeta a evolução do Suporte em FN-005, não esta funcionalidade | Responder D9 antes de FN-005 | Pendente |

Nenhuma divergência entre o plano mestre e o código encontrada.

## 9. Ramos desta entrega
Nenhum aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | documento criado a partir do inventário e do código | lucasgenial |
