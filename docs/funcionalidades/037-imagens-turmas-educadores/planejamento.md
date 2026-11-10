# 037 — Imagens de turmas e educadores — Planejamento

**Implementação:** Parcial
**Etapa:** Em planejamento
**Conforme princípios:** versão 1
**Prioridade no plano mestre:** sem onda própria; IMG-N e IMG-P listados como órfãos (§3); entregues na fila de produção §7 ("Organização da turma e imagens", PR #208, #209)
**Natureza:** existente documentada a partir do código
**Origem:** ramo do TU1-R3 (docs/tu-img-avatar-foto.md), registrado como funcionalidade; docs/176, docs/177 (P3.1.3)
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Dar identidade visual às turmas (brasões ou foto) e aos educadores (avatares adultos ou foto), separada dos avatares de alunos, com edição só por quem gerencia o recurso e preservação da imagem anterior em caso de falha.

## 2. Escopo
- **Inclui:** catálogo de 36 avatares de educadores e 36 brasões (atlas 6×6, sprites WebP v2); imagem privada e versionada da turma; imagem no detalhe e no Editar turma (IMG-E); imagem na lista de turmas (IMG-L); imagem ao criar turma (IMG-N, pendente); imagem no cadastro de professor (IMG-P, pendente).
- **Não inclui:**
  - Avatares de alunos e eliminação de fotos (L6) → FN-066.
  - Imagem de perfil do próprio usuário (IM1) → FN-019 (usa o mesmo catálogo de educadores).
  - Logo de escola e de rede → FN-033.

## 3. Fontes de verdade
- Documentos de origem: tu-img-avatar-foto (`docs/tu-img-avatar-foto.md`), tu1-r3-dashboard-analitico (`docs/tu1-r3-dashboard-analitico.md`) (última seção), docs/176 (`docs/176-reconciliacao-solicitacoes.md`), docs/177 (`docs/177-plano-paineis-turmas.md`) P3.1.3.
- Assets: `apps/web/public/images/avatares/` (educadores, brasões, estudantes); [assets.json](assets.json).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: docs/03 (`docs/03-perfis-e-permissoes.md`).
- Plano mestre: [§2 FN-037](../../planejamento/00-plano-mestre.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-036 organizacao-turma | Detalhe e edição da turma onde a imagem aparece | Em homologação |
| FN-019 perfil-configuracoes | Preparo de foto (arquivo/câmera/recorte/compactação) reaproveitado | Concluída |
| FN-066 avatares-alunos | Precursor (catálogo de estudantes) | Parcial |
| FN-077 desempenho (F3) | Sprites WebP v2 | Parcial |

**Funcionalidades que dependem desta:** FN-053 (IMG-L no resumo cadastral dos painéis, P3.1).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (TU-IMG 1–2) | Catálogos de 36 educadores e 36 brasões | Atlas único por catálogo | Entregue (homologação) | PR #208 |
| 1.2 (TU-IMG 3) | API aditiva com imagem privada, versionada e auditada | Autorização por alcance/perfil | Entregue (homologação) | PR #208; `ImagemTurmaController` |
| 1.3 (TU-IMG 4) | Interface desktop/celular | Edição a partir da imagem | Entregue (homologação) | PR #208, #209 |
| 1.4 (IMG-E) | Imagem no Editar turma | — | Entregue (homologação) | PR #209 |
| 1.5 (IMG-L) | Imagem na lista de turmas | `<x-avatar-turma>` | Entregue (homologação) | PR #215 |
| 1.6 | Sprites WebP v2 | — | Entregue (homologação) | PR #233 |
| 1.7 (IMG-N) | Imagem ao criar turma | Escolha no formulário de criação | Pendente | formulário em `turmas/index` sem imagem |
| 1.8 (IMG-P) | Imagem no cadastro de professor | Escolha no cadastro de membro | Pendente | `membros/create` só mostra iniciais |

## 6. Contrato técnico (proporcional ao risco)
- **API:** `POST /v1/turmas/{id}/imagem` (`turma.gerir`, limite `imagem-perfil`): `tipo` = `foto` | `avatar`, `avatar_id` 1–36, `foto` conforme `ImagemPerfil::REGRA`; `GET /v1/turmas/{id}/foto` (`turma.ver`). Usuário: `POST /v1/auth/imagem`, `GET /v1/auth/foto`, `GET /v1/usuarios/{id}/foto` (próprio ou `usuario.ver` com vínculo consultável).
- **Modelo:** três campos com defaults compatíveis em `turmas` (`foto_path`, avatar, `imagem_versao`), sem backfill nem exclusão.
- **Regras:** o escopo é resolvido antes de validar o arquivo; turma arquivada continua com imagem editável (metadado); a versão para em 4294967295 (409 "Atualização da imagem indisponível."); arquivo antigo removido só após sucesso.
- IMG-N/IMG-P (proposto): reaproveitar o mesmo endpoint após a criação, ou aceitar a imagem no próprio cadastro, sem duplicar o preparo de foto.

## 7. Interface
- Imagem junto ao título da turma; edição só para quem gerencia; mesma escolha avatar/foto do perfil; cancelar e falhar preservam a anterior.
- Lista de turmas com avatar (IMG-L). Formulários de criação de turma e de professor ainda sem escolha de imagem.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 2 — gate de produção e fila acumulada (PR #208, #209, #215, #233 só na homologação) | Promoção em blocos / aguardar | Junto do bloco de FN-036 | Pendente |
| IMG-N e IMG-P órfãos (sem prioridade) | Priorizar / manter pendente | Responsável decide a prioridade | Pendente |

## 9. Ramos desta entrega
Nenhum aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
