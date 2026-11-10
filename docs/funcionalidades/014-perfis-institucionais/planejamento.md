# 014 — Perfis institucionais — Planejamento

**Estado no tronco:** Concluída
**Prioridade no plano mestre:** sem posição nas ondas do §5 (concluída). Pendências: backlog de perfis futuros (sem priorização); Secretário de Educação contextual depois de FN-017 (Onda 2); código de acesso do responsável com FN-067 (Onda 4).
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/70 (A1-C), docs/132–135 (PF1), docs/139
**Atualizado em:** 2026-11-09, por agente de documentação

## 1. Objetivo
Representar as funções reais da escola além da matriz original de oito perfis: secretário escolar, vice-diretor e auxiliar de secretaria, cada um com política própria e sem herdar poderes de direção. Inclui a regra que impede a mesma pessoa de acumular funções administrativas e pedagógicas na mesma escola, salvo exceção autorizada pela equipe da plataforma.

## 2. Escopo
- **Inclui:** perfil `secretario_escolar` (A1-C); perfis `vice_dir_escolar` e `auxiliar_secretaria` (PF1); extras concedíveis do vice-diretor; regra de grupos funcionais e exceção por Suporte/Desenvolvedor com chamado e motivo; inclusão desses perfis em convites, gestão de vínculos e consumidores.
- **Não inclui:**
  - Motor de permissões e regra "quem cadastra quem": FN-012.
  - Convites e gestão por vínculo: FN-015.
  - Secretário de Educação contextual: depende de FN-017.
  - Código de acesso do responsável e portal: FN-067.
  - Consulta de identidade pelo vice-diretor: FN-016 (decisão §4 item 6).
  - Importação de alunos pelo secretário (`aluno.importar` sem tela): FN-034.
  - Foto e avatar de perfil (nascido no PF1-C): FN-019.

## 3. Fontes de verdade
- Mockups: sem mockup próprio; reutiliza as telas de equipe e convite.
- Origem: [docs/70](../../70-a1-c-secretario-escolar.md), [docs/132](../../132-provas-autoria-cabecalho.md), [docs/133](../../133-planejamento-perfis-institucionais.md), [docs/134](../../134-pf1-b-matriz-perfis-prioritarios.md), [docs/135](../../135-pf1-c-perfis-escolares.md), [docs/139](../../139-id1-dc1-ativacao-e-sequencia.md); [docs/03 §8 (A1-C e PF1)](../../03-perfis-e-permissoes.md).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Código: `apps/api/app/Support/CatalogoPermissoes.php` (`SECRETARIO_ESCOLAR`, `VICE_DIRETOR`, `VICE_EXTRAS`, `AUXILIAR_SECRETARIA`), `PerfisEspaco.php`, `PerfisEscolares.php`, `GruposFuncionais.php`, `Cadastro.php`, `apps/api/app/Http/Controllers/Api/ExcecaoFuncionalController.php`, `apps/web/app/Http/Controllers/ExcecaoFuncionalController.php`.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-012 permissoes-motor | Matriz e extras | Concluída |
| FN-015 vinculos-contexto | Os novos perfis só existem em vínculo de escola aceito e vigente | Concluída |
| FN-013 acessos-internos | Exceção de grupos funcionais concedida por Suporte/Desenvolvedor | Concluída |
| FN-017 convites-cpf | Secretário de Educação contextual | Pendente |
| FN-067 portal-responsaveis | Código de acesso do responsável | Pendente |

**Funcionalidades que dependem desta:** FN-019 (IM1 nasceu do PF1-C), FN-038 (coordenação e vice-diretor em disciplinas), FN-017 (catálogo de perfis aprovado entra no VI1), FN-067 (perfil responsável).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (A1-C) | Secretário escolar só em vínculo de escola; alunos e turmas sem notas/provas | Gestão por admin rede, dir. núcleo e dir. escolar | Entregue (produção) | PR #104 |
| 2.1 (PF1-A) | Inventário de funções | — | Entregue (homologação, validado) | PR #163 |
| 2.2 (PF1-B) | Matriz vice-diretor / secretário / auxiliar | Aprovada em 03/11/2026 | Entregue | docs/134 |
| 2.3 (PF1-C1..C5) | Catálogo e contexto, gestão e convites, consumidores, interface, CI e homologação | Publicado na homologação e validado | Entregue (homologação, validado) | PR #164, #165; docs/135, 139 |
| 2.4 (PF1-C) | Grupos funcionais e exceção por Suporte/Desenvolvedor | Bloqueio com mensagem e exceção com chamado | Entregue (homologação) | docs/134, 135 |
| 3.1 | Backlog de perfis futuros (coordenador de área, orientador, AEE, responsável, auditor etc.) | Planejamento próprio | Pendente | docs/133 |
| 3.2 | Secretário de Educação contextual | Contrato contextual no VI1 | Pendente | depende de FN-017 |
| 3.3 | Código de acesso do responsável | — | Adiada | depende de FN-067 |
| 3.4 | Extras do vice-diretor em disciplinas e consulta de identidade | Decisão | Pendente | docs/139; §4 item 6 |

## 6. Contrato técnico (proporcional ao risco)
- **Políticas independentes:** secretário escolar com 8 permissões fixas; vice-diretor com 11 padrões e 18 extras concedíveis; auxiliar de secretaria com `escola.ver`, `turma.ver`, `aluno.ver`, `aluno.gerir`. Nenhum desses perfis tem permissão sem vínculo selecionado; o campo global da conta não autoriza.
- **Compatibilidade:** os três perfis só existem em espaço do tipo `escola` (`PerfisEspaco::POR_TIPO`).
- **Grupos funcionais:** administrativos (`secretario_escolar`, `auxiliar_secretaria`) não acumulam com pedagógicos (`professor`, `coordenador`, `dir_escolar`, `vice_dir_escolar`, `aplicador`) na mesma escola em vigência sobreposta, salvo exceção em `usuario_funcoes_excecoes`.
- **Exceção:** API `GET configuracoes/funcoes/opcoes`, `POST configuracoes/funcoes/consultar` (10/min), `POST configuracoes/funcoes` (10/min); exige contexto interno de Suporte ou Desenvolvedor; versão otimista; histórico em `usuario_funcoes_eventos`; ninguém autoriza a própria exceção.

## 7. Interface
Perfis aparecem nas telas de equipe, convite e permissões (FN-012, FN-015). Tela interna `funcoes/index` em `/configuracoes/usuarios`. Conferência por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 6 — Matriz ID1 e extras do vice-diretor | Aprovar `identidade.usuario.consultar`/`identidade.aluno.consultar` e os extras do vice-diretor | Plano mestre: aprovar a matriz ID1 (destrava VI1, TR1/TE1, L4-D) | Pendente |
| Backlog de perfis futuros | Priorizar individualmente | Registrar cada perfil como candidata quando houver pedido real | Pendente |

Nenhuma divergência entre o plano mestre e o código encontrada.

## 9. Ramos desta entrega
Nenhum aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | documento criado a partir do inventário e do código | agente de documentação |
