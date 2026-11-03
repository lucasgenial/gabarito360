# ADR-H1 — homologação isolada e promoção decidida pelo responsável

**Data:**03/11/2026. **Status:** fluxo aceito pelo responsável; preparação técnica autorizada por “exato, prossiga”. **Decisão de produção:** exclusiva do responsável, em momento combinado. CI1-E continua em andamento, com UI inicial reprovada e direção de reformulação aprovada no126.

## Contexto e decisão

O responsável não consegue conferir visualmente o desenvolvimento acumulado enquanto o servidor atual permanece na versão anterior. Passamos a separar desenvolvimento local, homologação permanente em homologa.gabarito360.com.br e produção atual em gabarito360.com.br. Toda entrega concluída/testada vai primeiro à homologação, com identificação visível de ambiente, fase e commit. O especialista revisa o design, o responsável valida o uso e a promoção para produção exige decisão humana explícita. Não há publicação automática em produção.

O novo ambiente será uma VM QEMU própria no Proxmox, com MariaDB local exclusivo, Redis, arquivos, usuários técnicos, chaves e backups próprios. Dados acadêmicos iniciais fictícios; dados públicos de INEP/IBGE podem ser carregados separadamente. Não clonar o banco ou as credenciais de produção para facilitar os testes. E-mail e pagamentos ficam em modo de teste, sem contas reais. Banco somente em loopback; impedir conexões da VM aos destinos operacionais atuais. Publicação HTTPS por túnel próprio, sem trocar rota do domínio principal.

## Dimensionamento e alternativas

Inventário somente de leitura: host com4 CPUs, aproximadamente23,3GiB de RAM total e18,8GiB disponíveis; storage local-lvm com aproximadamente592GiB disponíveis. Esses valores são observações pontuais, não reservas. VM proposta:2vCPU,4GiBRAM e40GiB de disco thin; Ubuntu24.04 com PHP8.3 e MariaDB próprios. VMID120 e IP10.0.0.120/24 na bridge interna vmbr1 somente após verificar ausência de configuração/resposta/conflito. Não reutilizar nem apagar contêineres existentes, inclusive os parados.

Alternativas consideradas: outro diretório no servidor atual tem menor custo mas compartilha o risco operacional; novo LXC é mais leve e segue o parque atual, mas não atende a separação por VM solicitada; VM própria adiciona sistema e manutenção, compensados pelo isolamento e pela possibilidade de ensaiar recuperação. O tamanho será revisto por uso real, sem redimensionar produção para abrir espaço.

Imagem oficial Ubuntu, HTTPS e checksum antes de importar. Instalação cloud-init documentada e guardas para não sobrescrever ID/disco/arquivos de outro ambiente. Rede e credenciais criadas para homologação. Nenhum segredo em Git ou evidência pública. Acesso de revisão protegido enquanto o ambiente estiver em preparação; forma concreta registrada após conferência da configuração de túnel disponível.

## Entregas internas H1

| Parte | Critério |
|---|---|
| H1-A | Inventário e contrato de isolamento/documentação |
| H1-B | VM nova, sistema, MariaDB/Redis próprios, rede e proteção de acesso |
| H1-C | Release identificada, dependências, migrations somente no banco novo e dados fictícios |
| H1-D | HTTPS/DNS homologa, verificações de isolamento e testes funcionais no Chrome |
| H1-E | Roteiro repetível de publicação, backup/restauração e registro de aceite; produção permanece decisão humana |

## Aceite e recuperação

Conferir antes/depois os estados dos serviços operacionais, sem tocar nos dados dos CT106/111. Provar banco/filas/arquivos separados, ausência de e-mail/pagamento real e incapacidade de conectar aos bancos operacionais. Health público/autenticado, perfil de teste, marcador de homologação e comportamento de sessões/HTTPS. Guardar backup próprio e ensaiar restauração isolada. Não declarar domínio/VM prontos por apenas criar o recurso; verificar o acesso real.

Primeiro objetivo é tornar a homologação disponível. O CI1-E será reformulado segundo o126 e passará por revisão independente no ambiente disponível. CI1-F e fechamento L4 continuam com seus próprios critérios; nenhum deles substitui a decisão de produção.
