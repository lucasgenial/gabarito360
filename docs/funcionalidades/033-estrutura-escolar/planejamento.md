# 033 — Núcleos, escolas, turmas e alunos — Planejamento

**Implementação:** Parcial
**Etapa:** Em planejamento
**Prioridade no plano mestre:** Onda 3 (PROPOSTA, §5) — subfases A3 e A4; dependência declarada "—"
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/13 MP-014..017, MP-035, MP-039; docs/28 A3, A4
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Manter a estrutura cadastral da rede: núcleos territoriais, escolas (sede, anexo, unidade vinculada), turmas e alunos, cada um no escopo do perfil e do vínculo ativo. É a base de todas as demais funcionalidades pedagógicas (provas, correção, relatórios, painéis).

## 2. Escopo
- **Inclui:** CRUD de núcleos pela API; cadastro, edição, ativação/desativação e logo de escolas (com consulta ao Censo/INEP); cadastro, edição e listagem de turmas; cadastro, edição, listagem e detalhe de alunos; vínculo professor↔turma (MP-018, legado); foto/avatar do aluno; dados próprios do núcleo (A3) e separação das equipes de núcleo e escola (A4), ainda pendentes.
- **Não inclui:**
  - Importação e exportação de alunos → [FN-034](../034-importacao-exportacao-alunos/planejamento.md).
  - Dados estruturados dos responsáveis → [FN-035](../035-responsaveis-alunos/planejamento.md).
  - Abas da turma, transferência entre turmas, arquivamento e painel analítico → [FN-036](../036-organizacao-turma/planejamento.md).
  - Imagens de turmas e educadores → [FN-037](../037-imagens-turmas-educadores/planejamento.md).
  - Disciplinas e atribuições docentes → [FN-038](../038-disciplinas-curriculo/planejamento.md).
  - Status de matrícula e intervalos históricos (MP-035b) → FN-026.
  - Avatares no lugar de foto de aluno (L6) → FN-066.
  - Equipe da escola, perfis e convites → FN-012, FN-014, FN-017.
  - Catálogo nacional de instituições → FN-031.

