# 004 — Auditoria de requisições, expurgo e tela de consulta — Planejamento

**Estado no tronco:** Parcial
**Prioridade no plano mestre:** expurgo na Onda 3 (pacote de retenção com 062, 066 e 064, decisão §4 item 11); AU2 na Onda 4 (junto com 005 e 006, dependência de D9 e AU1)
**Natureza:** existente documentada a partir do código (AU1) e evolução de existente (expurgo, AU2)
**Origem:** tronco original — docs/28 (AU1, AU2), docs/32; AU1 antecipado como dependência de T5; execução em docs/72–76; operação em docs/operacao/auditoria.md
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Registrar quem fez o quê, onde e quando em toda requisição à API, sem gravar dados pessoais ou credenciais, com retenção de 180 dias, arquivo mensal verificável e, nas próximas fases, expurgo dos eventos vencidos e uma tela de consulta e exportação restrita a Desenvolvedor e Suporte.

## 2. Escopo
- **Inclui:**
  - AU1 — tabela `auditoria_eventos`, captura no `terminate` do middleware, gravação assíncrona na fila `baixa` por conexão dedicada (só INSERT/SELECT), conferência de inventário e integridade, arquivo mensal cifrado, restauração ensaiada.
  - Ativação em produção com credencial restrita (02/11/2026).
  - Expurgo de eventos com mais de 180 dias, com preservação excepcional por mês.
  - AU2 — tela de consulta com filtros, ocultação dos eventos de Desenvolvedores para o Suporte, auditoria da própria consulta e exportação CSV.
  - Revisão de privilégios das credenciais gerais (docs/74).
- **Não inclui:**
  - Atribuição "usuário real × usuário visto" durante o "ver como" → [FN-005](../005-suporte-ver-como/planejamento.md) (as colunas já existem).
  - Auditoria de domínio das cópias (outbox `copias_outbox`) → FN-024.
  - Auditoria de jobs e comandos de CLI: não passam pelo middleware; cada módulo integra explicitamente.
  - Particionamento físico mensal ou ledger: Adiada até haver volume real (docs/75).

