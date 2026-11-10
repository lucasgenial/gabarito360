# 042 — Duplicação de provas (CP1, CP2) — Planejamento

**Implementação:** Parcial
**Etapa:** Em planejamento
**Conforme princípios:** versão 1
**Prioridade no plano mestre:** Onda 2 (PROPOSTA, §5) — CP2, depende de DC1 (feito) e de FN-057; CP1 na fila de produção §7 (PR #151)
**Natureza:** existente documentada a partir do código (CP1) com evolução pendente (CP2, A7)
**Origem:** tronco — docs/28 A7; docs/115 §3; docs/118 (CP1); docs/142 §2 (CP2)
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Permitir reaproveitar uma prova sem tocar na original: o professor copia a própria prova como rascunho independente (CP1) e a coordenação entrega uma cópia a outro professor da mesma escola (CP2). Nenhuma cópia leva turmas, alunos, cartões, respostas ou notas.

## 2. Escopo
- **Inclui:** CP1-A..D — `POST /provas/{id}/copiar`, procedência, idempotência, modal Copiar e campo `formato_avaliacao`; CP2 — cópia da coordenação para professor, com notificação; A7 — exportar e importar prova em JSON.
- **Não inclui:**
  - Cópias entre espaços e escolas (T5) → FN-024 (Substituída) e FN-025.
  - Notificação no sistema usada pelo CP2 → FN-057 (módulo transversal; não criar lista local).
  - Enunciados e questões como entidades próprias → [FN-043](../043-editor-provas-questoes/planejamento.md); o contrato de cópia v1 só cobre configuração e gabarito.
  - Regras de autoria e visibilidade → [FN-041](../041-autoria-provas/planejamento.md).

## 3. Fontes de verdade
- Documentos de origem: docs/118 (`docs/118-cp1-contrato-duplicacao-pedagogica.md`), docs/142 (`docs/142-dc1-integracao-provas-e-cp2.md`) §2, docs/28 (`docs/28-plano-mestre-proximos-modulos.md`) A7, docs/115 §3, docs/12 (seção CP1).
- Extensão aprovada CP1 no docs/01 (`docs/01-inventario-dos-mockups.md`); lista em `mockups/provas.html`.
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: docs/03 (`docs/03-perfis-e-permissoes.md`) — `prova.duplicar_propria` (Professor padrão; Professor autônomo). `prova.duplicar_para_professor` é só proposta.
- Plano mestre: [§2 FN-042](../../planejamento/00-plano-mestre.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-040 provas-gabaritos | Prova e gabarito de origem | Concluída |
| FN-041 autoria-provas | Elegibilidade pela autoria | Em homologação |
| FN-038 disciplinas-curriculo (DC1) | Elegibilidade docente do destinatário do CP2 | Em homologação |
| FN-057 notificacoes | Aviso ao destinatário do CP2 | Pendente |
| FN-015 vinculos-contexto | Chave idempotente por vínculo | Concluída |

**Funcionalidades que dependem desta:** nenhuma declarada no plano mestre. FN-043 deverá definir a versão seguinte do contrato de cópia.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (CP1-A..D) | Copiar a própria prova + `formato_avaliacao` | Aceite do docs/118 | Entregue (homologação) | PR #151 (dedf074); `DuplicarProva` |
| 2.1 (CP2) | Coordenação copia para professor | Original intacta, destinatário correto, idempotência, notificação transacional | Pendente (só contrato) | docs/142; chave `prova.duplicar_para_professor` inexistente no código |
| 3.1 (A7) | Exportar e importar prova em JSON | Prova importada é corrigida normalmente | Incerto | só o comando `g360:importar-provas`; nenhuma exportação |

## 6. Contrato técnico (proporcional ao risco)
- **CP1 (existente):** `POST /v1/provas/{id}/copiar` com `titulo` (até 255) e `chave` UUID; middleware `prova.duplicar_propria`. Exige espaços ativos e vínculo presente (403), perfil `professor` ou `professor_autonomo`, autor da origem igual ao ator, mesmo espaço, formato `gabarito`, prova não institucional e as permissões `prova.ver/criar/editar` e `gabarito.ver/editar`; caso contrário 404 "Prova não disponível para cópia.".
- **Efeito:** novo rascunho no mesmo espaço, com configuração e questões do gabarito; sem turmas, cartões, notas, data de aplicação ou publicação. Procedência em `prova_duplicacoes` (origem, raiz, autor original, ator, vínculo, versão do contrato).
- **Idempotência:** mesma chave e mesmos dados devolvem a mesma prova; dados diferentes → 409 "Esta solicitação já foi utilizada com outros dados.".
- **Aplicação da cópia:** publicar ou gerar cartões exige turmas escolhidas e gabarito publicado (`DuplicarProva::conferirAplicacao`).
- **CP2 (proposto, docs/142):** chave `prova.duplicar_para_professor` padrão só do Coordenador; destinatário com vínculo vigente e atribuição DC1; título e motivo; idempotência por intenção; outbox transacional com notificação. Catálogo do docs/03 deve ser atualizado antes da sincronização.
- **A7 (parcial):** `g360:importar-provas {arquivo} --escola --usuario --vinculo --turmas --ano --espaco --aplicar` cria provas com gabarito publicado a partir de JSON; não há exportação nem importação pela tela.

### Detalhamento herdado do docs/115

Regras de duplicação (docs/115 §2 e §3).
- Duplicar prova cria um rascunho com IDs novos, sem turmas, cartões, notas nem alunos. Alterar as turmas da mesma prova é operação separada, com validação de lotação e estado. Nunca altera aplicação histórica.
- Professor para si: mostrar origem, título, disciplina, responsável e o que será copiado. Sugerir título com indicação de cópia. Copiar somente itens de lista positiva. Zerar turmas, publicação, datas, correção, cartões, respostas, notas, QR, tokens, links e envios. Não gerar cartões antes de vincular novas turmas e publicar.
- Proveniência: `prova_original_id`, `prova_raiz_id`, autor original, ator da duplicação, responsável da cópia e instante. Distinguir autoria de responsabilidade. Manter a referência mesmo se a original for arquivada. No detalhe, mostrar "Copiada de [prova]", data e responsável. O link só aparece se o usuário ainda puder ler a original. Nunca revelar título ou autor de fonte fora do escopo.
- Coordenador para outro professor (CP2): só dentro da escola do seu vínculo. Ação "Criar cópia para professor", com professor de vínculo aceito e lotação elegível na disciplina. Não dá acesso à original nem leva as turmas do autor. O destinatário recebe um rascunho próprio e escolhe suas turmas. Registrar origem, coordenador, destinatário, motivo e digest. Notificar no sistema. O e-mail não leva dados escolares. Não usar acesso interno. Revalidar vínculo e lotação no commit e no uso posterior.
- Permissões propostas: `prova.duplicar_propria` e `prova.duplicar_para_professor`. Professor institucional e professor autônomo copiam para si no próprio alcance. O coordenador copia só para professores da sua escola. Não inferir poderes por hierarquia.
- Duplo clique ou reenvio da mesma intenção cria uma cópia. Uma nova intenção pode criar outra variante, com outra chave.
- Se houver edição de enunciados fora do modelo atual, detalhar editor ou banco de questões em pacote próprio. Duplicar metadados não edita a prova textual.

Cenários de aceite (docs/115 §13):
- Professor das turmas 1ºA, 1ºC e 1ºD duplica para a 1ºB recém-atribuída. A cópia nasce sem turmas. Alterar o gabarito dela não muda a original nem recalcula notas antigas.
- Coordenador duplica para colega das turmas 1ºD, 1ºE e 1ºF. O colega não recebe vínculos das turmas 1ºA, 1ºB e 1ºC nem leitura da original fora do escopo.
- Duas tentativas concorrentes com a mesma chave não duplicam a prova. Uma nova intenção legítima pode criar nova cópia.

## 7. Interface
- CP1: ação "Copiar" na lista e no detalhe elegível (`provas/_botao-copiar.blade.php`), modal nativo com origem, explicação e novo nome; após confirmar, abre o rascunho. Sem página nem rota GET própria.
- CP2: fluxo a desenhar (escolher prova, destinatário, disciplina quando ambígua, título, motivo, resumo e confirmação).

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 2 — gate de produção (CP1, PR #151, só na homologação) | Promoção em blocos / aguardar | Definir bloco após a segurança | Pendente |
| §4 item 1 — aprovação do plano mestre e da priorização (CP2 na Onda 2) | Aprovar / ajustar | Aprovar §1–§3 e Onda 0 | Pendente |
| CP2 órfão: contrato pronto desde 04/11, sem plano de execução | Executar após FN-057 / antecipar sem notificação | Aguardar FN-057, conforme a governança | Pendente |
| A7 "Incerto" no plano mestre: o código tem importação de JSON só por comando de terminal e nenhuma exportação | Manter A7 / encerrar em favor de CP1/CP2 | Responsável decide se exportar/importar pela tela continua no escopo | Pendente |

## 9. Ramos desta entrega
Encerrado: `formato_avaliacao` (complemento autorizado durante o CP1).

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
