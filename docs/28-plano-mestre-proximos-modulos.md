# 28 — Plano mestre dos próximos módulos

Situação em 31/10/2026: execução iniciada, com conciliação do quadro por Codex após consulta ao GitHub e ao commit publicado. Os diagnósticos da seção 2 são históricos; consultar a seção 6 para o andamento atual.

Este documento é a fonte de verdade para pessoas e agentes de IA. Quem for executar um pacote lê a seção 1, as decisões da seção 4, o próprio pacote e `docs/33-lgpd-privacidade-e-consentimento.md`.

## 1. Regras para quem executa

### Antes de começar

1. Leia `CLAUDE.md`, este plano e os documentos citados no pacote.
2. Confirme que as dependências do pacote estão concluídas na seção 6.
3. Confirme que nenhuma decisão pendente da seção 4 bloqueia o pacote.
4. Marque o pacote como `em andamento`, com o nome do agente, no mesmo PR ou em um commit próprio.
5. Crie um branch `pacote/<ID>-<assunto>` a partir de `main`.

### Durante

- Um pacote por PR. Se ele crescer, divida e atualize este plano.
- Não altere nada fora dos arquivos do pacote sem registrar o motivo no PR.
- A API não renderiza HTML. A web chama a API por `App\Services\ApiClient`.
- Toda rota da API usa o middleware `permissao:`, com chave em `App\Support\CatalogoPermissoes`. Depois de mudar o catálogo, rode `g360:permissoes-sincronizar`.
- Toda consulta respeita `App\Support\Escopo`. Um perfil nunca vê dados fora do seu escopo.
- Componente Blade nunca fica dentro de JavaScript.
- Pastas chamadas `vendor` são ignoradas pelo Git. Bibliotecas de navegador ficam em `apps/web/public/js/libs`.
- Textos em português do Brasil, diretos e sem emojis.
- Cores e fontes só pelos tokens de `apps/web/public/css/app.css`.
- Gráficos só por `<x-grafico>`.
- Todo dado de turma, aluno, prova, cartão e nota pertence a um espaço. Consulta sem filtro de espaço é defeito.
- Toda mudança com dados pessoais cumpre os requisitos RL1 a RL17 de `docs/33-lgpd-privacidade-e-consentimento.md`.

### Limites

Nenhum agente faz estas ações sem aprovação explícita do responsável, dada para aquela ação:

- Merge, deploy ou migração em produção.
- Mudança de `.env`, DNS, Cloudflare, Resend, nginx ou PHP-FPM.
- Criar conta ou inserir credenciais em serviços externos.
- Apagar dados ou arquivos em produção.
- Enviar e-mail real para pessoas fora da equipe.

Chaves e senhas nunca entram no Git, em logs, em PRs ou em conversas.

### Para concluir um pacote

Um pacote só termina quando:

- Os testes novos e os existentes passam: `php artisan test` em `apps/api` e `apps/web`.
- `python scripts/verificar_telas.py` não aponta erros.
- `bash scripts/auditoria/rodar.sh <branch>` passa, se o pacote mudar telas. Tela nova entra em `scripts/auditoria/aud_cfg.json`.
- A verificação do GitHub passa no PR. Aguarde o resultado antes de pedir aprovação.
- O PR diz o que foi testado e o que não foi.
- A seção 6 foi atualizada.

### Modelo de pacote

Cada pacote traz: objetivo, dependências, arquivos, passos e critério de pronto. Pacotes da mesma fase sem dependência entre si podem rodar em paralelo, desde que não mexam nos mesmos arquivos. Conflitos previsíveis estão indicados em **Arquivos disputados**.

## 2. O que foi encontrado

### E-mail no ECOAR

| Item | Situação |
|---|---|
| Envio | Resend por SMTP, em `smtp.resend.com:587`, com remetente `contato-ecoar@lmsouza.com.br` |
| Autenticação | Chave DKIM em `resend._domainkey`; subdomínio `send` com MX e SPF apontando para Amazon SES em `sa-east-1` |
| Recebimento | Cloudflare Email Routing: MX `route1`, `route2` e `route3.mx.cloudflare.net` e SPF `_spf.mx.cloudflare.net` |
| DMARC | `p=none`, com relatórios enviados à Cloudflare |
| Modelos | Motor `App\Mail\TemplateEmail`, com HTML puro e `{{placeholders}}` |
| Fila | Redis |

### DNS do Gabarito360

`gabarito360.com.br` usa a Cloudflare, mas está configurado para **bloquear todo e-mail**: `MX 0 .`, SPF `-all` e DMARC `p=reject`. Enquanto isso não mudar, nada será enviado ou recebido com esse domínio.

### Lacunas no Gabarito360

| Lacuna | Efeito |
|---|---|
| `MAIL_MAILER=log` na API e na web | Nenhum e-mail sai |
| `esqueceuSenha` grava o token e não envia nada | **Recuperação de senha não funciona** |
| `LeadController` só grava no banco | Mensagens do Fale Conosco não chegam a ninguém |
| `QUEUE_CONNECTION=sync` e nenhum worker | Tudo roda dentro da requisição, inclusive a leitura de cartão |
| Cache e sessão em arquivo ou banco, com Redis instalado e sem uso | Gargalo sob carga |
| Nenhuma notificação | Módulo inexistente |
| `alunos.cpf` vazio nos 414 alunos e `data_nascimento` em 360 | CPF não serve para validar o portal agora; 54 alunos ficariam sem acesso |
| Só existe `nome_responsavel` | Não há contato para confirmar o responsável |
| `nucleos` só tem nome e órgão regional | Faltam dados para a edição |
| Perfis sem Desenvolvedor, Suporte e Secretário Escolar | Exigem novos perfis no catálogo |
| `usuarios.perfil` guarda um só perfil | Uma pessoa não pode ser professora em uma escola e diretora em outra |
| Nenhuma auditoria geral | Só existem registros pontuais, como `usuario_permissoes_log` e `ambiguidade_logs` |

### Modelos do ECOAR a reaproveitar

| Recurso | Como funciona |
|---|---|
| Escolha de contexto | `usuario_escopo_ativo` guarda o escopo ativo de cada usuário; a tela chama isso de vendor. Neste plano, o nome é **vínculo** |
| Ver como outro usuário | `EmularSolicitante`: só leitura, até 30 minutos, limitado a um chamado e às permissões do operador e do solicitante |
| Auditoria | `audit_logs`, com ações e resultados em tabelas próprias e leitura limitada por `EscopoAuditoria` |

### CalendarJS

Pacote `@calendarjs/ce`, licença MIT, funciona sem React e depende de LemonadeJS.

- Schedule tem visão de dia e semana, arrastar e soltar e eventos para criar, alterar e excluir.
- Timeline mostra lista cronológica agrupada por mês.
- Calendar é um seletor de datas.
- Não traz pt-BR pronto; a tradução é feita com `setDictionary`.
- A repetição de eventos aparece na página inicial, mas não está na documentação do Schedule.
- Uma visão mensal de eventos não foi confirmada.

Por isso, o pacote C1 começa com uma prova de conceito.

## 3. Ordem de prioridade

