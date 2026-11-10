# E-mail transacional e Fale Conosco

**Atualizado em:** 10/11/2026, por lucasgenial
**Ambiente:** produção (envio real pelo Resend). Na homologação, o transporte é `log` e nenhum e-mail sai.
**Quando usar:** configurar o domínio de e-mail, trocar uma chave, testar o envio, incluir um modelo ou investigar um e-mail que não chegou.
**Duração estimada:** teste de envio, 5 minutos. Configuração de domínio, 30 a 60 minutos mais a propagação do DNS.

Este documento descreve a configuração do domínio, os endereços, o motor de e-mail, os modelos e o Fale Conosco. A funcionalidade é a [FN-060](../funcionalidades/060-email-transacional/planejamento.md). A proteção dos dados em e-mails está em [11-seguranca-e-privacidade.md](../arquitetura/11-seguranca-e-privacidade.md).

> **Atenção:** nunca registre a chave do Resend em Git, logs, PRs ou conversas. Não envie e-mail real a pessoas de fora da equipe em testes. Alterar DNS, Cloudflare ou Resend exige aprovação expressa do responsável.

## Domínio

| Item | Configuração |
|---|---|
| Envio | Resend, região `sa-east-1`, por `smtp.resend.com:587`, usuário `resend` |
| Recebimento | Cloudflare Email Routing |
| Registros no DNS | `resend._domainkey` (DKIM do Resend), `send` (MX e SPF do Resend) e `rsend`, além dos registros MX e do DKIM da Cloudflare |
| SPF da raiz | `v=spf1 include:_spf.mx.cloudflare.net ~all` |
| DMARC | `p=none`, com relatórios. Suba para `p=quarantine` depois de duas semanas sem falhas |

Antes da configuração (E1), o domínio estava bloqueado para e-mail: `MX 0 .`, SPF `-all` e DMARC `p=reject`. Esses registros foram substituídos.

### Passos: configurar o domínio (E1)

Siga estes passos num domínio novo ou ao refazer a configuração.

1. Adicione o domínio no Resend, na região `sa-east-1`.
2. Publique na Cloudflare os registros DKIM e `send` indicados pelo Resend.
3. Remova o `MX 0 .`.
4. Ative o Email Routing. Crie `contatos@` e `suporte@`, encaminhando ao mesmo destino da equipe. A Cloudflare só recebe. O envio é autorizado no Resend. Os endereços `nao-responda@` e `notificacoes@` não precisam de caixa.
5. Troque o SPF da raiz para `v=spf1 include:_spf.mx.cloudflare.net ~all`.
6. Troque o DMARC para `p=none`, com relatórios.
7. Depois de duas semanas sem falhas, suba o DMARC para `p=quarantine`.
8. Confirme que o Resend mostra o domínio verificado e que um teste chega com SPF, DKIM e DMARC aprovados.

## Endereços

| Endereço | Uso |
|---|---|
| `nao-responda@gabarito360.com.br` | Remetente de confirmações e senhas. Não recebe |
| `notificacoes@gabarito360.com.br` | Remetente de avisos. Não recebe |
| `contatos@gabarito360.com.br` | Fale Conosco. Encaminha à caixa da equipe |
| `suporte@gabarito360.com.br` | Atendimento. Encaminha à mesma caixa da equipe |

O destino de encaminhamento é uma caixa pessoal do responsável. O endereço não é registrado aqui. Os demais endereços do domínio são descartados.

## Configuração

Na API (`apps/api/.env`), a senha SMTP é uma chave do Resend usada só para envio:

| Variável | Valor |
|---|---|
| `MAIL_MAILER` | `smtp` |
| `MAIL_HOST` | `smtp.resend.com` |
| `MAIL_PORT` | `587` |
| `MAIL_USERNAME` | `resend` |
| `MAIL_PASSWORD` | A chave do Resend. Não a registre |
| `MAIL_FROM_ADDRESS` | `nao-responda@gabarito360.com.br` |
| `MAIL_CONTATO` | `contatos@gabarito360.com.br` |
| `APP_URL_WEB` | `https://gabarito360.com.br` |

O arquivo `apps/api/config/emails.php` lê esses valores: `remetente`, `contato` e `url_web` (base dos links enviados por e-mail).

## Motor de e-mail

1. Os modelos ficam em `apps/api/resources/emails/modelos`, dentro de `base.html`.
2. Os campos usam `{{nome}}`. A classe `App\Mail\EmailModelo` escapa os valores.
3. O HTML não usa Blade. O motor gera também a versão em texto simples.
4. Cada mensagem vai pela fila `padrao`, com 3 tentativas e intervalos de 60 e 300 segundos.
5. O payload do job é cifrado (`ShouldBeEncrypted`), inclusive em `failed_jobs`. O destinatário e o conteúdo continuam necessários na entrega SMTP.
6. A tabela `emails_enviados` guarda o modelo, o hash do destinatário e a data, sem o corpo da mensagem. As falhas ficam em `failed_jobs`.
7. Novos modelos não carregam CPF.

