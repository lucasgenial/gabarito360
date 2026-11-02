# 99 — Publicação por fase na homologação

**Atualização de 02/11/2026:** publicação inicial executada após autorização, com backup final restaurado, preservação dos gabaritos e validação funcional. Consulte o [registro da execução e pendência das contas internas](100-publicacao-inicial-v1-t5.md). As seções abaixo preservam o planejamento e o diagnóstico anteriores ao corte.

Em 02/11/2026 o responsável solicitou preparar o projeto e o servidor para receber deploys por fase/microfase. A conferência visual passa a ocorrer **depois de disponibilizar a versão de homologação**, sem condicionar o merge à disponibilidade do computer-use. Isso substitui o bloqueio visual dos rascunhos E1–E3; não dispensa CI ou correção de falhas conhecidas.

## Integração concluída

PRs #125–132 incorporados, em ordem, com cinco checks aprovados em cada head e sem conflitos. Main resultante: `962d90c9f1981db6feb6d8964bcaceb70dfe8b02`. Inclui seleção, decisões, operação e acervo T5. Conferência visual do responsável ainda pendente da publicação.

## Fluxo das próximas fases

1. Revisar escopo e migrations/flags; implementar, testar e revisar o incremento.
2. Integrar o PR autorizado; conferir cinco checks e SHA exato da main. Nenhum push de branch é publicado automaticamente.
3. No CT 111, executar `/root/g360-operacao/preparar-release.sh SHA FASE`. Gera pacote privado com hash, origem publicada, diff operacional preservado, diagnóstico de migrations/flags/filas e serviços. Não troca código nem migra. Em falha, preservar o pacote incompleto e preparar uma nova tentativa; não marcar manualmente como pronto.
4. Após a primeira ativação acumulada, executar `/root/g360-operacao/publicar-fase.sh SHA ATUAL FASE`. Exige pacote preparado para o mesmo SHA anterior/fase, chama B5 e exige backup final. `FASE` é identificador como `T5-E3` ou `V3-A1`.
5. Durante o checkpoint B5, executar o backup imediato dos CTs 106/111 e copiar/verificar na segunda camada pelo procedimento existente. Confirmar com `confirmar-backup-final.py` somente após conferir arquivos/hashes e a cópia. Não esperar a agenda de 06h. Não excluir backups nem disparar mensagens.
6. B5 atualiza API/web juntas, migra somente API, reconstrói caches, retoma serviços e confere HTTP local/público. A tela autenticada mostra fase e SHA curto. Esse marcador identifica o código, não substitui o aceite funcional.
7. Responsável valida a versão em `https://gabarito360.com.br/`; registrar resultado e correções como próxima microfase. Acompanhar logs sanitizados, filas, jobs falhos e serviços por 15 minutos após a abertura. Falha crítica preserva manutenção e exige diagnóstico; não executar rollback automático de schema.

## Servidor preparado

Auxiliares instalados em `/root/g360-operacao` (root, 0700). Pacotes/evidências em `/root/g360-releases` (privados). Não há credenciais no pacote Git. Arquivos `.env`, uploads, filas e dados atuais não são substituídos na preparação.

O marcador `/root/g360-operacao/primeira-ativacao-pendente` impede usar o atalho rotineiro antes da transição inicial. Ele só será encerrado após a primeira ativação e sua verificação. Não contornar o marcador para tentar publicar a atualização acumulada como se fosse uma microfase sem dependências.

Diagnóstico aplicado no servidor: [evidência sanitizada](operacao/evidencias/preparacao-deploy-fases-2026-11-02.json). Publicado ainda em `af4c6d1e9e1d324ebd10e404af149b78a28270a4`; serviços ativos, filas vazias, zero jobs falhos; home/www e health HTTP 200. Espaço livre aproximado: aplicação 25 GiB, banco 8,3 GiB. O SSD de backup está desmontado fora de uso; o espaço do ponto de montagem não representa espaço livre do SSD.

## Primeira publicação acumulada: execução própria

