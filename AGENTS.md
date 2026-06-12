# Gabarito360

Sistema de gestão, aplicação, leitura e correção de cartões-resposta por foto.

## Objetivo
Criar uma plataforma com backend web, painel administrativo, app Android e módulo OMR para leitura automática de gabaritos.

## Stack desejada
- Backend: Laravel 12
- Banco: MariaDB
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

O mockup funcional em `style-system/` e a matriz registrada em
`docs/14-matriz-funcional-mockup.md` definem as telas, hierarquia visual,
navegação, comportamento responsivo e estados esperados da aplicação web.

Todo componente visual criado no projeto deve também utilizar os tokens definidos em:

`docs/ui_token_gov_brasil.json`

e seguir as diretrizes definidas em:

`docs/SDGB.md`

Não criar estilos hardcoded sem justificativa documentada.

Em caso de divergência:

1. o mockup define composição, fluxo e comportamento;
2. os tokens oficiais definem os valores visuais implementáveis;
3. `docs/SDGB.md` define as diretrizes normativas.

O tema claro é o padrão obrigatório. O tema escuro deve ser opcional, acionado
por controle explícito e persistido por usuário ou dispositivo.

## Controle de Versão

- Nunca executar comandos Git automaticamente.
- Sempre sugerir commits ao final de cada tarefa.
- Utilizar mensagens de commit em português.
- Preferir commits pequenos e temáticos.
- Informar quais arquivos pertencem a cada commit.
- Sugerir o comando git push apenas após o commit.

## Regras de Interface

Antes de criar qualquer tela Web ou Mobile:

1. Consultar a tela equivalente em `style-system/`.
2. Consultar `docs/14-matriz-funcional-mockup.md`.
3. Consultar `docs/design/design-system.md`.
4. Consultar `docs/ui_token_gov_brasil.json`.
5. Consultar `docs/design/componentes-web.md` ou `docs/design/componentes-mobile.md`.
6. Reutilizar componentes existentes.
7. Não criar estilos ad-hoc.

Toda nova tela deve respeitar:

- Mobile First
- WCAG 2.2 AA
- Dark Mode
- Tokens oficiais
