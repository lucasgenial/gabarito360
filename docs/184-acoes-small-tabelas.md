# Ações SMALL e densidade das tabelas

Planejamento revisto em 08/11/2026 antes da implementação. Base main375a1dc, homologação6ced29b. Esta é uma adequação transversal solicitada duranteP3.2; não iniciaP3.3. Pré-consulta independente: operacao-20261102/botoes-sm-design-previo.md (UI/UX e Product Design).

**Revisão posterior, antes da implementação:** o responsável escolheu Tailwind e autorizou preservar somente as cores como restrição estética. O plano técnico de compactar o CSS próprio foi substituído pela migração descrita em185-migracao-tailwind.md. Os critérios funcionais, de acessibilidade e integridade abaixo permanecem; fontes13px/padding/radius do CSS antigo não são mais a referência. Nova direção independente: escalaTailwind, textoSM14/20, fonte de sistema, min-h-8/rounded-md/px-3/gap-2 para ações, alvo44 no toque; validar a composição completa antes do aceite. A tabela abaixo registra o plano anterior para rastreabilidade, não autoriza implementar uma camada de remendos sobre o CSS atual.

| Entrega | Decisão e critério |
|---|---|
| Inventário | Conferir as56 tabelas em47 views,53 usos de x-tabela, ações de cards e as14 fontesJS que criam botões. O inventário estático é aproximado; não equivale a testar todas as telas |
| Variante compartilhada | Marcar explicitamente as ações internas com btn-sm; carregar a densidade somente no layout autenticado e em mídia screen. Não selecionar todo botão por estar dentro de um.card |
| Tamanho | Mínimo32px, texto13px, ícone16px, gap6px; altura natural para rótulos longos. Alvos44px em toque/telas pequenas, sem hitboxes sobrepostas |
| Semântica | Consultas e edição: texto curto ou ícone+texto; navegação/paginação: texto; imprimir/PDF/filtro/fechar podem usar ícone com nome acessível. Publicar/Salvar/Transferir/Encerrar/Retirar preservam texto e efeito explícitos. Editar e Arquivar/Desarquivar turma sempre ícone+texto |
| Linhas | Células8px vertical/12px horizontal, altura determinada pelo maior conteúdo da própria linha. Ações quebram em fluxo, sem reserva genérica150px nem margens somadas. Não cortar nomes ou observações para forçar altura |
| Cards móveis | Reduzir padding/gaps redundantes mantendo todos os dados e ações, leitura em ordem e alvo44. Matrizes e tabelas compactas conservam a apresentação específica |
| Exceções próprias | Widgetcontrols32face/44target, títulos de ajuda, avatar/foto/galeria, captura de câmera, calendário, abas e seleção mantêm os componentes próprios. Não resetar dimensõesGridStack/preferências |
| Proteções | Alterações de classe/ícone não mudam handlers, ações HTTP,CSRF,UR4,permissões ou contratosAPI. Não introduzir JS inline ou confirmação nativa. Não alterar impressão/PDF,autenticação/página pública |
| Validação | RegressõesWeb/JS, conferência de tabela/cards, nomes longos/múltiplas ações, edição/arquivamento, seleção/paginação, imagens e estados; desktop/320/390,zoom e tema escuro; aprovação final independente atual noChrome |
| Publicação | CI,merge e homologação isolada com backup/controle de integridade; nenhuma migração ou publicação em produção |

A cobertura por família e as evidências finais serão acrescentadas antes da integração. A verificação visual usa dados fictícios nos perfis que a sessão real não permite; a conferência real não altera vínculos para obter imagens.
