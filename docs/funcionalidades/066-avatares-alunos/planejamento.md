# 066 — Avatar no lugar de foto de aluno — Planejamento

**Implementação:** Parcial
**Etapa:** Em planejamento
**Prioridade no plano mestre:** Onda 3 (proposta), dentro do "pacote de retenção" junto com FN-062, FN-064 e o expurgo de FN-004; pendência única no §3: "Autorização do passo 6"
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/28 Fase 2, pacote L6; requisito RL19 do docs/33 (decisão "sem foto de aluno")
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Cumprir a minimização de dados representando o aluno por avatar (iniciais ou uma de 36 figuras aprovadas) em vez de foto. Atende alunos, escolas e professores que cadastram ou visualizam alunos.

## 2. Escopo
- **Inclui:** componente de avatar com iniciais e cor estável derivada do ID; galeria de 36 personagens em pixel art (aprovada em 31/10/2026); escolha no cadastro e na edição; retorno às iniciais; `alunos.avatar_id` (1 a 36 ou nulo); uso do avatar em cadastro, lista, detalhe, turma e painéis; levantamento das fotos existentes; passo 6 (apagar arquivos e remover `alunos.foto_path`) mediante aprovação.
- **Não inclui:** imagem de perfil de usuários e professores (FN-019, IM1); imagens de turma e educadores (FN-037); fotos de cartões-resposta, que são documento da correção (FN-045/046); retenção geral (FN-062).

## 3. Fontes de verdade
[docs/28 L6](../../28-plano-mestre-proximos-modulos.md), [docs/33 §6](../../33-lgpd-privacidade-e-consentimento.md) (RL19), [docs/operacao/avatares-alunos.md](../../operacao/avatares-alunos.md), [docs/135](../../135-pf1-c-perfis-escolares.md) e [docs/136](../../136-im1-preparo-fotos.md) (retrato de aluno autorizado depois), [docs/03](../../03-perfis-e-permissoes.md) (nota PF1-C). Mockups: cabeçalho do aluno com "Avatar iniciais" ([docs/01](../../01-inventario-dos-mockups.md)). Código: migration `2026_10_31_000005_adicionar_avatar_alunos.php`; componentes web `avatar.blade.php` e `seletor-avatar.blade.php`; `Api/AlunoController` (store/update com `avatar_id`); `Api/ImagemAlunoController`; web `AlunoController::imagem` e `foto`. Casos de uso: [casos-de-uso.md](casos-de-uso.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-002 B1 backup | Passo 6 só depois de backup verificado | Concluída |
| Decisão §4 item 11 | Autorizar o passo 6 (eliminar fotos e coluna) | Pendente |

**Funcionalidades que dependem desta:** FN-067 portal (risco R04 do docs/38 depende de L6/A5); FN-062 (inventário pós-L6).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 6.1 (L6 passos 1–3) | Retirar envio de foto; avatar de iniciais; avatar em todas as telas | Nenhuma tela ou API aceita foto; todas mostram avatar | Entregue (produção); ⚠ ver 6.4 | PR #70, commit `dc9949e` |
| 6.2 (L6 passo 4) | Galeria de 36 figuras, escolha e retorno às iniciais | API aceita 1–36 ou nulo | Entregue (produção) | PR #70; docs/operacao/avatares-alunos.md |
| 6.3 (L6 passo 5) | Levantamento das fotos existentes | Inventário agregado em produção | Entregue (produção): 0 fotos, 0 arquivos em 31/10/2026 | docs/operacao/avatares-alunos.md |
| 6.4 (L6 passo 6) | Apagar arquivos e remover `foto_path` | Backup, nova conferência e aprovação | Pendente, não autorizado | §4 item 11 |

## 6. Contrato técnico (proporcional ao risco)
- `alunos.avatar_id` `unsignedTinyInteger` nulo; API valida `between:1,36`.
- `foto_path` não aceita atribuição em massa e é proibido nos formulários (`prohibited`).
- ⚠ Estado atual do código (PF1-C/IM1, só na homologação): `POST /v1/alunos/{id}/imagem` aceita `tipo=foto` com arquivo reprocessado em WebP 384×384 no disco local `alunos/fotos`, e `POST /v1/alunos` aceita o campo `foto`; leitura privada em `GET /v1/alunos/{id}/foto` com `no-store`. Isso contraria o passo 4 do L6 ("nunca aceitar imagem enviada") e o RL19, e torna a coluna `foto_path` novamente usada. Ver §8.

## 7. Interface
Seletor com 37 opções (iniciais e 36 figuras), prévia imediata, persistência só ao salvar, acessível por teclado; cores pelos tokens dos temas claro e escuro. Conferência de 31/10/2026 registrada no docs/operacao/avatares-alunos.md. ⚠ Esse documento ainda referencia prints em `docs/evidencias/l6-2026-10-31/`, removidos em 09/11 pela regra sem prints; a referência deve sair do documento operacional (fora do escopo desta pasta).

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 11 — passo 6 do L6 | Autorizar eliminação de fotos e coluna; manter | Aprovar no pacote único de retenção (Onda 3) | Pendente |
| Divergência plano mestre × código: retrato de aluno | (a) Manter o retrato privado autorizado no PF1-C e rever RL19/L6 e o passo 6; (b) retirar o upload de foto de aluno e voltar ao L6 | Sem recomendação: decisão do responsável. O plano mestre registra MP-035a como "substituída por avatares (FN-066)", mas docs/135 e docs/03 registram autorização expressa do retrato de aluno em 03–04/11 | Pendente (não registrada no §4) |
| Promoção do PF1-C à produção | Hoje produção segue sem upload de foto de aluno | Decidir junto com o item acima antes da promoção do bloco FN-014/019 (§7) | Pendente (§4 item 2) |

## 9. Ramos desta entrega
Nenhum aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