Há 21 migrations pendentes, V1/V2 ainda desativados e 7 contas legadas (1 administrador de rede, 1 diretor de núcleo, 1 diretor escolar, 2 coordenadores, 1 professor e 1 aluno). Zero ajustes individuais de permissões. O inventário completo de vínculos só poderá executar depois da expansão V1: a tabela ainda não existe. Não inventar manifesto aprovado antes de conferir o esquema expandido.

1. Confirmar a release exata, CI, janela inicial e backup final coerente nas duas camadas. Guardar `.env`, chaves, configuração dos serviços e diff operacional no conjunto de recuperação. Manter integrações de envio fora dos testes; não enviar mensagens reais.
2. Reconciliar exclusivamente o hotfix PR #98 durante a janela: arquivo atual tem SHA-256 `98d893214d2ccac59a9ea4c7c978316db604dc9b46329713208c9c0d046c749a`, igual ao arquivo no commit `9088ae9b934af715040246af8767116cd2c4ff94`. Preservar cópia; restaurar somente esse arquivo à base para o fast-forward, mantendo seu cache efetivo até a atualização. Se o preflight cancelar, restaurar imediatamente o hotfix antes de qualquer reconstrução de cache. Não resetar a árvore nem remover arquivos não rastreados.
3. Usar B5 com `--manter-manutencao --backup-final /root/g360-operacao/pausa-backup-final.sh --fase INICIAL-V1-T5`. A abertura automática não é adequada: migrations expandem o esquema, mas não convertem as contas ou ativam vínculos.
4. Gerar inventário e manifesto V1, preservar exatamente os escopos existentes e os dois vínculos separados do administrador. Comparar permissões antes/depois; nenhuma ampliação implícita. Se houver ambiguidade, resolver com o responsável antes de converter. Aplicar com hash/ator, reemitir conciliação e exigir resultado aprovável; revisar tokens, convites e envios legados pendentes.
5. Preparar credencial de gravação da auditoria com INSERT/SELECT exclusivo na tabela, separada da credencial de migration; ensaiar negação de UPDATE/DELETE/ALTER. Guardar senha somente no `.env` protegido e cofre operacional, nunca em logs/versionamento. Configurar 180 dias e preservação excepcional, sem expurgo. Preparar papel interno do responsável separadamente, sem transformar automaticamente outros usuários em desenvolvedores.
6. Ativar vínculos e recursos preparados na janela, incluindo interface de cópias em API/web. Ativar execução das modalidades somente com auditoria, decisões, agendador e filas verificados. Flags necessárias: `VINCULOS_OBRIGATORIOS`, `AUDITORIA_ATIVA`, `COPIAS_ATIVAS`, `COPIAS_INTERFACE_ATIVA`, `COPIAS_AUDITORIA_ENTREGA`, `COPIAS_EXECUCAO_ATIVA`, `COPIAS_CADASTRO_EXECUCAO_ATIVA`, `COPIAS_RESULTADOS_EXECUCAO_ATIVA`; acesso interno tem sua própria configuração e atribuição.
7. Reconstruir caches como www-data, conferir corte T2 e vínculos, retomar serviços e validar login/seleção dos dois espaços, isolamento, páginas alteradas, health e filas. Nenhuma cópia real é iniciada apenas por ativar flags: exige as decisões e pedido explícito. Validar com dados fictícios separados e registrar aceite no servidor.
8. Concluída a verificação inicial, encerrar o marcador e passar ao fluxo rotineiro por fase. A preparação atual não executou esta migração/ativação, não alterou `.env` e não colocou o sistema em manutenção.

## Recuperação

Antes da atualização, restaurar somente os estados que o procedimento mudou. Depois de DDL, não voltar Git isoladamente nem apagar tabelas: manter manutenção e avaliar restauração coerente de banco/Redis/arquivos/chaves, preservando escritas posteriores e evidências. Backups completos já possuem ensaio de recuperação nos docs. 52/76; isso não afirma restauração de um novo backup que ainda será gerado na janela.
