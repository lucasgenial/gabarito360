# UR2 — correções de dependências de runtime

## Planejamento revisto antes da implementação

Base isolada: 0f721e4, branch fix/ur2-dependencias-seguranca. A auditoria dos locks API/Web aponta 35 alertas em 9 pacotes de runtime. Isto não comprova exploração da aplicação; justifica corrigir as versões antes de TU1.

1. Preservar composer.json, requisitos PHP e majors atuais. Atualização parcial dos nove pacotes afetados com `composer update ... -W --minimal-changes --no-install --no-scripts`, permitindo apenas transitivas necessárias. Sem ignorar audit/platform.
2. Conferir avisos oficiais e patches atuais, inclusive advisory Laravel de 04/11/2026 (Concurrency Process: corrigido em13.40). Não limitar a atualização ao TokenGuard13.29 se houver correções posteriores.
3. Comparar locks e auditorias antes/depois, incluindo auditoria completa de dev. Instalar vendors novos em QA WSL exclusivo com os locks finais. Rodar suítes completas API/Web e caches de rota/views.
4. Adicionar gate `composer audit --locked --no-dev` na matrix CI. Revisão independente do conjunto antes de commit/push/publicação; nenhuma operação de servidor nesta tarefa.

## Evidências e versões

| Pacote | Antes | Depois | Motivo |
|---|---|---|---|
| guzzlehttp/guzzle | 7.11.2 | 7.16.1 | Correção de segurança |
| guzzlehttp/promises | 2.5.0 | 2.6.0 | Transitiva necessária |
| guzzlehttp/psr7 | 2.11.1 | 2.13.2 | Correção de segurança |
| laravel/framework | v13.15.0 | v13.40.0 | Correção de segurança |
| laravel/prompts | v0.3.18 | v0.3.26 | Transitiva necessária |
| league/commonmark | 2.8.2 | 2.10.7 | Correção de segurança |
| league/flysystem | 3.34.0 | 3.36.0 | Correção de segurança |
| symfony/http-foundation | v7.4.13 | v7.4.23 | Correção de segurança |
| symfony/http-kernel | v7.4.13 | v7.4.23 | Correção de segurança |
| symfony/mime | v7.4.13 | v7.4.22 | Correção de segurança |
| symfony/routing | v7.4.13 | v7.4.23 | Correção de segurança |

Mesmas 11 atualizações em API e Web, sem inclusões/exclusões. `laravel/prompts ^0.3.25` é exigido pelo Laravel13.40; `guzzlehttp/promises ^2.6` é exigido pelo Guzzle7.16.1. Pacotes de desenvolvimento permanecem iguais. Nenhuma dependência de major diferente foi selecionada; composer.json e content-hash originais preservados.

Comando de resolução parcial em cada QA exclusivo:

```sh
composer update laravel/framework:^13.40 guzzlehttp/guzzle:^7.15.2 guzzlehttp/psr7:^2.12.3 league/commonmark:^2.10.2 league/flysystem:^3.35.3 symfony/http-foundation:^7.4.23 symfony/http-kernel:^7.4.23 symfony/mime:^7.4.22 symfony/routing:^7.4.23 -W --minimal-changes --no-install --no-scripts --no-interaction
```

A primeira tentativa de resolução recusou a restrição temporária mime^7.4.23 (versão inexistente); a resolução final usa7.4.22, patch oficial desse componente. Nenhuma restrição foi alterada no projeto. O Composer2.7.1 local suporta --minimal-changes e audit --locked; nenhum advisory foi ignorado. A instalação subsequente usou `composer install --no-interaction --no-progress` com vendors novos, executando apenas seus scripts normais de descoberta; não houve `composer update` ou instalação em servidor.

## Verificação

| Verificação | API | Web |
|---|---|---|
| Auditoria runtime antes (snapshot UR1) | 35 alertas /9 pacotes | 35 alertas /9 pacotes |
| audit --locked --no-dev depois | 0 advisories,0 abandoned | 0 advisories,0 abandoned |
| audit --locked incluindo dev depois | 0 advisories,0 abandoned | 0 advisories,0 abandoned |
| composer install com lock/vendor novo | Aprovado | Aprovado |
| Suíte completa PHP8.3 /SQLite | 838 testes/7080 asserções aprovados;28 skips previstos | 239 testes/2349 asserções aprovados |
| composer validate --strict e check-platform-reqs | Aprovados | Aprovados |
| route:cache e view:cache | Aprovados | Aprovados |

QA exclusivamente local WSL: `/tmp/g360-ur2-api` e `/tmp/g360-ur2-web`, sem reutilizar vendors antigos. Relatórios locais `/tmp/ur2-{api,web}-audit-{runtime,all}.json` e logs `/tmp/ur2-{api,web}-full.log`. Os 28 cenários dependentes de MariaDB/ensaio explícito ficam para os jobs próprios da CI; não simular sua aprovação por SQLite. Produção e homologação não foram alteradas. Atualização CI limitada a um gate runtime na matrix API/Web.


## Fontes primárias

- [Laravel TokenGuard advisory](https://github.com/laravel/framework/security/advisories/GHSA-f9x3-7vq9-cph6).
- [Laravel Concurrency advisory](https://github.com/laravel/framework/security/advisories/GHSA-63hm-g96r-2m2q).
- [Symfony HTTP Foundation advisory](https://symfony.com/cve-2026-118514).
- [Guzzle7.16.1, release do mantenedor](https://github.com/guzzle/guzzle/releases/tag/7.16.1).
- [PSR-7 2.13.2, release do mantenedor](https://github.com/guzzle/psr7/releases/tag/2.13.2).
- [CommonMark2.10.7, release do mantenedor](https://github.com/thephpleague/commonmark/releases/tag/2.10.7).
- [Flysystem3.36.0, release do mantenedor](https://github.com/thephpleague/flysystem/releases/tag/3.36.0).
- [Symfony avisos oficiais de segurança](https://symfony.com/blog/category/security-advisories).

Revisão independente de segurança concluída sem bloqueadores (parecer externo `operacao-20261102/ur2-revisao-final.md`). CI final e revisão de integração ainda devem preceder qualquer publicação; a próxima release exige vendor novo produzido por install dos locks, sem copiar o vendor de UR1.

Zero alertas conhecidos na auditoria não equivale a ausência de riscos ou certificação de segurança.