## 3. Fontes de verdade
- Mockups: `mockups/escolas.html`, `escola-detalhe.html`, `turmas.html`, `turma-detalhe-2.html`, `aluno-cadastrar.html`, `aluno-cadastrar-redesign.html`, `aluno-editar.html`, `aluno-detalhe.html` (inventário em docs/01 (`docs/01-inventario-dos-mockups.md`) §13–§19).
- Documentos de origem: docs/13 (`docs/13-roadmap.md`) MP-014..017, 035, 039; docs/28 (`docs/28-plano-mestre-proximos-modulos.md`) A3, A4; docs/19 (`docs/19-plano-telas-por-perfil.md`) MP-039.
- Casos de uso: [casos-de-uso.md](casos-de-uso.md); UC-002 de docs/07 (`docs/07-casos-de-uso.md`).
- Perfis e permissões: docs/03 (`docs/03-perfis-e-permissoes.md`); código `apps/api/app/Support/CatalogoPermissoes.php`.
- Plano mestre: [§2 FN-033](../../planejamento/00-plano-mestre.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-012 permissoes-motor | Chaves `nucleo.*`, `escola.*`, `turma.*`, `aluno.*` e middleware `permissao:` | Concluída |
| FN-015 vinculos-contexto | Escopo pelo vínculo ativo (V1) — dependência declarada de A3 | Concluída |
| FN-021 clientes-espacos | `espaco_id` e criação de espaço para núcleo/escola | Concluída |

**Funcionalidades que dependem desta:** FN-026, FN-034, FN-035, FN-036, FN-037, FN-038, FN-040, FN-049, FN-052, FN-053 (todas consomem núcleo/escola/turma/aluno). A4 depende de A3.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (MP-014) | CRUD de núcleos (API; tela "a definir") | Endpoints com escopo | Entregue (produção) | commit cf60afd; `routes/api.php` `nucleos` |
| 1.2 (MP-015) | Escolas: grid, cadastro, KPIs, logo, Censo | Cadastro e edição no escopo | Entregue (produção) | commit fa828c6; `EscolaController` |
| 1.3 (MP-016) | Turmas | CRUD no escopo | Entregue (produção) | commit d65b24b; importação por planilha → FN-034 |
| 1.4 (MP-017) | Alunos: CRUD e histórico | CRUD no escopo | Entregue (produção) | commit 4fdfa01 |
| 1.5 (MP-017) | Ficha PDF do aluno | Botão "Ficha do Aluno (PDF)" do mockup | Incerto | nenhuma rota encontrada na API nem na web |
| 1.6 (MP-035a) | Foto do aluno na edição | Foto/avatar editável | Entregue, depois substituída por avatares (FN-066) | `ImagemAlunoController` |
| 1.7 (MP-039) | Turmas, alunos e equipe pela escola | Fluxos pela escola | Parcial (importação → FN-034) | docs/19; `alunos/{id}/editar` |
| 2.1 (A3) | Dados do núcleo (código, endereço, contatos, responsável, logo) e `nucleo.editar` | Diretor de núcleo edita só o próprio núcleo | Pendente | só `nucleo.gerir` legado |
| 2.2 (A4) | Separar equipes do núcleo e da escola | Cada lista mostra só o que lhe pertence | Pendente | — |

## 6. Contrato técnico (proporcional ao risco)
- **API (existente):** `GET/POST /v1/nucleos`, `GET/PUT/DELETE /v1/nucleos/{id}`; `GET/POST /v1/escolas`, `GET/PUT /v1/escolas/{id}`, `POST /v1/escolas/{id}/toggle`, `GET/POST /v1/escolas/{id}/logo`, `GET/POST /v1/redes/{id}/logo`, `GET /v1/censo/escolas[/{inep}]`; `GET/POST /v1/turmas`, `GET/PUT /v1/turmas/{id}`; `GET/POST /v1/alunos`, `GET/PUT /v1/alunos/{id}`; `GET/POST/DELETE /v1/turmas/{id}/professores`.
- **Escopo:** `Instituicoes::nucleos`, `Escopo::escolaIds`, `Escopo::turmas`, `Escopo::alunos`; registros fora do escopo retornam 404.
- **Espaços:** com `Espacos::ativo()`, núcleo e escola criam espaço próprio; mudança de rede, de núcleo ou de escola é recusada com 422 ("exige procedimento próprio").
- **Dados protegidos:** CPF do aluno validado por `UnicoProtegido` (cifragem em FN-063). Matrícula única por espaço.
- **A3 (proposto, docs/28):** migration aditiva em `nucleos` (código, endereço, cidade, contatos, responsável, logo); `PUT /v1/nucleos/{id}` com `nucleo.editar`; tela de edição para Diretor de Núcleo e administrador; logo do núcleo nos relatórios.
- **A4 (proposto, docs/28):** usar `usuario_vinculos` sem duplicar pessoas; tela "Equipe do núcleo"; remover o uso de `usuarios.escola_nome`.
- **Desempenho:** listagens sem paginação na API (`get()`); paginação → FN-074; medição com 1000 usuários simultâneos → FN-077.

## 7. Interface
- Web existente: `escolas.*` (lista, criar, editar, detalhe, toggle, logo), `turmas.*`, `alunos.*` (lista, novo, editar, detalhe, transferir, arquivamento). Não há tela de núcleos.
- Estados de carga, vazio e erro seguem FN-076; tabelas seguem FN-074; migração Tailwind em FN-075.
- A3/A4 exigirão mockup ou aprovação explícita antes da implementação (não há mockup de núcleo nem de equipe do núcleo).

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 1 — aprovação do plano mestre e da priorização | Aprovar / ajustar | Aprovar §1–§3 e Onda 0 | Pendente |
| Ficha PDF do aluno (mockup `aluno-detalhe.html`) | Implementar / retirar do escopo | Confirmar com o responsável; nenhum código encontrado | Pendente |
| Criação de núcleo restrita a `admin_rede` no código, embora o catálogo marque `nucleo.gerir` como extra concedível ao Diretor de Núcleo (`-vo-----`) | Manter / alinhar catálogo | Registrar no A3 junto com `nucleo.editar` | Pendente |
| Não há tela web de núcleos (MP-014 "tela a definir") | Criar em A3 / manter só API | Tratar em A3 | Pendente |

## 9. Ramos desta entrega
Nenhum aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