| Fase | Tema | Por que nessa ordem |
|---|---|---|
| 0 | Base de operação | Sem backup, fila e Redis, os módulos seguintes ficam frágeis |
| 1 | E-mail e contato | Conserta a recuperação de senha, um defeito em uso |
| 2 | LGPD | Requisito essencial, que define regras dos módulos seguintes |
| 3 | Clientes, espaços e vínculos | Base de dados de todo o resto. Quanto antes, menos migração |
| 4 | Perfis, auditoria e notificações | Dependem de vínculos e espaços |
| 5 | Financeiro e selo dos documentos | Depende do cadastro de clientes |
| 6 | Cadastros | Reduz retrabalho diário das escolas |
| 7 | Calendário | Depende de provas e notificações |
| 8 | Portal dos responsáveis | Tem o maior risco e depende dos dados anteriores |
| 9 | Escala | Medição e otimização com o sistema completo |

Partes da escala que outros módulos exigem já entram na fase 0.

## 4. Decisões

### Pendentes

| ID | Decisão | Recomendação | Bloqueia |
|---|---|---|---|
| D9 | "Ver como" do Suporte permite alterar dados? | Só leitura, como no ECOAR | S1 |
| D10 | Primeiro acesso do responsável | Sistema gera o código, porque a secretaria precisa vê-lo | P3 |
| D13 | Dados da empresa para os termos | Razão social, CNPJ, endereço e encarregado | L1 |
| D14 | Revisão jurídica | Advogado ou encarregado revisa `docs/33` antes de publicar | L1, P2 |
| D15 | Acesso à Cloudflare | Autorizar o conector nesta sessão, ou liberar o uso do Chrome já conectado | E1 |

### Já definidas

- Resend e Cloudflare Email Routing, como no ECOAR.
- `contatos@` e `suporte@` encaminham ao mesmo destino usado em `lmsouza.com.br` e `eco-ar.app`.
- Endereços: `nao-responda@` para confirmação e recuperação de senha, `notificacoes@` para avisos, `contatos@` para o Fale Conosco e `suporte@` para atendimento. Os dois primeiros só enviam, e os dois últimos recebem.
- Portal dos responsáveis: sem CPF e sem código por e-mail nesta primeira versão. O envio por e-mail fica para depois.
- Código do responsável fica visível à secretaria da escola, no início do cadastro do aluno. Só a secretaria o recupera.
- Pessoa com vários papéis escolhe o vínculo ao entrar.
- Suporte pode ver o sistema como outro usuário.
- Só Desenvolvedor e Suporte acessam a auditoria. Todos são auditados. O Suporte não vê registros de Desenvolvedores. Nenhum perfil está acima do Desenvolvedor.
- Coordenador muda aluno de turma. O professor autônomo tem o mesmo poder sobre os próprios alunos.
- Dados de alunos só seguem com o professor se o contrato da escola permitir. Sem a cláusula, ele leva provas, materiais e notas anonimizadas.
- Professor autônomo tem cadastro mínimo de alunos: nome, turma e identificação simples, sem CPF, nascimento ou responsável. Ele não tem portal dos pais.
- Cadastro completo e portal dos pais são exclusivos de escolas e perfis superiores.
- O sistema é pedagógico: provas, gabaritos e respostas. Não guarda foto de aluno nem vídeo; o aluno é representado por avatar. A foto do cartão-resposta é mantida porque é o documento da correção.
- Professor autônomo é um perfil próprio. Perfis de escola e de núcleo também são distintos.
- Cliente pode ser professor, escola, núcleo, rede, secretaria ou governo.
- Cliente presenteado exibe a divulgação do Gabarito360 em impressões e PDFs. Cliente pagante exibe apenas "Feito no gabarito360.com.br".
- Novo perfil Financeiro, com arquitetura pronta para vários meios de pagamento. A integração real fica para depois.
- Dados copiados para uma escola permanecem nela se o professor sair. Se a escola sair, os dados dela são preservados, e cada professor pode continuar pagando.
- CalendarJS, sujeito à prova de conceito C1.
- Portal dos responsáveis com verificação anti-robô por Cloudflare Turnstile.
- Meta inicial de capacidade: 100 instituições, 600 professores e 1.000 responsáveis. Ver X1.
- Web e API seguem no mesmo servidor por enquanto, com grupos de processos separados.

## 5. Pacotes

### Fase 0. Base de operação

#### B1. Backup verificado

- **Objetivo:** copiar para o computador o backup diário do Proxmox e testar a restauração. Detalhes em `docs/operacao/backup.md`.
- **Dependências:** nenhuma.
- **Arquivos:** novo `docs/operacao/backup.md`; scripts em `scripts/operacao/`.
- **Passos:**
  1. Copiar os backups para o D:, com checksum. Feito.
  2. Restaurar CT 106 e CT 111 em ambiente isolado.
  3. Comparar contagens de registros.
  4. Documentar a restauração.
- **Pronto quando:** uma restauração real foi feita e registrada.

#### B2. Senha do banco

Cancelado em 31/10/2026 por decisão do responsável. A senha do banco não será trocada.

#### B3. Redis para cache, sessão e fila

- **Objetivo:** tirar cache e sessão do arquivo e do banco.
- **Dependências:** B1.
- **Arquivos:** `.env` de produção, `.env.example`, `docs/operacao/deploy.md`.
- **Passos:**
  1. Ajustar `CACHE_STORE=redis`, `SESSION_DRIVER=redis` e `QUEUE_CONNECTION=redis`, com prefixos separados para web e API.
  2. Limitar a memória do Redis e definir a política de expulsão.
  3. Documentar que a mudança encerra as sessões atuais.
- **Pronto quando:** login, correção e relatórios funcionam com Redis.

#### B4. Workers da fila e agendador

- **Objetivo:** processar trabalhos fora da requisição.
- **Dependências:** B3.
- **Arquivos:** `infra/systemd/gabarito360-worker@.service`, `infra/systemd/gabarito360-agendador.*`.
- **Passos:**
  1. Criar serviço systemd para `queue:work`, com filas `alta`, `padrao` e `baixa`.
  2. Criar o agendador de `schedule:run`.
  3. Reiniciar os workers no deploy.
  4. Alertar sobre `failed_jobs`.
- **Pronto quando:** um trabalho de teste passa pela fila e um trabalho que falha aparece em `failed_jobs`.

#### B5. Roteiro de deploy único

- **Objetivo:** substituir o deploy manual e o `deploy.sh` com caminho antigo.
- **Dependências:** B4.
- **Arquivos:** `scripts/operacao/deploy.sh`, `docs/operacao/deploy.md`.
- **Passos:**
  1. Rodar pull, composer, migração, caches e reinício dos workers.
  2. Falhar sem esconder erros.
  3. Verificar páginas ao final.
- **Pronto quando:** dois deploys seguidos funcionam sem passos manuais.

#### B6. Leitura de cartão pela fila

- **Objetivo:** tirar o processamento pesado dos processos web e API.
- **Dependências:** B4.
- **Arquivos:** `CartaoController`, `ProcessarCartaoJob`, `correcao/_camera.blade.php`.
- **Passos:**
  1. Responder ao envio assim que a foto for salva.
  2. Executar a leitura na fila `alta`.
  3. Adaptar a tela de câmera para acompanhar o resultado de cada cartão.
  4. Tratar recusa, erro e nova tentativa sem duplicar cartão.
- **Pronto quando:** 50 envios simultâneos em teste não ocupam a API e todos terminam.

### Fase 1. E-mail e contato

#### E1. Configuração do domínio

