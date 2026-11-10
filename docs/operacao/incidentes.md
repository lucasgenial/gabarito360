# Incidentes

**Atualizado em:** 10/11/2026, por lucasgenial
**Ambiente:** ambos (produção e homologação)
**Quando usar:** tratar uma indisponibilidade, um erro em massa ou um possível incidente de segurança com dados pessoais; consultar o histórico de incidentes já registrados.
**Duração estimada:** depende do incidente. O primeiro diagnóstico leva de minutos a 1 hora.

Este documento reúne um procedimento provisório de resposta e o histórico resumido dos incidentes registrados. O plano formal de incidentes de segurança com dados pessoais (L5, [FN-065](../funcionalidades/065-plano-incidentes/planejamento.md)) está **pendente** e depende da auditoria. Até ele existir, use o procedimento abaixo. A base legal está em [11-seguranca-e-privacidade.md](../arquitetura/11-seguranca-e-privacidade.md).

> **Atenção:** em qualquer incidente, não apague dados, não faça reset do Git, não reverta migrations e não desative proteções para "fazer funcionar". A produção só muda com autorização expressa do responsável. Um incidente com dados pessoais pode obrigar a comunicar a ANPD (Autoridade Nacional de Proteção de Dados) e os titulares.

## Pré-requisitos

- Acesso ao Proxmox (`pct exec 111`) e, para homologação, à VM 120.
- Acesso de leitura aos logs sanitizados e à fila (`php artisan queue:failed`).
- Os backups das duas camadas em dia ([backup-e-restauracao.md](backup-e-restauracao.md)).
- Um local privado fora do Git para registrar evidências.

## Passos: procedimento provisório

1. **Registre o início.** Anote a data e a hora (UTC e Brasília), quem detectou e o sintoma. Use uma pasta operacional restrita, nunca o Git.
2. **Classifique.** O incidente é de disponibilidade (lento, fora do ar, erro 500) ou de segurança com dados pessoais (vazamento, acesso indevido, perda)? No segundo caso, avise o responsável de imediato.
3. **Colete evidências de leitura.** Confira os serviços (`nginx`, `php8.3-fpm`, `redis-server`, os dois workers e o timer), o health local, o espaço em disco e as filas. Consulte os logs sanitizados. Não imprima `.env`, chaves, tokens, SQL com valores nem dados de alunos.
4. **Preserve.** Para um problema de segurança, preserve logs, `failed_jobs` e a auditoria do período. Registre o intervalo de uma possível lacuna da auditoria.
5. **Contenha com a menor mudança possível.** Prefira um ajuste pontual e reversível a uma publicação inteira. Não publique toda a `main` para corrigir um incidente isolado. Se precisar fechar o sistema, use a manutenção do Laravel e a home independente ([publicacao-producao.md](publicacao-producao.md)).
6. **Corrija na homologação primeiro**, quando o problema for de código. Ajustes de infraestrutura podem ser feitos direto no servidor afetado, com aprovação.
7. **Faça cópia de segurança antes de alterar um arquivo no servidor.** Guarde o original, o diff e o registro.
8. **Valide.** Confirme o health, o login e o fluxo afetado. Peça o aceite funcional ao responsável.
9. **Para incidente de segurança:** o responsável decide sobre a comunicação à ANPD e aos titulares. A Resolução CD/ANPD 15/2024 trata dos incidentes de segurança. A norma fala em prazo de três dias úteis para a comunicação. Confirme a norma vigente antes de contar o prazo. Os modelos de aviso e a simulação ainda não existem (L5).
10. **Registre o encerramento** neste documento: diagnóstico, correção, aceite, o que não foi verificado e as ações preventivas.

## Verificação

1. O sintoma original desapareceu e não há novos erros no log durante a observação (15 minutos para publicações).
2. Os serviços estão ativos, as filas esvaziaram e `failed_jobs` não cresceu.
3. Nenhum dado foi apagado ou alterado fora do planejado.
4. O responsável deu o aceite funcional.

## Reversão

