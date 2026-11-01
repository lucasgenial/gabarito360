# 54 — T2 publicado na homologação

## Resultado

**T2 aplicado e em uso no servidor de homologação representativa de produção em 01/11/2026.** O responsável autorizou seguir com a recomendação de aplicar o T2 e validar o uso. Essa autorização sucede a orientação anterior de manter sem deploy durante a preparação.

Origem: `dc9949e3690744b72c5071d919955c09ad2be9b8`. Release publicado: `af4c6d1e9e1d324ebd10e404af149b78a28270a4`, merge aprovado do PR #85, com as quatro verificações de CI aprovadas antes da execução. Código atualizado no CT 111 e migração/corte executados no banco do CT 106.

Há **2 clientes internos provisórios e 13 espaços**. O administrador mantém duas atribuições separadas, uma para cada rede. A cobertura e as restrições T2 estão ativas; o inventário, a auditoria e a verificação do esquema passaram sem inconsistências. Conteúdo das colunas originais de 13 tabelas preservado por comparação agregada antes/depois. Entre as contagens conferidas: 14 turmas, 414 alunos, 47 cartões, 47 notas e 2.115 respostas.

[Evidência sanitizada da publicação](operacao/evidencias/t2-ativacao-homologacao-2026-11-01.json).

## Backup final e recuperação

O procedimento fechou API/web, drenou e parou workers/agendador/PHP-FPM. As filas pendentes, reservadas e atrasadas estavam vazias. O B5 aguardou o backup antes de atualizar código ou iniciar DDL.

| Contêiner | Arquivo final | Bytes | SHA-256 |
|---|---|---:|---|
| 106 — banco | `vzdump-lxc-106-2026_11_01-08_20_46.tar.zst` | 2.245.129.524 | `6ac8aaf1f1ad5193e9127d25e92f1b747c282fc230dee8be3f763c1632314901` |
| 111 — aplicação | `vzdump-lxc-111-2026_11_01-08_23_02.tar.zst` | 8.750.470.251 | `3e51a7083b522e07af6cdc9f37c67e22057aa48b2b17f5b09787dbe67fc46dd4` |

Ambos passaram por `zstd -t` e SHA-256 integral, foram protegidos no SSD e copiados para `D:\Backups-Proxmox\dump`, com tamanho/hash conferidos durante a transferência. Marcador pontual: `D:\Backups-Proxmox\T2-CORTE-COPIA-OK-2026-11-01-4f3150d5.json`. O marcador do ciclo diário completo não foi sobrescrito. Não houve retenção/exclusão de backups antigos nem disparo de e-mails.

A confirmação foi enviada somente após as duas cópias. Banco, arquivos e chaves pertencem ao ponto sem escritores anterior ao corte. A restauração completa do procedimento foi exercitada nos docs. 52/53; estes arquivos finais foram verificados por integridade e dupla cópia, sem nova restauração nesta janela. Recuperação após novas escritas exige avaliar o que foi produzido desde esse ponto, restaurando o conjunto coerente; Git isoladamente não desfaz DDL.

## Execução e intercorrências resolvidas

A manutenção começou aproximadamente às **08h20 BRT**. O backup final foi confirmado às **08h34**. O corte estava concluído e conferido às **08h42**. A reabertura final ocorreu às **08h50**, após retomadas breves durante a validação. Horários detalhados em UTC na evidência; são observações desta janela, não previsão de indisponibilidade futura.

A execução exigiu três ajustes operacionais, registrados para não apresentar o processo como uma passagem sem intervenções:

