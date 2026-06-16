# CLAUDE.md — Gabarito360

Este arquivo é a referência permanente para todas as execuções neste projeto.
Leia-o integralmente antes de qualquer ação.

---

## Idioma

Todas as respostas e comunicações devem ser em **Português Brasileiro (Pt-BR)**.

---

## O Que É Este Projeto

O Gabarito360 é uma plataforma completa de gestão de avaliações educacionais para redes públicas de ensino.

**Não é** apenas um leitor de gabaritos.

Missão: atender todo o ciclo de avaliação — do cadastro de questões à análise pedagógica dos resultados.

---

## Regra Fundamental

**Nenhum código deve ser criado sem documentação aprovada.**

Toda funcionalidade deve ter origem rastreável em:
1. Mockup HTML identificado no inventário
2. Ou aprovação explícita posterior documentada

---

## Prioridade das Fontes de Verdade

```
1. Mockups HTML          → mockups/
2. Style System          → style-system/
3. Documentação oficial  → docs/
4. Regras do legado      → Gabarito360/ (somente regras de negócio)
5. Código legado         → Gabarito360/ (consulta histórica apenas)
```

Em conflito: Mockup > Style System > Docs > Legado

---

## Arquitetura Oficial

### Banco de Dados
- **MariaDB** (único banco aprovado)

### Aplicações

| App       | Tecnologia    | Diretório    | Responsabilidade                          |
|-----------|---------------|--------------|-------------------------------------------|
| API       | Laravel       | apps/api     | Auth, regras de negócio, OMR, relatórios  |
| WEB       | Laravel       | apps/web     | Interface administrativa, dashboards      |
| Android   | React Native  | apps/android | App mobile consumindo API                 |
| iOS       | React Native  | apps/ios     | App mobile consumindo API                 |

**A API nunca renderiza telas. Apenas JSON.**

---

## Perfis do Sistema

| Perfil              | Escopo                          |
|---------------------|---------------------------------|
| Administrador Rede  | Visão total da rede             |
| Diretor de Núcleo   | Visão das escolas do núcleo     |
| Diretor Escolar     | Visão da escola                 |
| Coordenador         | Gestão pedagógica da escola     |
| Professor           | Turmas e provas próprias        |
| Aluno               | Próprias notas e provas         |

---

## Design System

- **Framework:** gov.br Design System (Padrão Digital de Governo)
- **Fonte principal:** Rawline / Raleway (fallback)
- **Fonte mono:** Roboto Mono
- **Cor accent:** `#1351b4` (Blue Warm Vivid 50)
- **Cor success:** `#168821` (Green Cool Vivid 50)
- **Arquivo tokens:** `style-system/css/gov.css`

---

## Convenções de Implementação

### O Que Nunca Fazer
- Criar código sem documentação aprovada
- Reutilizar arquitetura, banco, frontend ou backend do legado sem justificativa
- Misturar responsabilidades entre API e WEB
- Renderizar HTML na API
- Criar funcionalidades fora dos mockups sem aprovação

### O Que Sempre Fazer
- Consultar `docs/01-inventario-dos-mockups.md` antes de implementar qualquer tela
- Verificar `docs/03-perfis-e-permissoes.md` antes de criar endpoints
- Seguir o Design System gov.br em todos os componentes visuais
- Documentar decisões de arquitetura em `docs/12-arquitetura.md`

---

## Convenção de Commits

```
tipo(escopo): descrição curta em pt-BR

Tipos: feat, fix, docs, refactor, test, chore
Escopo: api, web, android, ios, docs, db, infra

Exemplos:
  feat(api): implementa endpoint de autenticação
  feat(web): cria dashboard do coordenador
  docs: atualiza inventário dos mockups
  fix(api): corrige cálculo de média da turma
```

---

## Fluxo de Trabalho

1. **Ler** o MP (Micro-Passo) correspondente em `docs/14-plano-de-execucao.md`
2. **Verificar** dependências do MP no `docs/13-roadmap.md`
3. **Consultar** inventário de mockups para funcionalidades envolvidas
4. **Implementar** seguindo a arquitetura oficial
5. **Testar** contra os critérios de aceite do MP
6. **Commitar** seguindo a convenção de commits

---

## Documentação de Referência Rápida

| Documento                         | Quando Consultar                              |
|-----------------------------------|-----------------------------------------------|
| docs/01-inventario-dos-mockups.md | Antes de implementar qualquer tela            |
| docs/03-perfis-e-permissoes.md    | Antes de criar endpoints ou telas             |
| docs/06-regras-de-negocio.md      | Antes de implementar lógica de negócio        |
| docs/09-modelo-de-dados.md        | Antes de criar migrations                     |
| docs/10-rotas-api.md              | Para implementação da API                     |
| docs/11-rotas-web.md              | Para implementação do WEB                     |
| docs/13-roadmap.md                | Para entender sequência de implementação      |
| docs/14-plano-de-execucao.md      | Para executar um MP específico                |

---

## Uso do Legado

O diretório `Gabarito360/` contém o legado e é permitido apenas para:
- Recuperar regras de negócio validadas
- Recuperar fluxos funcionais aprovados
- Recuperar decisões históricas documentadas

**Proibido reutilizar:** arquitetura, banco, modelos, rotas, código frontend, código backend.