1. Reverta só o que o procedimento alterou: restaure o arquivo de configuração original e refaça o cache da aplicação.
2. Não faça rollback de banco automático. Depois de um DDL, siga a seção "Reversão" de [publicacao-producao.md](publicacao-producao.md).

## Referências

- [11-seguranca-e-privacidade.md](../arquitetura/11-seguranca-e-privacidade.md)
- [publicacao-producao.md](publicacao-producao.md)
- [backup-e-restauracao.md](backup-e-restauracao.md)
- [auditoria.md](auditoria.md)
- [FN-065 Plano de incidentes](../funcionalidades/065-plano-incidentes/planejamento.md)

## Histórico de incidentes

### 30/10/2026: o site parou com 47 fotos pedidas ao mesmo tempo

- **Sintoma:** o site travou. A web chama a API por localhost. As duas usavam o mesmo grupo de processos PHP-FPM.
- **Causa:** 47 fotos pedidas de uma vez esgotaram os processos.
- **Correção:** grupos de processos separados para a web (`www`, 12) e para a API (`api`, 12, socket próprio).
- **Prevenção:** preserve a separação ao mexer em nginx ou deploy. A etapa F4 de desempenho não paraleliza as chamadas da web à API por esse motivo. As miniaturas da correção carregam por visibilidade. Ver [12-desempenho.md](../arquitetura/12-desempenho.md).

### 01/11/2026: erro 500 depois do login (painel do professor)

**Diagnóstico.** O servidor CT 111 estava no commit `af4c6d1`, com o adaptador T2 ativo. Às 14:37 (Brasília), o login respondeu 200 e a web redirecionou. A consulta seguinte a `/api/v1/dashboard/professor` passou dos 15 segundos do cliente HTTP da web. Isso gerou `ConnectionException` e resposta 500. Não foi falha de senha, DNS ou do redirecionamento `www`.

Nginx, PHP-FPM e Redis estavam ativos, e o health local respondeu em 9 ms. Uma reprodução somente de leitura do controlador confirmou o gargalo: as consultas com subconsultas de isolamento e joins passavam tempo excessivo no planejamento do MariaDB, configurado com `optimizer_search_depth=62`. Nenhum filtro de acesso foi removido.

**Correção.** Inicializar as conexões PDO MySQL e MariaDB **da API** com `SET SESSION optimizer_search_depth=4` (`apps/api/config/database.php`). A alteração não muda a configuração global do banco, dados, esquema, filtros de autorização nem o timeout HTTP. Vale também para reconexões. SQLite e a web não mudam. Em conexão isolada de diagnóstico, o painel respondeu 200 em cerca de 0,76 s com profundidade 4 (e abaixo de 1 s com profundidade 1). Um valor menor pode produzir planos menos eficientes em outras consultas; a suíte MariaDB e o acompanhamento dos endpoints fazem parte do aceite.

**Aplicação.** O PR 98 foi integrado no commit `9088ae9`. Às 17:05 (Brasília) de 01/11/2026, com autorização expressa, somente o arquivo `apps/api/config/database.php` foi atualizado no CT 111. A base publicada permaneceu `af4c6d1`. Não houve migration, mudança de flags nem publicação das fases pendentes. O original, o diff e o registro foram guardados em uma pasta privada no CT 111. O cache da API foi reconstruído como `www-data` e o PHP-FPM foi recarregado. Conexão e reconexão confirmaram profundidade 4 (global 62). O controlador do professor respondeu 200 em 0,842 s. Login e health públicos retornaram 200. O responsável confirmou: "perfeito, agora funcionando". Incidente encerrado com aceite funcional.

**Pontos de atenção.**

