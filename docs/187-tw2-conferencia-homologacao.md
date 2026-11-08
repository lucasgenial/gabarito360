# TW2 — complemento da conferência na homologação

Planejamento revisado antes de implementar em08/11/2026. BaseGit6d4f8fe, árvore idêntica à releasecf84249 publicada somente na homologação. CI5 e backup/conferência130tabelas/201arquivos aprovados; produção intocada. A conferência autenticada usa o coordenador de testes já conectado noChrome, sem alterar dados.

## Decisões atuais

1. Nova instrução literal do responsável: rodapé **Gabarito360 - v0.1.0 - Homologação**. Supera o número isolado. Manter posição/cores/altura e versão única; ambiente deriva da configuraçãoAPP_ENV, sem mudar.env. Preservar fase/SHA nos marcadores e recibos. Não mudar a versão0.1.0 durante esta aprovação inicial.
2. O contrato de busca existente daAPI pesquisa nomeOU série. O teste real3B retornou somente a turma3B. Ajustar o rótulo para **Nome ou série**, resumo **Busca:**, mantendo campo`busca`, consulta, modal, Cancelar/Escape/limpeza e permissões.
3. Consulta prévia UI/UX/ProductDesign favorável aos dois ajustes mínimos. Nova revisão final das fontes/evidências; testes/CI, merge e publicação SOMENTE na homologação. SemDDL/API/produção. Backup deve capturar os dados atuais, não impor o inventário histórico.

## Execução

| Parte | Situação inicial |
|---|---|
| EstruturaTW2 e publicação0.1.0 | Publicada na homologação |
| Smoke real de conta, vínculo e filtro | Em conferência; consulta3B e cancelamento aprovados |
| Rodapé/contexto de ambiente e rótulo da busca | Implementar neste complemento |
| Validação/CI/revisão/merge/publicação do complemento | Pendente |
| EncerramentoTW2 | Após smoke desktop/mobile da release final |
| TW3 | Próxima etapa, sem implementação neste complemento |
