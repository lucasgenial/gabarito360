---
id: ADR-0059
titulo: O aplicador vê a leitura da folha atual, antes de salvar, sem corrigir
estado: Aceita
substituida_por: []
data: 2026-11-11
afeta_funcionalidades: [FN-079]
afeta_caminhos: ["apps/web/**", "apps/api/**"]
verificacao: revisao-ia
regras: []
agents_secao: Sessão e vínculo
agents_ordem: 20
agents_resumo:
  - "O aplicador (APL, chave `cartao.enviar`, só na data de aplicação) vê as marcações lidas apenas da folha atual, antes de salvar, para refazer a foto. Ele não corrige: não tem `correcao.alterar`."
  - "O aplicador nunca vê gabarito, nota, folhas anteriores, folha já salva, leitura vinda do servidor nem imagens de outros envios."
---
# ADR-0059 — O aplicador vê a leitura da folha atual, antes de salvar, sem corrigir

**Data:** 2026-11-11
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** decisão D-6, opção (b), do responsável em 11/11/2026, no planejamento do Leitor360 (§8)
**Afeta:** [FN-079](../funcionalidades/079-leitor360/planejamento.md); [04 Identidade, acesso e permissões](../arquitetura/04-identidade-acesso-e-permissoes.md)

## Contexto

O aplicador fotografa as folhas no dia da aplicação. Sem ver a leitura, ele não sabe se a foto serviu. Ele só descobriria o erro depois, quando o aluno já saiu. Mostrar a leitura completa, porém, revelaria gabarito e nota, que o perfil não pode ver. O planejamento da FN-079 ofereceu três opções: só captura, ver as marcações para refazer a foto, ou ver e corrigir.

## Decisão

O responsável escolheu a opção (b). O aplicador (APL), com a chave `cartao.enviar` e só no dia da aplicação, vê as marcações lidas da folha atual, antes de salvar, para refazer a foto.

O aplicador não corrige. Ele não tem `correcao.alterar`.

O aplicador não vê:

- o gabarito;
- a nota;
- as folhas anteriores;
- uma folha já salva;
- a leitura vinda do servidor;
- as imagens de outros envios.

## Consequências

- A tela de leitura tem uma variante do aplicador, sem navegação entre folhas e sem campos de correção.
- A API não devolve gabarito, nota nem dados de outras folhas nas rotas que o aplicador usa.
- Esta regra não dá ao perfil APL nenhuma chave nova. Ela limita o que a chave `cartao.enviar` mostra.
- Esta decisão não substitui ADR anterior.

## Verificação

- Revisão de IA (`revisao-ia`): o agente de conformidade confere, em cada PR do Leitor360, que o perfil APL não recebe gabarito, nota, folha anterior, folha salva, leitura do servidor nem imagem de outro envio.
- Teste de permissão do perfil APL nas rotas do leitor, previsto na FN-079.
- O AGENTS.md gerado cita esta regra na seção Sessão e vínculo.