1. O executor iniciou o B5 com `umask 077`. O Git criou arquivos novos sem leitura para o PHP; o procedimento parou em dependências, antes de migrar, preservando manutenção. Foram normalizadas as permissões de 64 arquivos versionados afetados e de diretórios novos, sem modificar conteúdo, `.env` ou arquivos locais não versionados. As etapas restantes de dependências, migração aditiva e caches foram retomadas com `umask 022`, no mesmo SHA aprovado. O backup permaneceu válido porque os escritores continuaram parados.
2. O auxiliar de verificação chamado `http.py` colidiu com a biblioteca padrão do Python. Foi renomeado, e a primeira conta temporária foi removida antes da repetição.
3. O Cloudflare recusou a assinatura padrão do cliente Python com erro 1010. A verificação usou identificação de navegador no cliente, sem alterar regras, configuração ou proteção do site. O acesso HTTPS e o login completo foram então validados.

O preenchimento e o corte DDL foram executados uma vez. A verificação posterior confirmou zero passos pendentes. As retomadas de validação não repetiram DDL nem restauraram dados.

## Validação do uso e retomada dos serviços

- Início, login e saúde da API responderam 200 no nginx local e pelo domínio HTTPS.
- Login real via API e web; painel e relatórios explícitos das duas redes responderam 200.
- Uma conta temporária vinculada apenas à primeira rede recebeu 404 ao tentar relatório, cartão e imagem da segunda rede. Após acrescentar seu segundo vínculo, o relatório retornou as 47 notas esperadas; cartão/respostas e imagem responderam 200, com hash da imagem preservado.
- Logout API/web confirmado. Todas as contas, vínculos e tokens temporários foram removidos; o administrador original permaneceu com suas duas atribuições. Não foram criados ou reprocessados dados escolares para essa verificação.
- Job técnico sem dados escolares consumido por um worker real; marcador removido. Os dois workers estavam ativos. O timer executou nova ocorrência de `schedule:run` com sucesso. Filas vazias, zero jobs falhos e nova auditoria sem inconsistências.

O smoke operacional verificou leitura de cartão/imagem e fila; não realizou novo upload ou reprocessamento OMR no servidor. A cobertura do fluxo OMR e das constraints do release também está nos testes e ensaios anteriores. Isso não substitui validações futuras de novas funcionalidades.

## Prevenção da falha de permissões

Esta entrega acrescenta `umask 022` ao início do B5 para não depender da máscara herdada do chamador. Temporários privados continuam protegidos por `mktemp`/`chmod`, e o hook mantém sua própria pasta privada. O teste de regressão reproduziu 0600/0700 antes da correção e passou exigindo 0644/0755 depois dela; os **20 testes do B5 passaram**.

Esse ajuste preventivo integra o novo PR e ainda depende de merge autorizado. O servidor continua no release aprovado `af4c6d1...`; sua recuperação operacional usou máscara correta sem aplicar código não incorporado ao servidor.

## Planejamento

T2 atende ao critério de cobertura dos registros e verificações aprovadas, agora também publicado. O próximo pacote é **V1 — vínculos por pessoa**: substituir o vínculo implícito do perfil por vínculos explícitos por espaço, preservando a compatibilidade. A seleção/troca de vínculo pertence a V2. A ativação T2 não implica que V1/V2 ou os demais módulos já estejam implementados.

## Estabilidade e encerramento

Conferência final às **08h57 BRT**, cerca de oito minutos após a reabertura: código no SHA aprovado e sem alterações versionadas; manutenção retirada; todos os serviços ativos; filas pendentes/reservadas/atrasadas vazias e zero jobs falhos. Auditoria, hashes e hierarquia novamente aprovados. Nenhum byte novo foi acrescentado aos logs Laravel API/web e nginx/error desde a referência anterior à abertura. Sessão web Redis de 120 minutos preservada. Os três arquivos/diretórios não versionados já existentes no servidor foram preservados.

O SSD foi desmontado ao fim da operação. As credenciais, tokens e contas temporárias foram removidos, e a marca de autorização dos auxiliares pontuais foi encerrada. Registros técnicos privados permanecem em `/root/g360-t2-ativacao-20261101` no host e no CT da aplicação; os auxiliares desse registro são pontuais e não substituem o roteiro revisado de um próximo deploy. A evidência versionada contém somente metadados operacionais, contagens e resultados.
