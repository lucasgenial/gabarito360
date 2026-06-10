# Gabarito360

Sistema de gestão, aplicação, leitura e correção de cartões-resposta por foto.

## Objetivo
Criar uma plataforma com backend web, painel administrativo, app Android e módulo OMR para leitura automática de gabaritos.

## Stack desejada
- Backend: Laravel 12
- Banco: PostgreSQL
- Mobile: Flutter
- OMR: OpenCV
- Tempo real: Laravel Reverb/WebSockets
- Filas/cache: Redis
- Infraestrutura: Docker, Nginx

## Regras do projeto
- Trabalhar por etapas pequenas.
- Antes de codar, gerar documentação técnica.
- Não apagar arquivos sem autorização.
- Criar commits organizados.
- Priorizar MVP funcional.
- Toda funcionalidade deve ter validação, autenticação e controle de permissões.
- Dados de alunos devem seguir boas práticas de segurança e LGPD.

## Design System Obrigatório

Todo componente visual criado no projeto deve utilizar os tokens definidos em:

`docs/ui_token_gov_brasil.json`

e seguir as diretrizes definidas em:

`docs/SDGB.md`

Não criar estilos hardcoded sem justificativa documentada.

## Controle de Versão

- Nunca executar comandos Git automaticamente.
- Sempre sugerir commits ao final de cada tarefa.
- Utilizar mensagens de commit em português.
- Preferir commits pequenos e temáticos.
- Informar quais arquivos pertencem a cada commit.
- Sugerir o comando git push apenas após o commit.

## Regras de Interface

Antes de criar qualquer tela Web ou Mobile:

1. Consultar `docs/design/design-system.md`.
2. Consultar `docs/ui_token_gov_brasil.json`.
3. Consultar `docs/design/componentes-web.md` ou `docs/design/componentes-mobile.md`.
4. Reutilizar componentes existentes.
5. Não criar estilos ad-hoc.

Toda nova tela deve respeitar:

- Mobile First
- WCAG 2.2 AA
- Dark Mode
- Tokens oficiais
