# 100 — Publicação inicial V1–T5 na homologação

Em 02/11/2026, o responsável autorizou a publicação, as migrações e a criação das contas internas, exigindo preservação dos gabaritos. A versão `a90fbf51d1ddc89a34b808c9de1c2997d1705914` (PR #133) foi publicada no CT 111, partindo de `af4c6d1e9e1d324ebd10e404af149b78a28270a4`. A CI da main foi aprovada no run 41492847186.

## Preservação e recuperação

Foram gerados backups completos dos CTs 106/111 para ensaio, copiados ao computador com SHA-256 e restaurados em contêineres isolados. As 21 migrations e a conversão dos vínculos foram ensaiadas nessa cópia. Depois, com escritores e PHP-FPM parados, foi gerado um novo conjunto FINAL, igualmente copiado, verificado e restaurado integralmente antes de liberar a migração no servidor.

Backups finais: `vzdump-lxc-106-2026_11_02-15_10_12.tar.zst` e `vzdump-lxc-111-2026_11_02-15_13_09.tar.zst`, em duas camadas. O recibo local é `D:\Backups-Proxmox\ATIVACAO-FINAL-COPIA-20261102.json`. Nenhum backup anterior foi excluído. O SSD foi protegido e desmontado ao fim.

A restauração final recuperou MariaDB íntegro, chaves, seis serviços e health HTTP 200 em aproximadamente 510 segundos. Os clones 9106/9111 ficaram sem rede externa e foram removidos após conferir sua identificação e volumes próprios; os contêineres de referência não foram substituídos.

Comparação do conteúdo das colunas originais de 13 tabelas e do SHA-256 dos arquivos, antes/depois das migrations e também no backup final restaurado:

| Conteúdo | Preservado |
|---|---:|
| Gabaritos | 4 |
| Questões de gabarito | 180 |
| Cartões e respectivos arquivos | 47 |
| Respostas | 2.115 |
| Notas | 47 |
| Alunos | 414 |
| Turmas | 14 |

Nenhum gabarito foi recriado, apagado ou reprocessado. Não houve carga de dados escolares de teste no servidor.

## Atualização e acessos existentes

O B5 aplicou o SHA aprovado e 21 migrations, mantendo manutenção até a conversão e a conferência da auditoria. O ajuste operacional de login #98 foi preservado e reconciliado antes do fast-forward; em restauração do backup final, sua cópia privada foi reaplicada antes de limpar o cache antigo. Não é seguro restaurar apenas Git depois de DDL.

Seis contas com escopos escolares foram convertidas em sete vínculos, sem ampliação de permissões. O administrador conserva suas duas redes separadas. A conta de aluno que já não tinha escopo escolar permanece sem vínculo: não foi inventada uma escola nem desativada sua identidade. A conciliação aprovou o conjunto.

Auditoria ativada com credencial separada de SELECT/INSERT; UPDATE, DELETE e ALTER foram efetivamente negados. Retenção de 180 dias, sem expurgo. Flags de vínculos, acessos internos e cópias foram ativadas. Nenhuma cópia de dados foi iniciada por essa ativação.

A sessão web usa agora o cookie `gabarito360_v1_session`, exigindo novo login para carregar a seleção de vínculos. O prazo continua em 120 minutos de inatividade.

## Verificações de funcionamento

- Administrador: dois vínculos disponíveis, quatro gabaritos acessíveis; quatro leituras pela rede indevida recusadas com 404.
- Web HTTPS: login, seleção das duas redes, painel/provas/cópias (seis páginas), marcador da versão e logout aprovados.
- A página intermediária de contexto depende de JavaScript; o cliente HTTP do ensaio seguiu explicitamente seu destino. Isso não representa conferência visual pelo Computer Use.
- Serviços retomados e acompanhamento de filas, jobs falhos e logs por procedimento operacional. Evidência detalhada e monitoramento ficam em área privada; não versionar logs completos ou credenciais.

[Evidência sanitizada da publicação](operacao/evidencias/ativacao-v1-t5-2026-11-02.json).

## Contas internas: pendência separada

**Não criadas no servidor neste registro:** `dev@gabarito360.com.br` / Desenvolvedor, `suporte@gabarito360.com.br` / Suporte e `financeiro@gabarito360.com.br` / Financeiro.

O cadastro de identidade exige CPF não nulo. Foi solicitada a escolha entre identificadores fictícios explicitamente marcados como teste ou CPFs informados pelo responsável. A ausência de resposta não autoriza preencher esse dado.

A criação foi ensaiada apenas no clone descartado, mantendo o hash de senha do administrador existente e sem atribuir vínculo escolar. Desenvolvedor geriu acessos internos; Suporte/Financeiro foram impedidos de fazê-lo; os três não receberam acesso pedagógico. Após a resposta, criar sob manutenção com configuração interna desligada para o bootstrap, usando os serviços de domínio e transação; reabilitar configuração/cache e verificar cada perfil. Recusar duplicação caso uma conta já exista.

## Próxima execução

O uso rotineiro exige a observação operacional final e o encerramento do marcador da primeira ativação. Usar o [fluxo por fase](99-publicacao-por-fase.md) a partir do SHA realmente publicado. A criação das contas e o aceite visual do responsável permanecem tarefas identificadas, sem bloquear por si só a consulta aos gabaritos existentes na versão publicada.