- **Executor:** agente com acesso à conta, depois de D15, ou o responsável, seguindo este roteiro.
- **Dependências:** D15.
- **Arquivos:** novo `docs/operacao/email.md`.
- **Passos:**
  1. Adicionar `gabarito360.com.br` no Resend, na região `sa-east-1`.
  2. Publicar na Cloudflare os registros DKIM e `send` indicados pelo Resend.
  3. Remover o `MX 0 .`.
  4. Ativar Email Routing e criar `contatos@` e `suporte@`, encaminhando ao mesmo destino de `lmsouza.com.br`. A Cloudflare só recebe; o envio é autorizado no Resend, e `nao-responda@` e `notificacoes@` não precisam de caixa.
  5. Trocar o SPF da raiz para `v=spf1 include:_spf.mx.cloudflare.net ~all`.
  6. Trocar o DMARC para `p=none`, com relatórios.
  7. Após duas semanas sem falhas, subir para `p=quarantine`.
- **Pronto quando:** o Resend mostra o domínio verificado e um teste chega com SPF, DKIM e DMARC aprovados.

#### E2. Motor de e-mail

- **Objetivo:** enviar pela fila, com modelos únicos.
- **Dependências:** B4, E1.
- **Arquivos:** `apps/api/app/Mail/`, `apps/api/resources/emails/`, novo `apps/api/app/Support/Emails.php`, testes.
- **Passos:**
  1. Trazer a ideia do `TemplateEmail` do ECOAR, com HTML e `{{placeholders}}`.
  2. Escapar todo valor inserido.
  3. Criar a base com logo, rodapé e versão em texto simples.
  4. Enviar pela fila `padrao`, com 3 tentativas.
  5. Registrar envio e falha em `emails_enviados`, sem o corpo da mensagem.
  6. Criar `g360:email-teste`.
- **Pronto quando:** testes cobrem substituição, escape e fila, e um envio real de teste chega.

#### E3. Recuperação de senha

- **Objetivo:** consertar o fluxo.
- **Dependências:** E2.
- **Arquivos:** `AuthController`, telas de esqueci e redefinir senha.
- **Passos:**
  1. Enviar o link com token de validade de 60 minutos.
  2. Invalidar o token após o uso.
  3. Limitar pedidos por e-mail e por IP.
  4. Manter a resposta igual para e-mail existente ou não.
  5. Enviar aviso de senha alterada.
- **Pronto quando:** o fluxo funciona de ponta a ponta e os testes cobrem token vencido e repetido.

#### E4. Modelos de e-mail

- **Dependências:** E2.
- **Modelos:**

| Modelo | Quando | Fase |
|---|---|---|
| `recuperar-senha` | Pedido de nova senha | 1 |
| `senha-alterada` | Senha trocada | 1 |
| `convite-usuario` | Conta criada por um gestor | 1 |
| `confirmar-email` | Conta nova ou e-mail alterado | 1 |
| `email-alterado-aviso` | Aviso ao endereço anterior | 1 |
| `fale-conosco-equipe` | Mensagem recebida | 1 |
| `fale-conosco-confirmacao` | Confirmação a quem escreveu | 1 |
| `vinculo-escola` | Pessoa vinculada a uma escola ou núcleo | 2 |
| `permissao-alterada` | Perfil ou permissão alterados | 2 |
| `conta-suspensa` | Conta desativada | 2 |
| `notificacao-geral` | Notificação enviada por e-mail | 2 |
| `resumo-diario` | Resumo opcional de notificações | 2 |
| `suporte-ver-como` | Aviso de que o Suporte acessou a conta | 2 |
| `prova-agendada` | Aplicação marcada | 4 |
| `prova-lembrete` | Véspera da aplicação | 4 |
| `correcao-concluida` | Correção terminada | 4 |
| `correcao-fechada` | Correção fechada ou reaberta | 4 |
| `importacao-concluida` | Resultado de importação | 3 |
| `portal-resultado` | Resultado enviado ao responsável, quando houver e-mail cadastrado | Futuro |
| `assinatura-pagamento` | Pagamento aprovado, falho ou vencendo, quando houver pagamento | Futuro |

- **Pronto quando:** cada modelo da fase tem pré-visualização em `/referencia-visual`, para administradores, e teste de renderização.

#### E5. Convite de usuário

- **Dependências:** E4.
- **Arquivos:** `UsuarioController`, `membros/*`.
- **Passos:**
  1. Trocar a senha definida pelo gestor por um convite com link.
  2. Dar 7 dias de validade ao link.
  3. Permitir reenviar o convite.
- **Pronto quando:** uma conta nova define a própria senha pelo link.

#### E6. Confirmação e troca de e-mail

- **Dependências:** E4.
- **Passos:**
  1. Exigir a senha atual para trocar o e-mail.
  2. Manter o endereço anterior até a confirmação.
  3. Avisar o endereço anterior.
- **Pronto quando:** o e-mail só muda depois da confirmação.

#### E7. Fale Conosco

- **Objetivo:** melhorar o formulário e fazer a mensagem chegar.
- **Dependências:** E4.
- **Arquivos:** `LeadController`, `leads/*`, `apps/web/public/css/app.css`.
- **Passos:**
  1. Refazer a tela com a identidade atual.
  2. Campos: nome, e-mail, celular, instituição, cargo, assunto e mensagem.
  3. Adicionar Cloudflare Turnstile e limite por IP.
  4. Enviar para a equipe e confirmar a quem escreveu.
  5. Permitir que `Responder` vá ao e-mail do visitante.
  6. Revisar a promessa de resposta em 2 dias úteis.
- **Pronto quando:** um envio real chega ao destino configurado.

#### E8. Demais páginas públicas

- **Dependências:** E7.
- **Arquivos:** `planos/*`, `leads/sucesso`, `auth/*`, `layouts/public`, `checkout/*`.
- **Passos:**
  1. Refazer planos, que ainda usa estilo próprio em `<style>`, seguindo a página inicial.
  2. Decidir o que fazer com checkout e assinatura enquanto não houver pagamento.
  3. Criar páginas de termos e privacidade.
  4. Ajustar títulos e descrições para busca.
- **Pronto quando:** as páginas públicas seguem a página inicial e passam pela auditoria.

### Fase 2. LGPD

Texto-base: `docs/33-lgpd-privacidade-e-consentimento.md`.

#### L1. Publicar privacidade e termos

- **Dependências:** D13, D14.
- **Arquivos:** `layouts/public`, novas telas em `legal/`.
- **Passos:**
  1. Publicar aviso de privacidade e canal do encarregado.
  2. Criar tabela `termos_versoes` e `termos_aceites`.
  3. Exigir aceite de usuários no próximo login.
  4. Tratar cookies conforme RL16.
- **Pronto quando:** RL7, RL15 e RL16 foram atendidos.

#### L2. Inventário, retenção e suboperadores

- **Dependências:** nenhuma.
- **Arquivos:** novo `docs/34-inventario-de-dados.md`.
- **Passos:**
  1. Mapear dados, finalidade, base, local e prazo.
  2. Listar suboperadores: Cloudflare, Resend, Amazon SES e outros.
  3. Analisar transferência internacional.
  4. Elaborar o relatório de impacto do portal e dos dados de menores.
- **Pronto quando:** RL2, RL12 e RL13 foram atendidos.

#### L3. Proteção dos dados

- **Dependências:** B1.
- **Passos:**
  1. Criptografar CPF e contatos, com busca por hash.
  2. Mascarar dados em logs.
  3. Remover dados pessoais de URLs e e-mails, quando possível.
  4. Fazer teste de restauração com chaves.
- **Pronto quando:** RL8 e RL9 foram atendidos.

