# E-mail

## Domínio

| Item | Configuração |
|---|---|
| Envio | Resend, região `sa-east-1`, por `smtp.resend.com:587`, usuário `resend` |
| Recebimento | Cloudflare Email Routing |
| Registros | `resend._domainkey`, `send` (MX e SPF) e `rsend`, além dos MX e do DKIM da Cloudflare |
| DMARC | `p=none`. Subir para `p=quarantine` após duas semanas sem falhas |

## Endereços

| Endereço | Uso |
|---|---|
| `nao-responda@` | Remetente de confirmações e senhas. Não recebe |
| `notificacoes@` | Remetente de avisos. Não recebe |
| `contatos@` | Fale Conosco. Encaminha para `genio.lucas+gabarito360@gmail.com` |
| `suporte@` | Atendimento. Encaminha para o mesmo destino |

Os demais endereços são descartados.

## Configuração

Em `apps/api/.env`, a senha SMTP é uma chave do Resend só para envio:

```text
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=587
MAIL_USERNAME=resend
MAIL_PASSWORD=<chave>
MAIL_FROM_ADDRESS="nao-responda@gabarito360.com.br"
MAIL_CONTATO="contatos@gabarito360.com.br"
APP_URL_WEB=https://gabarito360.com.br
```

## Modelos

Ficam em `apps/api/resources/emails/modelos`, dentro de `base.html`. Campos usam `{{nome}}`, e os valores são escapados por `App\Mail\EmailModelo`. Os envios vão pela fila `padrao`, com 3 tentativas.

Modelos disponíveis: `recuperar-senha`, `senha-alterada`, `fale-conosco-equipe` e `fale-conosco-confirmacao`.

## Testar

```bash
php artisan g360:email-teste destino@exemplo.com
```

`emails_enviados` guarda modelo, hash do destinatário e data, sem o corpo da mensagem. Falhas ficam em `failed_jobs`.