## 3. Fontes de verdade
- Mockups: nenhum mockup para a tela de auditoria (docs/01 não lista). AU2 exige mockup ou aprovação explícita antes da execução.
- Documentos de origem: [docs/28](../../28-plano-mestre-proximos-modulos.md) (AU1, AU2), [docs/32](../../32-auditoria.md), [docs/72](../../72-au1-a-registro-auditoria.md), [docs/73](../../73-au1-b-operacao-auditoria.md), [docs/74](../../74-au1-b2-privilegios-e-fila.md), [docs/75](../../75-au1-b3-arquivos-e-restauracao.md), [docs/76](../../76-au1-ensaio-equivalente.md), [docs/80](../../80-t5-a3-outbox-auditoria.md), [docs/100](../../100-publicacao-inicial-v1-t5.md), [docs/operacao/auditoria.md](../../operacao/auditoria.md).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md) — contexto interno (Desenvolvedor, Suporte). Ainda não há chave de permissão de auditoria no catálogo.
- Plano mestre: [§2 FN-004](../../planejamento/00-plano-mestre.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-002 base-operacao | Fila `baixa` em Redis e workers | Concluída (com ressalva) |
| FN-015 vinculos-contexto | Contexto do vínculo (vínculo, espaço, cliente, escola, núcleo, perfil) no evento | Concluída |
| FN-013 acessos-internos | Contexto interno no evento; AU2 restrita a Desenvolvedor e Suporte | Concluída |
| FN-062 lgpd-inventario-retencao | Política de retenção e expurgo | Parcial |
| Decisão §4 item 11 | Aprovar o expurgo de AU1 no pacote de retenção | Pendente |

**Funcionalidades que dependem desta:** FN-005 (S1 e AU3 registram usuário real e visto), FN-006 (A2 depende de AU1), FN-024 (T5-A3 grava envelope de domínio em `auditoria_eventos`), FN-062 (pacote de retenção), FN-064 (operações de titulares auditadas).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 4.1 (AU1-A) Registro assíncrono | Tabela aditiva, captura sem payload, IP reduzido à rede, flag desligada | Testes de registro, mascaramento e falha sem derrubar a operação | Entregue | PR #106; docs/72, docs/73 |
| 4.2 (AU1-B) Conferência somente leitura | `g360:conferir-auditoria` (inventário, digest, corte de retenção simulado) | Conferência sem escrita | Entregue | PR #107; docs/74 |
| 4.3 (AU1-B2) Retenção e conexão dedicada | 180 dias configuráveis; conexão `auditoria_gravacao` INSERT/SELECT; recuperação da fila Redis | Job recusa conexão geral ou credencial vazia | Entregue | PR #108; docs/73, docs/75 |
| 4.4 (AU1-B3) Arquivo mensal cifrado | `g360:arquivar-auditoria` e verificação | Pacote verificável sem consultar o banco | Entregue | PR #109; docs/75 |
| 4.5 (AU1-B4) Ensaio equivalente | Recuperação em clones com 1,8 M eventos sintéticos | Recuperação completa registrada | Entregue | PR #110; `docs/operacao/evidencias/au1-recuperacao-volume-2026-11-01.json`; docs/76 |
| 4.6 Ativação com credencial restrita | `AUDITORIA_ATIVA`, credencial SELECT/INSERT | Captura ativa em produção | Entregue (produção 02/11) | docs/100 |
| 4.7 Expurgo de eventos vencidos | Exclusão dos eventos com mais de 180 dias, respeitando meses preservados | Política aprovada e expurgo testado | Pendente | docs/74, docs/80 ("nenhuma exclusão implementada") |
| 4.8 Revisão de privilégios das credenciais gerais | Isolamento além da credencial do job | Revisão registrada | Pendente | docs/74 |
| 4.9 Particionamento físico / ledger | Alternativa ao arquivo mensal | Medição de volume real | Adiada | docs/75 |
| 4.10 (AU2) Tela de consulta e exportação | Filtros, ocultação de Desenvolvedores para o Suporte, auditoria da consulta, CSV | Testes de Desenvolvedor, Suporte e negação aos demais | Pendente | Sem rota nem tela |

## 6. Contrato técnico (proporcional ao risco)
- **Captura:** `RegistrarAuditoria` é middleware global da API (`append`) e registra no `terminate`, depois da resposta. Só rotas `api/v1/*`; excluídos `GET api/v1/health` e `GET api/v1/planos`. Registra todas as demais requisições, de leitura ou escrita, inclusive recusas e erros.
- **Campos:** `id` UUID, `mes`, `ocorrido_em` (UTC, microssegundos), `usuario_real_id`, `usuario_visto_id` (hoje sempre iguais), `vinculo_id`, `acesso_interno_id`, `espaco_id`, `cliente_id`, `escola_id`, `nucleo_id`, `perfil`, `metodo`, `rota` (modelo da URI, não a URL), `referencias` (só IDs numéricos de `id`, `alunoId`, `provaId`, `turmaId`, `usuarioId`), `status_http`, `resultado` (`sucesso` < 400, `recusado` < 500, `erro`), `ip_rede` (/24 em IPv4, /48 em IPv6). Sem payload, sem credenciais.
- **Gravação:** job `GravarAuditoria` cifrado (`ShouldBeEncrypted`), fila `baixa`, 3 tentativas (10 s e 60 s); exige conexão dedicada diferente da padrão e com usuário; reentrega com o mesmo UUID é idempotente e conteúdo divergente é recusado. Falha ao enfileirar não altera a resposta e só registra `auditoria_enfileiramento_indisponivel` no log.
- **Configuração:** `AUDITORIA_ATIVA` (padrão desligado), `AUDITORIA_QUEUE_CONNECTION` (redis), `AUDITORIA_RETENCAO_DIAS` (180), `AUDITORIA_DB_CONNECTION`, `AUDITORIA_MESES_PRESERVADOS`.
- **Volume:** índices por `(mes, ocorrido_em)`, `(usuario_real_id, ocorrido_em)` e `(espaco_id, ocorrido_em)`. AU2 precisa paginar e filtrar por esses índices; medir com o padrão de 1000 usuários simultâneos quando a tela existir (FN-077).
- **Expurgo (a definir):** comando agendado que exclui por mês fechado vencido fora de `meses_preservados`, depois de arquivo mensal verificado; exige credencial com DELETE separada da de gravação (decisão).

## 7. Interface
AU2 não tem mockup. Antes da execução: mockup ou aprovação explícita, tabela no padrão FN-074, estados de carga, vazio e erro (FN-076), Tailwind (FN-075), desktop e celular. Conferência registrada por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 11 — LGPD operacional, inclui o expurgo de AU1 e a política de prazo da auditoria | Pacote único de retenção; decisão isolada | Pacote único de retenção na Onda 3 | Pendente |
| §4 item 9 — D9 ("ver como" só leitura) | — | Responder D9 antes de S1; AU2 segue junto na Onda 4 | Pendente |
| Revisão de privilégios das credenciais gerais (docs/74) | Restringir; manter | Revisar junto com o expurgo | Pendente |
| Referência independente para os digests (docs/75) | Guardar fora do servidor; manter local | Definir com o pacote de retenção | Pendente |
| ⚠ docs/28 previa registrar por padrão só escritas e leituras sensíveis; o código registra todas as requisições da API, exceto saúde e planos | Manter o comportamento atual; filtrar | Manter (decisão técnica de AU1-A); registrar no docs/32 ao revisá-lo | Sem decisão registrada |
| docs/32 ainda diz "captura continua desligada" (§6 do plano mestre) | — | Vale o plano mestre: ativada em 02/11 | Registrado no plano mestre |
| AU2 sem mockup | Criar mockup; aprovação explícita | Mockup antes do planejamento detalhado de AU2 | Pendente |

## 9. Ramos desta entrega
- AU1-B4 ensaio equivalente (docs/76): ensaio de volume e recuperação (encerrado).

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