#### L6. Trocar foto de aluno por avatar

- **Objetivo:** cumprir a minimização de dados. O sistema não precisa de foto de aluno.
- **Dependências:** B1.
- **Arquivos:** `AlunoController`, `Aluno`, telas de aluno, novo `components/avatar.blade.php`, migração de `alunos.foto_path`.
- **Passos:**
  1. Retirar o envio de foto das telas e da API.
  2. Criar avatar com as iniciais do nome, com cor definida pelo id do aluno.
  3. Usar o avatar em todas as telas que mostram aluno.
  4. Oferecer as iniciais como padrão e uma galeria de 36 personagens em pixel art, aprovada pelo responsável em 31/10/2026. Permitir escolher no cadastro/edição, salvar a escolha e voltar às iniciais. Nunca aceitar imagem enviada.
  5. Levantar as fotos existentes no banco e no armazenamento.
  6. Com aprovação do responsável, depois do backup, apagar os arquivos e remover a coluna.
- **Pronto quando:** nenhuma tela ou API aceita foto de aluno, e todas mostram o avatar.

#### L4. Pedidos dos titulares

- **Dependências:** T2.
- **Passos:**
  1. Criar protocolo e prazo por pedido.
  2. Exportar dados de um titular.
  3. Corrigir, anonimizar e eliminar, respeitando retenção legal.
  4. Executar a rotina de retenção.
- **Pronto quando:** RL3 a RL6 foram atendidos, com testes.

#### L5. Incidentes

- **Dependências:** AU1.
- **Arquivos:** novo `docs/operacao/incidentes.md`.
- **Passos:** criar registro, responsáveis, modelos de aviso e simulação. Considerar o prazo de três dias úteis da ANPD, após confirmar a norma.
- **Pronto quando:** uma simulação foi registrada e RL11 foi atendido.

### Fase 3. Clientes, espaços e vínculos

**Conceitos:**

- **Cliente:** quem contrata. Pode ser professor, escola, núcleo, rede, secretaria ou governo.
- **Espaço:** onde os dados vivem. Pode ser pessoal, escola, núcleo ou rede.
- **Vínculo:** papel de uma pessoa em um espaço.

**Modelo proposto, a detalhar em T1:**

| Tabela | Papel |
|---|---|
| `clientes` | Tipo, nome, documento, modalidade (`pagante`, `presenteado`, `teste`, `interno`) e situação |
| `espacos` | Tipo, cliente, escola, núcleo ou rede ligados e situação |
| `usuario_vinculos` | Usuário, espaço, perfil, início, fim e situação |
| `registros_origem` | Entidade, registro, espaço de origem, registro de origem, autor e lote |
| `copias_lotes` | Quem pediu, motivo, origem, destino, base legal, situação e resultado |

Turmas, alunos, provas, gabaritos, cartões e notas ganham `espaco_id` e autor. O espaço indica o dono; a cópia nunca compartilha a mesma linha entre espaços, evitando que uma exclusão afete o outro lado.

**Regras de negócio:**

| Situação | Resultado |
|---|---|
| Professor autônomo se vincula a uma escola | Dados dele são copiados para a escola. O espaço pessoal segue existindo |
| Professor trabalha na escola | Dados nascem no espaço da escola, com ele como autor |
| Professor sai e quer continuar | Mantém o espaço pessoal. Recebe provas e materiais. Notas identificadas só com cláusula no contrato; sem ela, anonimizadas |
| Professor sai | Os dados da escola não são apagados |
| Escola deixa o sistema | Dados da escola são preservados, inacessíveis até reativação ou fim do prazo |
| Professor de escola encerrada quer continuar | Contrata como autônomo, com a mesma regra da linha acima |

#### T1. Documento do modelo de dados

- **Dependências:** nenhuma.
- **Arquivos:** `docs/40-clientes-espacos-e-vinculos.md` e `docs/41-t1-mapa-de-consultas.md` (35 já utilizado pelas prévias jurídicas).
- **Passos:**
  1. Detalhar tabelas, chaves, índices e regras de escopo.
  2. Definir como rede, núcleo e escola se ligam a espaços.
  3. Listar toda consulta que precisa filtrar por espaço.
  4. Simular os 6 casos da tabela de regras.
  5. Planejar a migração em etapas, com volta segura.
  6. Definir a estratégia para crescer: índices iniciados por `espaco_id` e partição por tempo em tabelas grandes.
- **Pronto quando:** aprovado pelo responsável. Nenhum pacote T começa antes.

#### T2. Clientes e espaços

- **Dependências:** T1, B1.
- **Passos:**
  1. Criar `clientes` e `espacos`.
  2. Criar cliente e espaço para rede, núcleo e escola existentes.
  3. Adicionar `espaco_id` e autor, primeiro como opcionais.
  4. Preencher os dados atuais e conferir contagens.
  5. Tornar `espaco_id` obrigatório.
- **Pronto quando:** nenhum registro fica sem espaço e os testes atuais passam.

#### V1. Vínculos por pessoa

- **Andamento em 01/11/2026:** V1-A/B/C1/C2a/C2b/C3 incorporados nos PRs #87–#92. V1-C3 documentado em: [conciliação e preparo do corte](60-v1-conciliacao-e-preparo-do-corte.md). Backend com regime obrigatório ainda desligado; V2 deve concluir seleção por aba, interface de gestão e página de convite antes da ativação. Conciliação real e ensaio operacional permanecem pendentes. Sem deploy destas fases.

- **Objetivo:** permitir vários papéis para a mesma pessoa.
- **Dependências:** T2.
- **Arquivos disputados:** `Usuario`, `Permissao`, `Escopo`, `CatalogoPermissoes.php`, `Acesso`.
- **Passos:**
  1. Criar `usuario_vinculos`, usando espaço, perfil, início, fim e situação.
  2. Migrar `usuarios.perfil` e `usuario_escopos`, sem perder acesso.
  3. Escrever teste comparando as permissões de cada usuário antes e depois.
  4. Fazer `Permissao` e `Escopo` lerem o vínculo ativo.
  5. Manter `usuarios.perfil` até a migração terminar.
- **Pronto quando:** `PerfisTest` passa sem alteração e uma pessoa com dois vínculos recebe só os dados do vínculo ativo.

#### V2. Escolha do vínculo ao entrar

- **Dependências:** V1.
- **Arquivos:** `AuthController`, `LoginController`, `layouts/app.blade.php`, novo `auth/vinculo.blade.php`.
- **Passos:**
  1. Depois do login, mostrar a tela de escolha, se houver mais de um vínculo.
  2. Entrar direto, se houver um só.
  3. Guardar o vínculo ativo no token, para duas abas não se confundirem.
  4. Mostrar o vínculo atual no topo, com opção de trocar sem novo login.
  5. Rejeitar vínculo que não pertença ao usuário.
- **Pronto quando:** a troca muda menu, dados e permissões, e os testes cobrem tentativa de usar vínculo alheio.

#### T3. Perfis por tipo de espaço

- **Andamento em 01/11/2026:** T3-A incorporado no PR #96; T3-B no PR #97; T3-C no PR #99 (`3b8aa5b`, docs/65). Acesso pessoal preparado no código, sem publicação no servidor. Detalhes: [docs/63](63-t3-perfis-por-espaco.md). Sem deploy.

