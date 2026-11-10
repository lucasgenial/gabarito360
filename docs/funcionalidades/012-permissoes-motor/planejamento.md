# 012 — Motor de permissões, equipe e menus — Planejamento

**Implementação:** Concluída
**Etapa:** Concluída
**Prioridade no plano mestre:** sem posição nas ondas do §5 (concluída). Pendências residuais sem priorização: migração das 8 rotas `perfil:` e registro das decisões 1–6 do docs/21.
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/03, docs/13 (MP-013, 018, 026, 030, 031, 036), docs/19, docs/21 (etapas 0–7), docs/22
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Decidir, para cada pedido, o que a pessoa pode fazer: permissões padrão do perfil, mais extras concedidas, menos padrões retirados, sempre limitadas pelo escopo (rede, núcleo, escola, turma). Também cobre a gestão da equipe (quem cadastra quem) e os menus montados a partir das permissões.

## 2. Escopo
- **Inclui:** catálogo de permissões e matriz por perfil; extras com validade e retiradas por pessoa; regra "quem cadastra quem"; middlewares `permissao:` e `perfil:`; escopo por rede/núcleo/escola/turma; telas de equipe (listar, cadastrar, editar, ativar/desativar, reenviar convite, enviar link de senha, permissões); menus laterais e barra do celular por permissão.
- **Não inclui:**
  - Vínculos por pessoa, convites e escolha de vínculo: FN-015.
  - Política dos perfis internos (Desenvolvedor, Suporte, Financeiro): FN-013.
  - Políticas próprias de secretário escolar, vice-diretor e auxiliar de secretaria: FN-014.
  - Professor autônomo e espaço pessoal: FN-022.
  - Capacidades de consulta de identidade (`identidade.*`): FN-016.
  - Matriz de disciplinas (`disciplina.gerir`): FN-038.
  - Cadastro de escolas padronizado com o Censo (etapa 6 do docs/21): FN-033.

## 3. Fontes de verdade
- Mockups: `mockups/perfis-equipe.html`, `mockups/membro-cadastrar.html`, `mockups/membro-editar.html` ([inventário §26–§28](../../01-inventario-dos-mockups.md)).
- Origem: [docs/03](../../03-perfis-e-permissoes.md), [docs/21](../../21-plano-perfis-permissoes-e-telas.md), [docs/19](../../19-plano-telas-por-perfil.md), [docs/13](../../13-roadmap.md).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md); UC-008 de [docs/07](../../07-casos-de-uso.md).
- Código: `apps/api/app/Support/Permissao.php`, `CatalogoPermissoes.php`, `Cadastro.php`, `Escopo.php`, `apps/api/app/Http/Middleware/CheckPermissao.php`, `CheckPerfil.php`, `apps/api/app/Http/Controllers/Api/UsuarioController.php`, `apps/web/app/Http/Controllers/MembroController.php`, `apps/web/app/Support/Acesso.php` (menus).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-001 fundacao-monorepo | Tabelas `usuarios`, `perfil_permissoes`, `usuario_permissoes`, `usuario_escopos` | Concluída |
| FN-011 autenticacao-conta | Identidade autenticada | Parcial |
| FN-015 vinculos-contexto | Com o regime de vínculos, permissões e escopo vêm do vínculo selecionado | Concluída |

**Funcionalidades que dependem desta:** FN-013, FN-014, FN-015, FN-016 (capacidades de identidade), FN-022, FN-038 e todas as funcionalidades com rotas protegidas por `permissao:`.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (MP-013) | Membros da equipe | CRUD de membros no escopo | Entregue (produção) | f3dfa94 |
| 1.2 (MP-018) | Vínculo professor↔turma | Professor vê só suas turmas | Entregue (produção) | commit "MP-018" |
| 1.3 (MP-026) | Secretaria e aplicador | Perfis com painel próprio | Entregue (produção) | 917dd81 |
| 2.1 (docs/21 etapas 0–7) | Correção do cadastro, catálogo, motor, telas, menus, celular, escolas INEP, testes | Testes por perfil | Entregue (produção) | PR #15–#22 |
| 2.2 (MP-030) | Motor configurável | Padrão + extras − retiradas | Entregue por outro desenho | 509be82; `Support\Permissao` (não `PermissaoService`) |
| 2.3 (MP-031) | Trocar `perfil:` por `permissao:` | Nenhuma rota com `perfil:` | Parcial | restam 8 rotas `dashboard/*` em `apps/api/routes/api.php` |
| 2.4 (MP-036) | Menu por perfil | Itens só com permissão | Entregue | 59a80ba |
| 2.5 | Registrar respostas das decisões 1–6 do docs/21 | Respostas no docs/03 ou docs/21 | Pendente | §3 do plano mestre |

## 6. Contrato técnico (proporcional ao risco)
- **Regra central:** efetivas = padrão do perfil + extras válidas (`valida_ate`) − retiradas. Com contexto de vínculo, valem as permissões do vínculo (`ContextoVinculo::permissoes`); com contexto interno, as do acesso interno. Perfis `professor_autonomo`, `secretario_escolar`, `vice_dir_escolar` e `auxiliar_secretaria` não recebem nada sem vínculo explícito. Permissão nunca amplia escopo.
- **Quem cadastra quem (`Cadastro::PODE_GERIR`):** admin_rede gere todos os perfis escolares, de núcleo e de rede; dir_nucleo gere direção e equipe escolar administrativa e coordenação; dir_escolar e vice-diretor (com `usuario.gerir`) gerem equipe da escola, professores, aplicadores e alunos; coordenador gere professor, aplicador e aluno.
- **Conceder/retirar:** exige `usuario.permissoes`, gerir o perfil da pessoa, ter a própria permissão; só extras podem ser concedidas e só padrões podem ser retirados.
- **API:** `GET permissoes/perfis` (`usuario.ver`), `GET|POST usuarios`, `GET|PUT usuarios/{id}`, `POST usuarios/{id}/toggle`, `POST usuarios/{id}/convite`, `POST usuarios/{id}/link-senha` (5/min), `GET|PUT usuarios/{id}/permissoes` (`usuario.permissoes` para gravar).
- **Cache:** por requisição, em memória (`Permissao::$cache`); sem cache entre requisições.
- **Desempenho:** autorização em SQL e lotes otimizada em FN-077 (F2).

## 7. Interface
Telas `membros/index`, `membros/create`, `membros/edit`, `membros/perfis`, `membros/permissoes`. Menu lateral e barra inferior do celular por permissão (`Acesso::ITENS`, `ORDEM`, `PRINCIPAIS`). Conferência registrada por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 6 — Matrizes de ID1 e DC1 | Aprovar matriz ID1; registrar a matriz DC1 vigente no docs/03 | Plano mestre: aprovar ID1 e registrar DC1 (`disciplina.gerir` em vigor) | Pendente |
| 8 rotas `perfil:` em `dashboard/*` (MP-031) | Migrar para `permissao:` / manter | Migrar; o painel por perfil é a última exceção | Pendente |
| Decisões 1–6 do docs/21 | Registrar respostas | Registrar no docs/03 o que o código já aplica (extras com validade e retiradas existem) | Pendente |
| ⚠ `secretario_educacao` aceito no cadastro legado (`UsuarioController::store`), mas fora de `PerfisEspaco::POR_TIPO` | Retirar do cadastro / criar vínculo contextual (FN-014 após FN-017) | Divergência registrada; perfil não suportado no regime de vínculos | Pendente |

## 9. Ramos desta entrega
Nenhum aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | documento criado a partir do inventário e do código | lucasgenial |
