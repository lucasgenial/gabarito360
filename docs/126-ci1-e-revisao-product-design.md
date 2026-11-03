# CI1-E — revisão independente de Product Design e UI/UX

03/11/2026. O responsável rejeitou o formulário inicial e determinou que toda alteração de UI/UX seja aprovada por ele ou por agente especializado. Regra permanente em AGENTS.md. A aprovação da conferência CI1-D permanece válida somente para aquela entrega.

Skill frontend-design instalada de anthropics/skills, além das skills existentes Product Design/audit e design-critique. Aplicadas ao produto existente, preservando marca, Figtree, azul, componentes e navegação. Não gerar outro projeto nem publicar um protótipo separado.

## Parecer anterior à reformulação

Revisor independente: agente product_design_review. **Versão inicial REPROVADA; direção abaixo APROVADA para implementação**, com aprovação final ainda pendente de evidência renderizada. A grade automática em cinco colunas quebrou a leitura, igualou campos básicos e avançados e deslocou município de sua UF. O protocolo usava UUID como informação principal; o detalhe não mostrava claramente estado/próximo passo, nem o nome da cidade. Erros422 eram genéricos.

## Direção aprovada

- Cabeçalho x-pagina e breadcrumbs existentes; conteúdo legível dentro da casca, sem coluna lateral de cards. No máximo duas colunas no desktop e uma no celular; nome em linha inteira.
- Três grupos verticais: identificação, localização e informações adicionais. Opcionais em disclosure, sem wizard ou numeração artificial. UF e município juntos; pesquisa municipal junto da seleção e somente após UF.
- Professor pode informar que não sabe a dependência administrativa; suporte precisa conferir a classificação antes do cadastro. Não inventar um valor para passar na API.
- Nome da instituição e estado atual como informação principal; UUID/data/código IBGE secundários. Próximo passo explícito em cada estado.
- Erros vinculados aos campos, resumo focável, manutenção dos valores, localização e chave de intenção. Foco inicial no primeiro erro.
- Um CTA principal por tarefa, voltar/cancelar secundários. Pedir complemento e indeferir em ações distintas; indeferimento tem confirmação explícita e motivo.
- Conferir desktop1440/2560 e celular390, navegação por teclado, foco, carregamento e recuperação de erro. Capturas atuais são necessárias; testes de código não substituem aprovação de design.

## Gate de saída

A interface CI1-E não está aprovada até o especialista inspecionar a nova renderização, apontar a resolução das pendências e emitir parecer final explícito, ou o usuário aprovar a versão concreta. Sem merge/publicação da UI reprovada. Sem deploy/migration/carga no servidor até fechar o L4 completo. CI1-F continua separado.