- **Dependências:** V1.
- **Arquivos disputados:** `CatalogoPermissoes.php`.
- **Passos:**
  1. Criar `professor_autonomo`, com poderes de gestão só no espaço pessoal, inclusive mudar aluno de turma.
  2. Limitar seu cadastro de alunos aos campos mínimos, tanto na tela quanto na API.
  3. Separar perfis de escola e de núcleo.
  4. Rever o menu por tipo de espaço.
  5. Impedir o portal dos pais em espaços pessoais.
- **Pronto quando:** os testes cobrem professor autônomo, de escola e de núcleo.

#### T4. Cadastro de clientes

- **Andamento em 01/11/2026:** T4-A incorporado no PR #100 (`a161e87`); nenhuma rota ou concessão de acesso. A1-A incorporado no PR #101 é a primeira parte do pré-requisito das telas T4-B. Sequência revista: [docs/67](67-a1-planejamento-e-acessos-internos.md). Sem deploy.
- **Dependências:** T2, A1.
- **Arquivos:** novas telas `clientes/*`.
- **Passos:**
  1. Criar telas para Desenvolvedor, Suporte e Financeiro.
  2. Cadastrar tipo, documento, contatos e modalidade.
  3. Mostrar espaços, usuários e contrato.
  4. Registrar mudança de modalidade na auditoria.
- **Pronto quando:** cada tipo de cliente pode ser criado, editado e consultado.

#### T5. Cópia de dados entre espaços

- **Dependências:** T3, B4, AU1.
- **Passos:**
  1. Criar serviço de cópia, repetível e pela fila.
  2. Registrar origem em `registros_origem`.
  3. Copiar ao vincular professor autônomo a uma escola.
  4. Evitar duplicar aluno, usando matrícula e origem.
  5. Ao desvincular, consultar a cláusula do contrato e copiar só o permitido.
  6. Reduzir dados de alunos ao cadastro mínimo no espaço pessoal.
  7. Mostrar resultado e permitir nova tentativa.
- **Pronto quando:** testes cobrem os 6 casos e nenhum lado perde dados.

#### T6. Encerramento de cliente

- **Dependências:** T5, L4.
- **Passos:** bloquear acesso, oferecer exportação, manter dados no prazo, oferecer continuidade aos professores e eliminar ao final.

### Fase 4. Perfis, auditoria e notificações


#### A1. Perfis Desenvolvedor, Suporte, Financeiro e Secretário Escolar

- **Replanejado em 01/11/2026:** antecipado como dependência de T4. A1-A prepara contexto interno e política comercial; A1-B cobre seleção/transporte e gestão; A1-C cobre Secretário Escolar e hierarquia institucional. [Plano e limites](67-a1-planejamento-e-acessos-internos.md). A1-A incorporado no PR #101 (`ee96bad`). A1-B revisto em [docs/68](68-a1-b-contextos-internos.md): B1 seleção/transporte incorporado no PR #102 (`29222ad`); B2 gestão/bootstrap incorporado no PR #103 (`98b28e1`), conforme [docs/69](69-a1-b2-gestao-interna.md). Habilitação desligada; sem deploy.
- **Dependências:** V1.
- **Arquivos disputados:** `CatalogoPermissoes.php`, `docs/03-perfis-e-permissoes.md`, `PerfisTest.php`.
- **Passos:**
  1. Criar `desenvolvedor`, acima de todos os perfis.
  2. Criar `suporte`, com acesso amplo e ações de atendimento.
  3. Criar `financeiro`, restrito a clientes, contratos e cobranças, sem dados de alunos.
  4. Criar `secretario_escolar`, para alunos e turmas. Código do responsável depende de P3/P4; importação XLSX permanece pendência do módulo de alunos. Ele é diferente de `secretario_educacao`. A1-C em revisão: [docs/70](70-a1-c-secretario-escolar.md).
  5. Preservar a matriz legada de oito colunas e usar políticas independentes para os novos perfis (replanejado em A1-A/A1-C).
  6. Copiar a regra do `GuardaHierarquico` do ECOAR: ninguém altera perfil acima do seu.
  7. Impedir que esses perfis apareçam em relatórios pedagógicos.
- **Pronto quando:** `PerfisTest` cobre os quatro perfis.

#### AU1. Registro de auditoria

- **Objetivo:** registrar quem fez o quê, onde e quando.
- **Dependências:** V1.
- **Arquivos:** migrações, `apps/api/app/Support/Auditoria.php`, novo middleware, `docs/32-auditoria.md`.
- **Passos:**
  1. Criar `auditoria_eventos`, com usuário real, usuário visto, vínculo, ação, entidade, escola, núcleo, IP, data e resultado.
  2. Registrar por padrão as requisições que alteram dados.
  3. Registrar leituras sensíveis: dados de aluno, exportações e código do responsável.
  4. Nunca gravar senhas, tokens ou códigos; mascarar dados pessoais.
  5. Gravar pela fila `baixa`, sem atrasar a resposta.
  6. Impedir edição e exclusão dos registros pelo sistema.
  7. Definir retenção e particionar por mês.
- **Pronto quando:** testes confirmam registro, mascaramento e que falha no registro não derruba a operação.

#### AU2. Tela de auditoria

- **Dependências:** AU1, A1.
- **Passos:**
  1. Criar a tela com filtros por pessoa, ação, período e escola.
  2. Liberar só a Desenvolvedor e Suporte.
  3. Ocultar do Suporte os registros de Desenvolvedores, inclusive em filtros, contagens e exportações.
  4. Registrar também as consultas à própria auditoria.
  5. Exportar CSV.
- **Pronto quando:** testes cobrem Desenvolvedor, Suporte sem ver Desenvolvedor, e negação a todos os demais perfis.

#### S1. Ver como outro usuário

- **Objetivo:** o Suporte vê o sistema como o usuário atendido.
- **Dependências:** V2, AU1, A1, D9.
- **Arquivos:** novo middleware, `layouts/app.blade.php`, tela de membros.
- **Passos:**
  1. Exigir motivo antes de iniciar.
  2. Usar token próprio de 30 minutos, com as permissões do usuário visto limitadas às do Suporte.
  3. Bloquear escrita, se D9 confirmar a recomendação.
  4. Impedir ver como Desenvolvedor, administrador ou outro Suporte.
  5. Mostrar faixa fixa com botão de sair.
  6. Registrar início, fim e ações com usuário real e usuário visto.
  7. Avisar o usuário por e-mail depois do acesso.
- **Pronto quando:** testes cobrem bloqueio de escrita, validade e alvos proibidos.

#### A2. Painel técnico

- **Dependências:** A1, B4, AU1.
- **Passos:**
  1. Mostrar fila, falhas, e-mails, versão e saúde dos serviços.
  2. Restringir ao Desenvolvedor.
- **Pronto quando:** a tela mostra dados reais e nega acesso aos outros perfis.

#### N1. Modelo de notificações

- **Dependências:** B4.
- **Arquivos:** migrações, `apps/api/app/Services/Notificacoes/`, `docs/29-notificacoes.md`.
- **Passos:**
  1. Criar `notificacoes`, `notificacao_destinatarios` e `notificacao_preferencias`.
  2. Definir tipos em um catálogo único, como o de permissões.
  3. Criar `Notificador::enviar(tipo, dados, destinatarios)`.
  4. Resolver destinatários por perfil e escopo.
  5. Agrupar eventos repetidos para evitar excesso.
- **Pronto quando:** testes cobrem destinatários por escopo.

#### N2. Central de notificações

