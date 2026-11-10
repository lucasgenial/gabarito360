# ADR-0016 — Toda entrega vai primeiro a uma homologação isolada em VM própria; a promoção para produção é decisão do responsável

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-03
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** docs/127-h1-homologacao-isolada.md (ADR-H1); AGENTS.md, seção "Continuidade e publicação" (decisão atualizada de 03/11/2026)
**Afeta:** [FN-003](../funcionalidades/003-publicacao-e-homologacao/planejamento.md); [operação](../operacao/)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

O responsável não conseguia conferir visualmente o desenvolvimento acumulado, porque o servidor de produção ficava na versão anterior. O projeto separou três ambientes: desenvolvimento local, homologação permanente em `homologa.gabarito360.com.br` e produção em `gabarito360.com.br`.

## Decisão

Toda entrega concluída e testada vai primeiro à homologação, com identificação visível de ambiente, fase e commit. O especialista revisa o design. O responsável valida o uso. A promoção para produção exige decisão humana explícita.

Citação do AGENTS.md: "toda entrega deve ser publicada primeiro na homologação isolada `homologa.gabarito360.com.br`, com banco, arquivos, filas e credenciais de serviços próprios."

Desenho da homologação (ADR-H1):

- VM QEMU própria no Proxmox, com MariaDB local exclusivo, Redis, arquivos, usuários técnicos, chaves e backups próprios.
- VM proposta: 2 vCPU, 4 GiB de RAM e 40 GiB de disco thin; Ubuntu 24.04, PHP 8.3 e MariaDB próprios. VMID 120 e IP 10.0.0.120/24 na bridge interna.
- Banco somente em loopback. A VM não conecta aos destinos operacionais atuais.
- E-mail e pagamentos em modo de teste, sem contas reais.
- HTTPS por túnel próprio, sem trocar a rota do domínio principal.
- Imagem oficial Ubuntu com checksum. Nenhum segredo no Git ou em evidência pública.

Alternativas consideradas: outro diretório no servidor atual (menor custo, mesmo risco operacional); novo contêiner LXC (mais leve, sem a separação por VM pedida); VM própria (mais manutenção, melhor isolamento e ensaio de recuperação). Venceu a VM própria.

Entregas internas H1: H1-A inventário e contrato de isolamento; H1-B VM, MariaDB, Redis, rede e proteção; H1-C release, dependências e migrations no banco novo; H1-D HTTPS, DNS e testes no Chrome; H1-E roteiro repetível de publicação, backup, restauração e aceite.

## Consequências

- Não clone o banco nem as credenciais de produção sem a autorização do [ADR-0017](0017-copia-pontual-de-producao-para-homologacao.md).
- Não declare domínio ou VM prontos só por criar o recurso. Verifique o acesso real.
- O aceite exige provar banco, filas e arquivos separados, ausência de e-mail ou pagamento real e incapacidade de conectar aos bancos operacionais.
- Não reutilize nem apague contêineres existentes, inclusive os parados.

## Verificação

- A pasta `infra/homologacao` e os recibos de publicação descrevem o ambiente.
- O rodapé da aplicação mostra o ambiente (ver [ADR-0038](0038-rodape-de-versao-com-ambiente.md)).
- H1-A a H1-E foram executados e o CI1-E foi aprovado e publicado na homologação (docs/129).