- Rankings com empate não têm critério secundário de ordenação. Planos distintos podem retornar alunos diferentes na última posição empatada.
- O controlador ainda gerava cerca de 1.100 consultas nesse cenário. A redução (F2, 2.500 → 32 no painel do professor) veio depois, na homologação ([12-desempenho.md](../arquitetura/12-desempenho.md)).
- O próximo deploy precisa reconciliar esse único diff operacional com a correção já integrada à `main` antes da checagem de árvore limpa. Isso foi feito na ativação de 02/11/2026.
- Procedimento: aplique **só** as duas opções PDO sobre o commit publicado, com cópia do arquivo, conferência do diff e reconstrução do cache. Não execute migrations nem altere flags. Se houver regressão, restaure só o arquivo de configuração anterior e reconstrua o cache. Nenhum rollback de banco é necessário.

### 01/11/2026: intercorrências do corte T2 (resolvidas)

1. O executor iniciou o B5 com `umask 077`. O Git criou arquivos sem leitura para o PHP e o procedimento parou nas dependências, antes de migrar, mantendo a manutenção. As permissões de 64 arquivos versionados e dos diretórios novos foram normalizadas, sem alterar conteúdo, `.env` ou arquivos locais. O backup permaneceu válido. O B5 passou a aplicar `umask 022`, com teste de regressão.
2. Um auxiliar de verificação chamado `http.py` colidiu com a biblioteca padrão do Python e foi renomeado. A primeira conta temporária foi removida antes da repetição.
3. A Cloudflare recusou a identificação padrão do cliente Python (erro 1010). A verificação usou identificação de navegador no cliente, sem alterar regras da borda.
4. Na restauração de ensaio, o relatório consolidado ficou mais de 127 segundos no estado `Statistics` do MariaDB, porque o otimizador juntava as subconsultas de permissão numa árvore enorme de joins. A correção materializou as tabelas derivadas com `DISTINCT` e levou o relatório a 259 ms, preservando os filtros de acesso.

### 03/11/2026: login recusado na homologação após a cópia de dados

O formulário mostrava "E-mail ou senha incorretos". A URL base da web continha `/api/v1`, e o controlador acrescenta `/v1`, o que gerava `/api/v1/v1/auth/login` (404). Corrigido apenas na configuração da homologação. Nenhuma senha foi redefinida. Os testes diretos de API e o GET da página não cobriam o caminho. Detalhes em [copia-producao-homologacao.md](copia-producao-homologacao.md).

### 03/11/2026: permissões de diretórios extraídos na homologação

O primeiro ensaio operacional da release `68671c8` detectou permissões restritivas nos diretórios extraídos. A homologação ficou em manutenção, sem abrir código parcialmente acessível. Os diretórios de código foram corrigidos para 755 e os caches e configurações para 750 e 640. O backup foi preservado e o manifesto acadêmico foi confirmado antes de trocar o link. O `deploy.py` normaliza as permissões antes do Composer.

### 09/11/2026: parada da publicação F4-A na homologação

A primeira tentativa parou na conferência do marcador anterior, antes de qualquer alteração: o gerador de operador trocava também a fase anterior pela nova. O gerador foi corrigido, o operador falho foi arquivado fora do caminho e a segunda tentativa publicou. Depois da publicação, a sessão Chrome do responsável caiu, porque o PHP-FPM reiniciou.

### 04/11/2026: revisão de leituras com erro 500

A lista de 53 cartões executava 47.401 consultas (39,9 s) e a revisão de 45 questões de um cartão, 40.276 consultas (34,8 s), em produção. O cliente web expira em 15 s, o que gerava `ConnectionException` e erro 500. A emissão de links repetia a autorização e o escopo a cada foto e a cada questão. A correção prepara a autorização dos links em lote por prova. O responsável decidiu que o código seja corrigido na homologação e que a promoção à produção valha só para esse hotfix isolado. O registro completo pertence a [FN-046](../funcionalidades/046-revisao-leituras/planejamento.md).

## Divergências encontradas

| Documento antigo | Divergência | Vale |
|---|---|---|
| `docs/28` (L5) | Previa `docs/operacao/incidentes.md` com registro, responsáveis, modelos de aviso e simulação | Este arquivo existe com um procedimento provisório e o histórico. Os modelos de aviso e a simulação seguem pendentes |
| Registro do incidente de 01/11 | Chamava o ambiente de "homologação representativa" | O CT 111 é a produção |