- **Dependências:** N1.
- **Arquivos:** `layouts/app.blade.php`, novo `notificacoes/*`.
- **Passos:**
  1. Adicionar sino com contador no topo.
  2. Criar lista com lidas e não lidas.
  3. Consultar a cada 60 segundos, parando com a aba oculta.
- **Pronto quando:** uma notificação aparece e pode ser marcada como lida.

#### N3. Preferências e e-mail

- **Dependências:** N1, E4.
- **Passos:**
  1. Permitir escolher canais por tipo.
  2. Enviar e-mail pela fila `baixa`.
  3. Oferecer resumo diário.
- **Pronto quando:** a preferência é respeitada nos testes.

#### N4. Push no navegador

- **Dependências:** N2.
- **Observação:** opcional e pode ficar para depois do app.

### Fase 5. Financeiro e selo dos documentos

Módulo sem banco nesta etapa: tudo funciona com lançamento manual e já prevê integrações.

**Nunca guardar número de cartão ou CVV.** Só tokens do provedor.

#### F1. Modelo financeiro

- **Dependências:** T2.
- **Arquivos:** novo `docs/36-financeiro.md`, migrações.
- **Passos:**
  1. Criar `contratos`, `contrato_itens`, `faturas`, `cobrancas`, `pagamentos`, `notas_fiscais` e `eventos_pagamento`.
  2. Migrar `planos`, `assinaturas` e `pagamentos` atuais, trocando titular por cliente.
  3. Guardar valores em centavos.
  4. Tratar cliente público: processo, empenho, vigência e nota fiscal.
  5. Tratar desconto, cortesia e modalidade `presenteado`.
- **Pronto quando:** testes cobrem contrato, fatura e pagamento manual.

#### F2. Telas do Financeiro

- **Dependências:** F1, T4.
- **Passos:**
  1. Criar contratos, faturas, baixa manual e inadimplência no cadastro do cliente.
  2. Criar painel de recebimentos.
  3. Bloquear acesso por inadimplência só depois de regra definida.
- **Pronto quando:** o Financeiro gerencia um cliente sem acesso a dados de alunos.

#### F3. Interface de pagamentos

- **Dependências:** F1.
- **Arquivos:** `apps/api/app/Services/Pagamentos/`.
- **Passos:**
  1. Definir `GatewayPagamento`, com criar cobrança, consultar, cancelar, estornar e validar webhook.
  2. Criar `GatewayManual`.
  3. Adaptar `MercadoPagoService` à interface.
  4. Criar rota única de webhook por provedor, com assinatura verificada.
  5. Processar eventos pela fila, sem repetir efeito.
  6. Documentar como adicionar provedor. Candidatos: Mercado Pago, Asaas, Efí, Pagar.me e Pix ou boleto direto com bancos.
- **Pronto quando:** um provedor falso de teste passa por todo o fluxo.

#### R1. Selo dos documentos

- **Objetivo:** divulgação conforme a modalidade do cliente.
- **Dependências:** T4.
- **Arquivos:** novo `components/selo-documento.blade.php`, CSS de impressão, `omr/gerar_resultado.py`, `omr/gerar_cartoes.py`.
- **Passos:**
  1. Criar a regra central `Selo::do(espaco)`.
  2. Cliente `presenteado`: bloco institucional que apresenta o Gabarito360, com QR.
  3. Cliente pagante: apenas "Feito no gabarito360.com.br".
  4. Aplicar a impressões e PDFs de todos os relatórios.
  5. Não mostrar o bloco na tela. Usar o mesmo texto para todos, sem dados do aluno.
- **Pronto quando:** testes cobrem as duas modalidades, e a impressão foi conferida em A4.

### Fase 6. Cadastros

#### A3. Dados dos Núcleos Territoriais

- **Dependências:** V1.
- **Arquivos disputados:** `CatalogoPermissoes.php`.
- **Passos:**
  1. Migrar `nucleos` com código, endereço, cidade, contatos, responsável e logo.
  2. Criar `PUT /v1/nucleos/{id}`, com permissão `nucleo.editar`.
  3. Criar a tela de edição, para Diretor de Núcleo e administrador.
  4. Usar a logo do núcleo nos relatórios.
- **Pronto quando:** o diretor edita só o próprio núcleo.

#### A4. Separar servidores do núcleo e das escolas

- **Dependências:** A3.
- **Arquivos:** `UsuarioController`, `membros/*`, menu.
- **Passos:**
  1. Usar `usuario_vinculos`, sem duplicar pessoas.
  2. Criar a tela `Equipe do núcleo`.
  3. Limitar `Equipe da escola` às pessoas com vínculo de escola.
  4. Mostrar uma pessoa com os dois vínculos nas duas telas.
  5. Migrar e conferir os dados atuais.
  6. Remover o uso de `usuarios.escola_nome`.
- **Pronto quando:** cada lista mostra só o que pertence a ela e os testes cobrem o escopo.

#### A5. Alunos: importar, exportar e mudar de turma

- **Dependências:** B4, E4 e T3.
- **Passos:**
  1. Reaproveitar `g360:alunos-importar` em um serviço único.
  2. Criar `alunos.importar` e `alunos.exportar`.
  3. Publicar modelo CSV com colunas documentadas.
  4. Mostrar prévia com erros por linha antes de gravar.
  5. Atualizar pela matrícula, sem duplicar aluno.
  6. Importar pela fila e avisar ao concluir.
  7. Exportar CSV respeitando o escopo.
  8. Criar `aluno_turma_historico`.
  9. Mudar de turma individualmente e em lote, com permissão do coordenador e do professor autônomo.
  10. Preservar notas na turma em que a prova ocorreu.
- **Pronto quando:** testes cobrem importação com erros, repetição e mudança de turma com histórico.

#### A6. Dados dos responsáveis

- **Dependências:** A1.
- **Passos:**
  1. Criar `responsaveis` e `aluno_responsaveis`.
  2. Guardar contatos criptografados, como campos opcionais nesta versão.
  3. Incluir no formulário e no CSV.
  4. Relatar alunos sem data de nascimento; hoje são 54.
- **Pronto quando:** a escola consegue completar os dados pendentes.

#### A7. Duplicar, exportar e importar provas

- **Dependências:** nenhuma.
- **Arquivos:** `ProvaController`, `provas/*`, `ImportarProvas.php`.
- **Passos:**
  1. Duplicar prova com gabarito, como rascunho.
  2. Aplicar a outras turmas da mesma escola.
  3. Exportar em JSON versionado, sem dados de alunos.
  4. Importar com validação e prévia.
  5. Importar de outra turma pela própria tela.
  6. Garantir que notas e cartões não sejam copiados.
- **Pronto quando:** uma prova duplicada ou importada é corrigida normalmente.

### Fase 7. Calendário

#### C1. Prova de conceito

- **Dependências:** nenhuma.
- **Passos:**
  1. Instalar `@calendarjs/ce` e LemonadeJS em `apps/web/public/js/libs`, com versões fixas.
  2. Testar Schedule e Timeline nos temas claro e escuro, em 375 px.
  3. Traduzir para pt-BR.
  4. Confirmar visão mensal e repetição.
  5. Registrar a decisão em `docs/30-calendario.md`.
- **Pronto quando:** o responsável aprovou a prova de conceito. Se faltar visão mensal, levar alternativas antes de seguir.

#### C2. Modelo de eventos

