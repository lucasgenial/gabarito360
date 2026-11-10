# 017 — Vínculos e convites CPF-primeiro (VI1) — Planejamento

**Implementação:** Pendente
**Etapa:** Em planejamento
**Prioridade no plano mestre:** Onda 2 (depende de FN-016, FN-056 para o aceite agendado e FN-057). Proposta ainda não aprovada (§5).
**Natureza:** nova
**Origem:** docs/115 §5, docs/119, 125, 133, 139
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Fazer o cadastro de pessoas na instituição começar pelo CPF: se a pessoa já existe, o operador só propõe um novo vínculo, sem sobrescrever dados da conta; se não existe, libera o cadastro mínimo. O titular aceita ou recusa, inclusive com início futuro, sem ganhar acesso antes da data.

## 2. Escopo
- **Inclui:** VI1-A cadastro CPF-primeiro; VI1-B aceite antecipado com vínculo agendado e novos estados; VI1-C notificações do convite; contrato contextual do Secretário de Educação; incorporação do catálogo de perfis aprovado no PF1.
- **Não inclui:**
  - Fundação de identidade e capacidades de consulta: FN-016.
  - Convites atuais por e-mail e gestão por vínculo (permanecem até a substituição): FN-015.
  - Módulo de agenda e de notificações: FN-056 e FN-057 (não criar versões locais).
  - Transferências e matrícula: FN-025 e FN-026.
  - Importação de histórico anterior: fluxo distinto, sem permissão retroativa (docs/115 §5).

## 3. Fontes de verdade
- Mockups: nenhum; telas a aprovar antes da execução.
- Origem: docs/115 §5 (`docs/115-replanejamento-provas-identidade-transferencias.md`), docs/119 (`docs/119-ci1-a-contrato-catalogo-nacional.md`), docs/125 (`docs/125-ci1-e-solicitacoes-manutencao.md`), docs/133 (`docs/133-planejamento-perfis-institucionais.md`), docs/139 (`docs/139-id1-dc1-ativacao-e-sequencia.md`).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: docs/03 (`docs/03-perfis-e-permissoes.md`).
- Código atual a evoluir: `apps/api/app/Services/ConviteVinculo.php`, `ContaUsuario.php`, `UsuarioController::store`.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-016 identidade-pessoal | Busca por CPF e matriz de consulta | Parcial |
| FN-015 vinculos-contexto | Base de vínculos e convites | Concluída |
| FN-056 agenda-calendario | VI1-B agendado | Pendente |
| FN-057 notificacoes | VI1-C | Pendente |
| FN-014 perfis-institucionais | Catálogo de perfis aprovado | Concluída |

**Funcionalidades que dependem desta:** FN-014 (Secretário de Educação contextual).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (VI1-A) | Cadastro CPF-primeiro | Mesma conta reaproveitada; unicidade revalidada sob transação | Pendente | sem código |
| 1.2 (VI1-B) | Aceite antecipado e vínculo agendado | Início futuro sem acesso antecipado; outros vínculos preservados | Pendente | sem código (`aceito_agendado` inexistente) |
| 1.3 (VI1-C) | Notificações do convite | Outbox transacional, repetição idempotente, histórico de entrega | Pendente | sem código |
| 1.4 | Secretário de Educação contextual | Contrato de espaço e seleção aprovado | Pendente | docs/125, 133 |

## 6. Contrato técnico (proporcional ao risco)
A detalhar antes da execução. Diretrizes do docs/115 §5: estados propostos `convidado`, `aceito_agendado`, `ativo`, `suspenso`, `recusado`, `encerrado`, com migration aditiva compatível com os estados atuais; início não anterior ao dia atual no fuso da instituição, gravado em UTC; mudança de papel, alcance ou início após o convite exige novo convite; revalidação de emissor, escopo e papéis no aceite e no acesso; consulta de CPF exata, auditada e limitada.

### Detalhamento herdado do docs/115

Passos do fluxo (docs/115 §5). Os estados e as regras abaixo são propostas, ainda não implementadas.
1. O operador escolhe a instituição e informa o CPF primeiro. O restante do formulário fica bloqueado durante a verificação.
2. O backend normaliza, valida e pesquisa a identidade protegida. Sem correspondência conciliada, libera o cadastro mínimo. A unicidade é revalidada sob transação para dois cadastros simultâneos.
3. Se o usuário existe, o sistema mostra só a identificação mínima autorizada. O operador não sobrescreve nome, e-mail ou senha globais. Ele informa papel, instituição, início e alcance do novo vínculo.
4. O início não pode ser anterior ao dia atual no fuso da instituição. O sistema grava o instante em UTC. Para o dia de hoje, não retroage antes do instante autorizado. Início futuro é permitido. A importação de histórico anterior é outro fluxo e não cria permissão retroativa.
5. O convite vai para o e-mail já verificado da conta existente. Para pessoa nova, o e-mail é verificado e a ativação segue fluxo seguro. O sistema não exibe nem reutiliza senha de terceiros. A escola não troca o e-mail de acesso.
6. O titular aceita ou recusa. Aceite antecipado de convite futuro registra o aceite e mantém o vínculo agendado. O acesso começa só quando o vínculo está aceito, o início foi atingido e a instituição e a conta estão ativas. Recusa, cancelamento e expiração não afetam vínculos de outras instituições.
7. O sistema revalida emissor, escopo e papéis no aceite e no acesso. Mudar papel, alcance ou início após o convite exige nova versão de convite. Nenhuma permissão sobe em silêncio.

Regras adicionais:
- Não escolher só o vínculo mais recente. Mostrar todos os vínculos simultâneos válidos e considerar as datas do caso escolar.
- Notificações usam outbox transacional, repetição idempotente, reenvio controlado e histórico de entrega. O e-mail de convite não equivale a aceite.
- A materialização dos estados propostos, compatível com os estados atuais, é definida na migration.

Estratégia de migração (docs/115 §11): expandir, mapear, conferir, trocar leituras e restringir. Nenhum vínculo atual é renumerado ou convertido em massa sem mapa. Ambiguidades vão para lista de conciliação. Rollback de aplicação não faz down destrutivo.

Cenários de aceite (docs/115 §13):
- Novo usuário verifica o e-mail. Usuário existente aceita o vínculo no login habitual.
- Convite futuro aceito hoje não libera acesso hoje.
- Recusa e expiração preservam os outros vínculos.
- CPF com máscara, inválido, ausente, corrigido, duplicado legado ou concorrente não gera identidade errada. Conhecer o CPF de outra pessoa não revela cadastro nem histórico.
- Duas tentativas concorrentes com a mesma chave não duplicam pessoa nem vínculo.

## 7. Interface
A definir: formulário com CPF primeiro e demais campos bloqueados durante a verificação; identificação mínima de pessoa encontrada; estados de carga, vazio e erro. Conferência por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 6 — Matriz de permissão de ID1 | Aprovar as capacidades de consulta | Plano mestre: aprovar; destrava VI1 | Pendente |
| §4 item 7 — Escopo do módulo de agenda | Aprovar a PoC e priorizar FN-056 | VI1-B aguarda o módulo; não criar agenda própria | Pendente |
| Convivência com os convites atuais (FN-015) | Substituir / manter os dois caminhos | Definir no contrato técnico; o cadastro atual exige CPF e e-mail e cria conta nova | Pendente |

## 9. Ramos desta entrega
Nenhum.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | documento criado a partir do inventário e do código | lucasgenial |
