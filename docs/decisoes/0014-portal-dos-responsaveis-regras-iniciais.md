# ADR-0014 — O portal dos responsáveis não usa CPF nem código por e-mail na primeira versão e usa Cloudflare Turnstile

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-10-31
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** docs/28-plano-mestre-proximos-modulos.md, seção 4 "Já definidas" (portal) e seção 4 "Pendentes" (D10)
**Afeta:** [FN-067](../funcionalidades/067-portal-responsaveis/planejamento.md); [FN-035](../funcionalidades/035-responsaveis-alunos/planejamento.md); [FN-061](../funcionalidades/061-lgpd-termos-privacidade/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

O banco tinha `alunos.cpf` vazio nos 414 alunos da época e só o campo `nome_responsavel`. CPF e contato não serviam para validar o responsável. O portal envolve dados de menores e tem o maior risco do plano.

## Decisão

- Primeira versão sem CPF e sem código por e-mail. O envio por e-mail fica para depois.
- O código do responsável fica visível à secretaria da escola, no início do cadastro do aluno. Só a secretaria o recupera.
- O portal usa verificação anti-robô por Cloudflare Turnstile.
- Nenhum pacote do portal começa antes de D10, D14 e L2.

## Consequências

- A decisão D10 (o sistema gera o código, recomendação do plano) continua pendente, junto com D14 (revisão jurídica).
- O portal é exclusivo de escolas e perfis superiores.

## Verificação

- Existe configuração de Turnstile em `apps/web/config/services.php` e `apps/api/config/services.php`.
- O portal ainda não está implementado ([FN-067](../funcionalidades/067-portal-responsaveis/planejamento.md) pendente).