- **Dependências:** C1, A1.
- **Passos:**
  1. Criar `eventos`, com tipo, escola, turma, prova, início, fim e responsável.
  2. Criar permissões `calendario.ver` e `calendario.editar`.
  3. Criar endpoints por período e escopo.
- **Pronto quando:** cada perfil recebe só os eventos do seu escopo.

#### C3. Tela de calendário

- **Dependências:** C2.
- **Passos:**
  1. Criar visões de agenda e linha do tempo.
  2. Liberar para professores, coordenadores e diretores.
  3. Permitir criar, mover e excluir, conforme permissão.
- **Pronto quando:** a tela passa na auditoria e funciona no celular.

#### C4. Provas no calendário e avisos

- **Dependências:** C2, N1, N3.
- **Passos:**
  1. Criar evento ao agendar uma aplicação.
  2. Atualizar ao mudar a data e cancelar ao excluir.
  3. Notificar coordenação, direção e núcleo.
  4. Enviar lembrete na véspera pelo agendador.
- **Pronto quando:** testes cobrem criação, mudança, cancelamento e destinatários.

### Fase 8. Portal dos responsáveis

Módulo de maior risco, com dados de menores. Disponível só para escolas e perfis superiores. Nenhum pacote começa antes de D10, D14 e L2.

**Modelo decidido:** o responsável entra com a matrícula do aluno e o código de acesso. No primeiro acesso, informa matrícula e data de nascimento e recebe o código. Se esquecer, só a secretaria da escola o consulta ou gera outro.

**Riscos aceitos e como reduzi-los:**

- Matrícula e nascimento são conhecidos por colegas e parentes. Quem chegar primeiro recebe o código. Por isso, o sistema registra o primeiro acesso, a secretaria vê a data e pode gerar outro código, e a escola pode entregar o código em papel antes do primeiro acesso.
- O código precisa ser legível pela secretaria. Ele não é uma senha pessoal, fica criptografado no banco, e toda consulta é auditada.
- Nenhum sistema impede por completo que alguém copie ou fotografe dados. O objetivo é dificultar, identificar a origem e limitar o que é exposto.

#### P1. Modelo de segurança

- **Dependências:** D10, A6.
- **Arquivos:** novo `docs/31-seguranca-portal.md`.
- **Passos:**
  1. Documentar ameaças: adivinhação, colega que conhece os dados, robôs e vazamento por quem acessou.
  2. Definir dados mínimos exibidos.
  3. Definir retenção de registros.
  4. Passar por revisão de segurança.
- **Pronto quando:** o documento foi aprovado pelo responsável.

#### P2. Consentimento

- **Dependências:** L1.
- **Passos:** exibir o termo da seção 4 de `docs/33`, com registro de versão e aceite, e separar a autorização opcional de comunicações.

#### P3. Código de acesso e primeiro acesso

- **Dependências:** P1, P2, A6, AU1, X3.
- **Arquivos:** rotas públicas próprias, sem `auth`, `apps/api/app/Http/Controllers/Api/Portal/`.
- **Passos:**
  1. Criar `portal_acessos_aluno`, com código criptografado, situação, primeiro acesso e geração.
  2. Gerar código alfanumérico de 10 caracteres, sem letras ou números parecidos.
  3. Criar a tela de primeiro acesso, com matrícula, nascimento e Turnstile.
  4. Mostrar o código uma vez, com orientação para guardá-lo.
  5. Permitir no primeiro acesso só alunos ainda sem código ativado.
  6. Criar a entrada com matrícula, código e Turnstile.
  7. Dar a mesma resposta para qualquer dado incorreto.
  8. Limitar tentativas por IP e por matrícula, com bloqueio progressivo.
  9. Criar sessão de 15 minutos, sem token na URL.
  10. Registrar todo acesso na auditoria.
- **Pronto quando:** testes cobrem repetição do primeiro acesso, bloqueio, enumeração e sessão vencida.

#### P4. Código no cadastro do aluno

- **Dependências:** P3, A1.
- **Arquivos:** `alunos/show.blade.php`.
- **Passos:**
  1. Mostrar o código em destaque no início do cadastro, só para a secretaria da escola.
  2. Mostrar a data do primeiro e do último acesso.
  3. Permitir gerar outro código, invalidando o anterior.
  4. Criar impressão de códigos por turma, para entrega em papel.
  5. Auditar consulta, geração e impressão.
- **Pronto quando:** outros perfis não recebem o código na API.

#### P5. Tela de resultados

- **Dependências:** P3.
- **Passos:**
  1. Criar layout próprio, limpo e sem menu do sistema.
  2. Mostrar notas por prova, sem dados de outros alunos.
  3. Bloquear indexação e cache com `noindex` e `no-store`.
  4. Mostrar logos da escola e do núcleo.
  5. Não incluir propaganda nesta tela.
- **Pronto quando:** a auditoria de telas passa e o teste de segurança confirma que outro aluno não é acessível.

#### P6. Impressão e PDF

- **Dependências:** P5, R1.
- **Passos:**
  1. Gerar PDF com logos da escola, do núcleo e do Gabarito360.
  2. Incluir marca-d'água com data, hora e código do acesso, nunca o código de entrada.
  3. Usar o selo de R1, que aparece só na impressão e no PDF.
  4. Deixar o envio por e-mail para depois.
- **Pronto quando:** o selo segue a modalidade do cliente e não aparece na tela.

#### P7. Revisão final de segurança

- **Dependências:** P6.
- **Passos:** teste de intrusão do fluxo, revisão de logs, plano de resposta a incidentes e publicação gradual em uma escola.

#### P8. Código por e-mail

- **Dependências:** P7, E4, N3.
- **Observação:** segunda versão, quando houver contatos confirmados.

### Fase 9. Escala

| Prática | Pacote |
|---|---|
| Medir antes de otimizar | X1 |
| Remover consultas repetidas e criar índices | X2 |
| Limites e proteção na borda | X3 |
| Arquivos fora do servidor | X4 |
| Separar serviços | X5 |
| Observabilidade | X6 |

#### X1. Metas e teste de carga

- **Dependências:** B6.
- **Meta inicial:** 100 instituições, 600 professores e 1.000 responsáveis.
- **Hipótese de pico, a validar com uso real:** 320 pessoas ao mesmo tempo, 20% do total, geram cerca de 50 requisições por segundo. A meta é responder 95% em menos de 500 ms, com menos de 0,5% de erros, e ler 100 cartões por minuto.
- **Passos:**
  1. Confirmar as metas com os dados de uso.
  2. Criar roteiros `k6` para login, painel, correção e relatórios.
  3. Rodar em ambiente separado, nunca em produção sem aprovação.
- **Pronto quando:** existe um relatório com o limite atual.

#### X2. Banco e consultas

- **Dependências:** X1.
- **Passos:**
  1. Corrigir repetições, como a contagem por cartão em `CartaoController::index`.
  2. Adicionar índices apontados pelo log de consultas lentas.
  3. Paginar listas grandes.
  4. Fazer cache de relatórios, com invalidação quando houver nota nova.
- **Pronto quando:** as consultas críticas atingem a meta.

#### X3. Limites por rota e borda

- **Dependências:** X1.
- **Passos:** aplicar limite por usuário e IP, cache de arquivos, Turnstile em rotas públicas e WAF da Cloudflare.

#### X4. Fotos em armazenamento externo

- **Dependências:** X1.
- **Passos:** avaliar Cloudflare R2 e migrar fotos por disco do Laravel, mantendo links assinados.

#### X5. Separação de serviços

