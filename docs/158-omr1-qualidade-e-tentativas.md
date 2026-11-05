# OMR1 — qualidade de imagem e tentativas rastreáveis

05/11/2026. Planejamento revisto antes de implementar. Urgência: foto incompleta foi aceita pelo detector, que escolheu elementos internos como marcadores. Diagnóstico somente de leitura na homologação: cartão65/prova4,45 questões,13 brancos vigentes,25 registros de revisão; reexecução isolada do motor antigo produz29 brancos. Dois dos quatro candidatos estão fora dos cantos. Não recalcular, corrigir ou reprocessar respostas/notas reais automaticamente; preservar as correções humanas.

| Etapa | Entrega | Estado inicial |
|---|---|---|
| OMR1-A | Qualidade obrigatória e imediata em câmera/galeria/upload; quatro marcadores completos, geometria, resolução e nitidez; guarda repetida no worker | Diagnóstico concluído; implementar |
| OMR1-B | Reenvio sem sobrescrita silenciosa, conflito claro e tentativa numerada; histórico de fotos/leitura/nota/alterações preservado e protegido no banco | Implementar |
| OMR1-C | Resultado com recorte/folha digitalizada em painel PDF no desktop e modal no compacto; reuso do leitor existente | Direção inicial consultada; adaptar ao painel solicitado |
| OMR1-D | Ensaios de visão, regressões, concorrência/isolamento, revisão visual independente, CI/merge e homologação com backup/restauração | Após implementação |
| Produção | Somente após decisão posterior do responsável | Não publicar |

Consulta prévia UI/UX/Product Design: separar foto recebida, qualidade conferida e leitura concluída; mensagens específicas por arquivo; lote continua após recusas; capturar manualmente não contorna validação. Não concluir nota/zero/brancos de uma foto recusada. Muitos brancos isoladamente não demonstram defeito: folha realmente em branco é válida e deve continuar distinta de falha de imagem. Toda falha geométrica deve bloquear correção automática, nunca produzir brancos com confiança artificial.

Histórico: tentativas de transporte/worker com a mesma foto não incrementam número de captura. Cada nova foto identificada deve ter autoria/contexto/data/hash e resultado; exclusão/reenvio não pode eliminar a anterior nem suas revisões. Correção vigente permanece até nova leitura explicitamente confirmada. Guardar snapshot imutável e arquivos privados, sem cascatas; leitura autorizada pelo mesmo aluno/prova/espaço e permissões vigentes, nunca por URL pública. Não prometer que hashes, por si só, impedem adulteração por administrador do servidor. Prazo de guarda escolar segue contrato aprovado; não implantar retenção eterna nem exclusão automática nesta entrega.

Resultado: no desktop, painel ao lado de Questão por questão; compacto, botão Visualizar cartão-resposta abre modal PDF. Documento é a foto digitalizada do aluno, não o gabarito oficial nem o PDF do boletim. Autorização própria para foto além do resultado; download apenas ao abrir no compacto, sem aba externa. Manter foco/Esc, zoom, ajuste, erro/retry e proteção local de cópia do leitor existente.

Validar fixtures sem dados reais: cartão íntegro, perspectiva tolerável, marcadores cortados/ausentes, falso quadrado interno, imagem pequena/desfocada, folha em branco legítima, lote misto, falha técnica, repetição idempotente, duplicidade por QR/manual, concorrência, cancelamento/falha preservando vigente, novo resultado confirmado e histórico imutável/consultável. Qualidade é uma barreira conservadora, não garantia de leitura perfeita. Amostra real somente em diagnóstico isolado sem gravação e sem screenshots públicas.