### Modelos disponíveis

| Modelo | Quando |
|---|---|
| `recuperar-senha` | Pedido de nova senha |
| `senha-alterada` | Senha trocada |
| `convite-usuario` | Conta criada por um gestor. Informa função e vínculo |
| `convite-vinculo` | Convite a uma pessoa já cadastrada para um novo vínculo |
| `convite-recusado` | Aviso de que o convite foi recusado |
| `confirmar-email` | Conta nova ou e-mail alterado |
| `email-alterado-aviso` | Aviso ao endereço anterior |
| `boas-vindas-cadastro` | Quem criou a própria conta ou comprou o sistema. Entra em uso quando o autocadastro voltar |
| `fale-conosco-equipe` | Mensagem recebida, enviada à equipe |
| `fale-conosco-confirmacao` | Confirmação a quem escreveu |

### Modelos previstos (ainda sem arquivo)

`vinculo-escola`, `permissao-alterada`, `conta-suspensa`, `notificacao-geral`, `resumo-diario` e `suporte-ver-como` (fase 2); `prova-agendada`, `prova-lembrete`, `correcao-concluida` e `correcao-fechada` (fase 4); `importacao-concluida` (fase 3); `portal-resultado` e `assinatura-pagamento` (futuro). Cada modelo novo precisa de pré-visualização em `/referencia-visual` (para administradores) e de teste de renderização.

### Fluxos que usam e-mail

| Fluxo | Regras |
|---|---|
| Recuperar senha (E3) | O link tem token com validade de 60 minutos. O token é invalidado depois do uso. Limite de 5 pedidos por minuto na rota. A resposta é igual para e-mail existente ou não. O sistema envia aviso de senha alterada |
| Convite de usuário (E5) | O gestor não define mais a senha. A pessoa recebe um link com validade de 7 dias e define a própria senha. É possível reenviar o convite |
| Troca de e-mail (E6) | Exige a senha atual. O endereço anterior permanece até a confirmação, e recebe um aviso. A validade da confirmação é de 24 horas |
| Fale Conosco (E7) | Campos: nome, e-mail, celular, instituição, cargo, assunto e mensagem. Usa Cloudflare Turnstile e limite de 5 envios por minuto por IP. Envia à equipe e confirma a quem escreveu. O botão `Responder` da equipe vai ao e-mail do visitante |

## Passos: testar o envio

1. Execute na API:

   ```bash
   php artisan g360:email-teste destino@exemplo.com
   ```

2. Confirme no destino que a mensagem chegou e que o cabeçalho de autenticação mostra SPF, DKIM e DMARC aprovados.
3. Consulte `emails_enviados` (modelo, hash e data). Consulte `failed_jobs` se a mensagem não chegou.

## Verificação

1. O Resend mostra o domínio verificado.
2. Um teste chega com SPF, DKIM e DMARC aprovados.
3. A fila `padrao` esvazia e `failed_jobs` não cresce.
4. Um envio real de teste do Fale Conosco chega ao destino configurado. O responsável confirmou esse envio em produção.
5. Pendência: o teste real de convite de usuário (E5) e de troca de e-mail (E6) ainda não foi feito em produção.

## Reversão

1. Para parar o envio sem perder mensagens, mude `MAIL_MAILER` para `log` e reconstrua o cache de configuração. Os e-mails passam a ser gravados em log.
2. Para restaurar o bloqueio do domínio, é preciso decisão expressa do responsável (volta o `MX 0 .`, SPF `-all` e DMARC `p=reject`). Nenhum fluxo de e-mail funciona nesse estado.
3. Para trocar a chave do Resend, gere uma nova chave só de envio, atualize `MAIL_PASSWORD` por procedimento seguro, reconstrua o cache e revogue a anterior.

## Referências

- [11-seguranca-e-privacidade.md](../arquitetura/11-seguranca-e-privacidade.md)
- [13-infraestrutura-e-ambientes.md](../arquitetura/13-infraestrutura-e-ambientes.md)
- [FN-060 E-mail transacional e Fale Conosco](../funcionalidades/060-email-transacional/planejamento.md)

## Divergências encontradas

| Documento antigo | Divergência | Vale |
|---|---|---|
| `docs/operacao/email.md` | Lista 9 modelos | O código tem 10. O modelo `convite-vinculo` (`ConviteVinculoController`) foi acrescentado |
| `docs/28` (E2) | Cita `apps/api/app/Support/Emails.php` | O arquivo não existe. O motor é `App\Mail\EmailModelo` |
| `docs/28` (E3) | Dizia que `esqueceuSenha` não enviava nada, que o `MAIL_MAILER` era `log` e que o `QUEUE_CONNECTION` era `sync` | Esses defeitos foram corrigidos (E1 a E3 concluídos) |
| `docs/operacao/email.md` | Registrava o endereço da caixa pessoal de destino | Omitido por regra de privacidade |