- **Dependências:** X1.
- **Observação:** só se a medição pedir. Ordem: workers em contêiner próprio, depois réplica de leitura, depois mais de uma instância da web.
- **Requisito:** aplicação sem estado local, com sessão e cache no Redis e arquivos externos.

#### X6. Observabilidade

- **Dependências:** B4.
- **Passos:** logs estruturados, rastreio de erros, métricas, alertas e página de saúde.

## 6. Quadro de andamento

Situação: `pendente`, `em andamento`, `em revisão`, `concluído` ou `cancelado`.

Atualizado em 31/10/2026 com autorização do responsável para manter a completude registrada. Esta atualização preserva escopo, prioridades e critérios dos pacotes. PR incorporado não implica conclusão de critérios ainda pendentes. E7 inclui confirmação de uso real pelo responsável; L3 tem publicação e validação operacional registradas. L6 publicado e verificado em 31/10/2026; remoção de fotos/coluna não autorizada.

| Pacote | Situação | Responsável | PR |
|---|---|---|---|
| B1 Backup | concluído: cópia e restauração registradas; PR #62 incorporado | Claude / Codex | #51, #62 |
| B2 Senha do banco | cancelado | | |
| B3 Redis | concluído | Claude | |
| B4 Workers | concluído | Claude | |
| B5 Deploy | concluído: roteiro aprovado e dois deploys consecutivos verificados em 31/10/2026 (docs/37) | Codex | #66, #67 |
| B6 Cartão pela fila | concluído: PR #61 incorporado, migração e deploy verificados; validação com cartão real em produção ainda não executada | Codex | #61, #65 |
| E1 Domínio | concluído | Claude | |
| E2 Motor de e-mail | concluído | Claude | |
| E3 Recuperar senha | concluído | Claude | |
| E4 Modelos | fase 1 concluída; fase 2 pendente | Claude | |
| E5 Convite | código incorporado e publicado; falta teste real de convite | Claude / Codex | #54, #55 |
| E6 Trocar e-mail | concluído no código; falta teste real | Claude | |
| E7 Fale Conosco | concluído: chaves reais configuradas, publicado e envio confirmado pelo responsável | Claude / Codex | #53, #58 |
| E8 Páginas públicas | entrega visual publicada; termos e privacidade adiados pelo responsável | Codex | #59, #60 |
| L1 Publicar termos | em andamento: prévias com campos a preencher autorizadas; publicação e aceite pendentes (docs/35) | Codex | #64 |
| L2 Inventário | parcial: inventário incorporado; políticas e conferências em docs/39; contratos, prazos por instituição e validação residual pendentes | Codex | #68, #71 |
| L3 Proteção | concluído: chave dedicada, migração e logs protegidos publicados; verificações operacionais em 31/10/2026 (docs/operacao/protecao-dados.md) | Codex | #69 |
| L4 Titulares | pendente: depende de T2 | | |
| L5 Incidentes | pendente: depende de AU1 | | |
| L6 Trocar foto por avatar | concluído funcionalmente: galeria de 36 avatares e iniciais publicadas e verificadas em produção (docs/operacao/avatares-alunos.md); foto_path e fotos preservados, remoção não autorizada | Codex | #70 |
| T1 Modelo de dados | concluído: modelo aprovado pelo responsável; PR #73 incorporado; docs/40 e docs/41 | Codex | #73 |
| T2 Clientes e espaços | concluído e publicado na homologação representativa em 01/11/2026: release af4c6d1 (PR #85); backup final em duas camadas, preenchimento e restrições aplicados; 2 clientes internos/13 espaços, administrador com dois vínculos separados; auditoria, dados e validação funcional aprovados (docs/54). V1/V2 permanecem próximos pacotes distintos | Codex | #74, #75, #76, #77, #78, #79, #80, #81, #82, #83, #84, #85 |
| V1 Vínculos | código incorporado; ativação depende de V2 e ensaio; sem deploy | Codex | #87–#92; docs/60 |
| V2 Escolha do vínculo | V2-A/B/C incorporados nos PRs #93/#94/#95; ensaio da release em ambiente equivalente pendente; sem deploy | Codex | docs/61–62 |
| T3 Perfis por espaço | T3-A #96, T3-B #97 e T3-C #99 incorporados; sem deploy | Codex | docs/63–65 |
| T4 Cadastro de clientes | T4-A #100 incorporado; A1 necessário antes das telas T4-B; sem deploy | Codex | docs/66–67 |
| T5 Cópia de dados | pendente | | |
| T6 Encerramento | pendente | | |
| A1 Novos perfis | A1-A #101 e B1 #102 incorporados; B2 #103 incorporado; C Secretário Escolar em revisão; código responsável depende P3/P4; sem deploy | Codex | docs/67–70 |
| AU1 Registro de auditoria | pendente | | |
| AU2 Tela de auditoria | pendente | | |
| S1 Ver como | pendente | | |
| A2 Painel técnico | pendente | | |
| N1 Modelo de notificações | pendente | | |
| N2 Central | pendente | | |
| N3 Preferências | pendente | | |
| N4 Push | pendente | | |
| F1 Modelo financeiro | pendente | | |
| F2 Telas financeiras | pendente | | |
| F3 Pagamentos | pendente | | |
| R1 Selo | pendente | | |
| A3 Núcleos | pendente | | |
| A4 Separar equipes | pendente | | |
| A5 Alunos | pendente | | |
| A6 Dados dos responsáveis | pendente | | |
| A7 Provas | pendente | | |
| C1 Prova de conceito | pendente | | |
| C2 Eventos | pendente | | |
| C3 Tela de calendário | pendente | | |
| C4 Avisos de provas | pendente | | |
| P1 Segurança | pendente | | |
| P2 Consentimento | pendente | | |
| P3 Código e primeiro acesso | pendente | | |
| P4 Código no cadastro | pendente | | |
| P5 Resultados | pendente | | |
| P6 PDF | pendente | | |
| P7 Revisão | pendente | | |
| P8 Código por e-mail | pendente | | |
| X1 Carga | pendente | | |
| X2 Banco | pendente | | |
| X3 Limites | pendente | | |
| X4 Fotos | pendente | | |
| X5 Serviços | pendente | | |
| X6 Observabilidade | pendente | | |

## 7. Trabalho em paralelo

| Bloco | Pode rodar junto | Cuidado |
|---|---|---|
| Fase 0 | B1 com L2, A7 e C1 | B3 e B4 mudam a operação; um por vez |
| Fase 1 | E3, E5, E6 e E7, depois de E4 | Mudam fluxos próprios |
| Fase 2 | L2 e L3 | L1 depende de dados da empresa |
| Fase 3 | Sequencial até V2 | T2 e V1 mudam o modelo central e não rodam com outros pacotes de dados |
| Fase 4 | AU1 e N1 | A1 mexe em `CatalogoPermissoes.php` |
| Fase 5 | F3 e R1 | F1 vem primeiro |
| Fase 6 | A5 e A7 | A3, A4 e A6 tocam em cadastros de pessoas |
| Fase 7 | C3 e C4, depois de C2 | Coordenar o `layouts/app.blade.php` |
| Fase 8 | Sequencial | Revisão de segurança em cada etapa |

Arquivos disputados: `CatalogoPermissoes.php`, `Permissao.php`, `Escopo.php`, `app.css`, `layouts/app.blade.php`, `routes/api.php` e `routes/web.php`. Faça mudanças pequenas e atualize o branch antes do PR.
