# 045 — Motor OMR e acompanhamento da correção — Planejamento

**Implementação:** Concluída
**Etapa:** Concluída
**Observação de estado:** com ressalva: teste com cartão impresso real pendente
**Prioridade no plano mestre:** fora das ondas (funcionalidade concluída); a ressalva é pendência do §3 sem onda própria. O §5 é PROPOSTA, ainda não aprovada.
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/13 MP-021 e MP-033; docs/19 MP-038 (mesma entrega que MP-033); docs/08 e docs/18
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Ler por visão computacional os cartões-resposta fotografados, calcular a nota e permitir que professores, coordenação e direção acompanhem a correção de cada prova e de cada turma até que todos os alunos esperados estejam corrigidos.

## 2. Escopo
- **Inclui:** geração do PDF de cartões com QR e marcadores (`omr/gerar_cartoes.py`, `omr/layout.py`); envio de fotos pela câmera, galeria ou upload; leitura nativa (`omr/ler_cartao.py`, `NativoOmrDriver`); redirecionamento pelo QR para a prova correta da mesma escola; resolução de ambíguos; vinculação manual de cartão sem aluno; exclusão de correção; fechar e reabrir a correção; acompanhamento por prova e por turma; atualização automática do status da prova (`publicada` → `em_correcao` → `corrigida`).
- **Não inclui:**
  - Fila assíncrona, idempotência dos envios e worker → FN-002 (B6).
  - Miniaturas, tela de revisão questão a questão, ajuste de limiares (R3) e leitor PDF da revisão → FN-046.
  - Barreira de qualidade da foto, tentativas numeradas e histórico imutável, documentos verificáveis → FN-047 (OMR1–OMR3).
  - Driver OMR externo → FN-048.
  - Resultados e relatórios → FN-049.

## 3. Fontes de verdade
- Mockups: `mockups/acompanhar-correcao.html`, `mockups/acompanhar-correcao-turma.html` (inventário em [docs/01](../../01-inventario-dos-mockups.md), seções 11 e 12).
- Documentos de origem: [docs/08](../../08-omr.md), [docs/18](../../18-omr-nativo-e-cartoes.md), [docs/13](../../13-roadmap.md) (MP-021, MP-033), [docs/19](../../19-plano-telas-por-perfil.md) (MP-038), [docs/12](../../12-arquitetura.md) (DA-004).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md); UC-004 do [docs/07](../../07-casos-de-uso.md).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md) §4.5 e §5; código em `apps/api/app/Support/CatalogoPermissoes.php`.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-040 provas-gabaritos | A prova precisa estar publicada e ter gabarito | Concluída |
| FN-002 base-operacao (B4, B6) | Fila Redis e leitura assíncrona dos envios | Concluída (com ressalva) |
| FN-012 permissoes-motor | Chaves `cartao.*` e `correcao.*` | Concluída |
| FN-021 clientes-espacos | Isolamento por espaço (`ContextoCorrecao`) | Concluída |

**Funcionalidades que dependem desta:** FN-046 (revisão de leituras), FN-047 (qualidade e tentativas), FN-048 (driver externo), FN-049 (resultados), FN-052 (painéis com progresso de correção), FN-053 (operações de correção no painel da turma).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (MP-021) Motor OMR nativo e cartões com QR | Leitura Python/OpenCV, PDF de cartões, ambíguos, vinculação | Leitura e nota gravadas; ambíguos resolvíveis | Entregue (produção) | commit 15e26f4; docs/18 |
| 1.2 (MP-033 = MP-038) Acompanhamento por turma | Provas × alunos da turma | Tela `acompanhar-correcao-turma` funcional | Entregue (produção) | commit b1b8c0f |
| 1.3 Teste com cartão impresso real | Validar leitura com foto de cartão impresso | Registro textual do teste com foto real | Pendente | docs/18 ("Pendente: teste com foto real") |

## 6. Contrato técnico (proporcional ao risco)
- **API** (`apps/api/routes/api.php`, prefixo `v1`, `auth:sanctum` + `contexto_vinculo`): `provas/{id}/cartoes` (GET lista, POST envio), `provas/{id}/cartoes/status`, `provas/{id}/cartoes/pdf`, `provas/{id}/cartoes/envios/{chave}` (+ `/repetir`), `provas/{id}/correcao/fechar|reabrir`, `cartoes/{id}` (GET/DELETE), `cartoes/{id}/vincular-aluno`, `cartoes/{id}/resolver-ambiguidade`, `turmas/{id}/correcao`. Controladores: `CartaoController`, `AcompanhamentoTurmaController`.
- **Web (BFF)**: `CorrecaoController` (rotas nomeadas `correcao.*`, `cartoes.*`). Os caminhos efetivos ficam sob `/app` sem identificadores na URL (UR4, `App\Support\RotasNavegacao`; ver FN-018).
- **Dados**: `cartoes`, `cartao_respostas`, `notas`, `ambiguidade_logs` (migrations `2026_01_01_000012..15`), `provas.correcao_fechada_em/por` (`2026_10_30_000002`), `cartao_envios` (`2026_10_31_000001`, `2026_11_01_000003`).
- **Autorização**: escopo da prova por `Escopo::prova`; o aplicador só envia (demais ações devolvem "Prova não encontrada") e só vê provas com data de aplicação igual a hoje. Correção fechada bloqueia envio, exclusão, vínculo e revisão para quem não tem `correcao.reabrir` (`App\Support\Correcao`).
- **Progresso**: `ProgressoCorrecao` conta alunos ativos esperados; a prova vira `corrigida` quando todos têm cartão lido com nota (`AtualizadorStatusProva`).
- **Driver**: `OmrDriverInterface` com `nativo` ou `simulado` (`config/omr.php`, `OMR_DRIVER`).
- PDF de cartões é exceção documentada à regra "API só JSON" (download de arquivo).

## 7. Interface
Telas `correcao.index` (Corrigir), `correcao.show` (acompanhar prova), `correcao.camera` (aplicador), `correcao.turma` (acompanhar turma). Estados de carga e erro seguem FN-076 (breadcrumb e toast só nas telas de prova, correção e resultado). Sem prints; conferências registradas por texto.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 1 — aprovação do plano mestre e da priorização | Aprovar §1–§3 e Onda 0 / discutir | Aprovar o inventário (§1–§3) e a Onda 0 | Pendente |
| Teste com cartão impresso real (§3) | Executar na homologação / em produção com a ressalva de B6 (FN-002) | Executar junto com a validação de B6 com cartão real | Pendente |
| ⚠ UC-004 (docs/07) prevê "Leitura concluída" quando pendentes = 0; o código conclui por **alunos esperados** (alunos ativos das turmas da prova), não por cartões pendentes | Atualizar o docs/07 / manter | Registrar o critério por aluno como vigente (introduzido em OMR2, FN-047) | Pendente |

## 9. Ramos desta entrega
Nenhum aberto. Os ramos históricos (qualidade, tentativas, revisão) viraram FN-046 e FN-047.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
