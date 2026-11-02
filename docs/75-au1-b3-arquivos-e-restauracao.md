# AU1-B3 — arquivos mensais e restauração dos serviços

Status: aprovada no merge do PR #109; sem ativação. Data: 01/11/2026. Ensaio equivalente posterior em [docs/76](76-au1-ensaio-equivalente.md).
Responsável pela aprovação: administrador do projeto. PR #108 incorporado mediante autorização.

## Planejamento revisto antes da fase

1. Manter a chave UUID global e o banco como fonte autoritativa. Criar cópias mensais cifradas, verificáveis e imutáveis por execução, sem retirar eventos do banco.
2. Ensaiar cópia completa do banco de teste, snapshot Redis com pendência e recuperação em contêineres novos, sem reutilizar os volumes da origem.
3. Comparar manifesto antes de consumir pendências; depois verificar replay idempotente, grants restaurados e arquivos mensais. Não publicar backups ou chaves como artefatos do CI.
4. Registrar limites: CI descartável não equivale ao contêiner completo Proxmox, ao volume real nem à janela operacional. Estes continuam requisitos da ativação, que não foi autorizada.

## Decisão de arquitetura

Adotar arquivo mensal cifrado como cópia de conservação. Cada execução gera pacote novo com linhas cifradas autenticadas e manifesto de contagem/digest. Meses fechados podem receber eventos tardios: produzir nova revisão e conservar a referência anterior; nunca sobrescrever. A preservação excepcional bloqueia a futura eliminação do mês inteiro; arquivar não autoriza expurgo.

Alternativas: particionamento nativo exigiria incluir o mês em toda chave única, enfraquecendo a unicidade global de UUID; ledger global separado adicionaria transação entre duas tabelas, migração e manutenção. A cópia mensal preserva a garantia atual sem mudança de esquema, mas não reduz o tamanho do banco nem acelera consultas. O ledger pode ser reconsiderado após medição de volume.

Fonte: [limitações oficiais do MariaDB](https://mariadb.com/docs/server/server-usage/partitioning-tables/partitioning-limitations).

## Consequências e pendências

- A chave de cifra da aplicação deve ser conservada separadamente junto às instruções de recuperação; sua perda impede leitura dos arquivos e da fila. Não compartilhar a chave nos manifestos.
- Digests detectam divergência diante de referência confiável; não impedem adulteração conjunta por quem controla chave e cópias. Referência independente e acesso restrito continuam necessários.
- A verificação do pacote é local e não importa eventos no banco. Para recuperação operacional usar backup consistente do banco e conferir o manifesto.
- Atualização posterior: recuperação completa e volume sintético validados em docs/76. Antes de ativar continuam necessários backup final atual nas duas camadas, revisão real de grants, monitoração e autorização da release consolidada. T5 pode retomar o planejamento de desenvolvimento, sem ativação de cópias reais.
