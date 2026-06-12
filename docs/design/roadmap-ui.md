> **Roadmap histórico suspenso.** A sequência MP-019A/MP-029 foi substituída
> pelas etapas R1 a R7 de `docs/13-plano-refatoracao-mockup-mariadb.md`. O
> mockup em `style-system/` agora orienta a reconstrução da aplicação web.

> **R4 concluída em 12 de junho de 2026.** O shell responsivo, os tokens
> congelados, o tema explícito e o catálogo Blade compartilhado estão prontos
> para as fatias funcionais da R5.

# Roadmap de UI

## 1. Estratégia

O Design System é introduzido de forma incremental e versionável. A fundação web da MP-019A foi concluída em etapa reparadora após a MP-028; as próximas interfaces devem obrigatoriamente reutilizá-la.

## 2. Ordem recomendada

```text
Documentação oficial do Design System
        |
        +--> MP-019A: fundação visual web
        |        |
        |        +--> MP-019 e telas web funcionais
        |
        +--> MP-040: arquitetura Flutter
                 |
                 +--> MP-040A: tema e tokens Flutter
                          |
                          +--> MP-040B: componentes Flutter
                                   |
                                   +--> MP-041 a MP-045

MP-046: snapshot do dashboard
        |
        +--> MP-046A: componentes de dashboard
                 |
                 +--> MP-048 e MP-049
```

## 3. Relação com os micropassos

| Micropasso | Entrega visual | Dependências | Componentes relacionados |
|---|---|---|---|
| MP-019A | Estrutura visual do painel e tokens web | MP-018, ADR-D009, documentação visual | layout, sidebar, header, Button, campos, Card, Modal, Badge, Table, estados |
| MP-019 | Painel organizacional mínimo | MP-019A | formulários, tabelas, navegação e feedback |
| MP-028 | Tela administrativa mínima de provas e vínculos | MP-019A e MP-027 | Tabs, Accordion, Select, Table e feedback |
| MP-040 | Projeto e arquitetura Flutter | contratos das FASES 2 e 6 | prepara integração, sem telas funcionais |
| MP-040A | Design System Flutter | MP-040 | ThemeData, tipografia, cores, espaçamento, dark mode |
| MP-040B | Biblioteca de componentes Flutter | MP-040A | botões, campos, cards, badges, navegação e estados |
| MP-041 a MP-045 | Fluxo mobile funcional | MP-040B e APIs correspondentes | login, aplicações, alunos, captura, conferência e resultado |
| MP-046 | Snapshot consistente | gate da FASE 8 | contrato de dados, sem interface |
| MP-046A | Biblioteca operacional de dashboard | R4 e MP-046 | especializações de KPI/gráfico, progresso, alertas, listas e estados |
| MP-048 | Dashboard operacional em tempo real | MP-046A e MP-047 | composição e atualização dos componentes |
| MP-049 | Resiliência do dashboard | MP-048 | reconexão, desatualização e contingência |
| MP-051 | Tela de relatório por turma | MP-019A e MP-050 | filtros, tabela, resumo e exportação |

## 4. Momento de cada componente

| Componente ou grupo | Primeiro micropasso de implementação | Reutilização prevista |
|---|---|---|
| Tokens web, tipografia, cores, espaçamento, bordas, sombras e dark mode | MP-019A | todas as telas web |
| Layout, sidebar/drawer, header, Breadcrumb e Avatar | MP-019A | MP-019, MP-028, MP-048 e MP-051 |
| Button, Input, Textarea, Select, Modal, Card, Badge, Alert/Toast e Tooltip | MP-019A | todas as telas web conforme necessidade |
| Table, DataTable e Pagination | MP-019 e MP-051 | cadastros, provas e relatórios |
| Tabs e Accordion | MP-028 | provas e gabaritos |
| DatePicker | primeiro fluxo de aplicação que exigir data, a partir do MP-029 | aplicações e filtros posteriores |
| Loading, Error e Empty State web | MP-019A | todas as telas web |
| Tema, tokens, tipografia e dark mode Flutter | MP-040A | MP-040B a MP-045 |
| Botões, campos, cards, badges, dialogs, bottom sheets, navegação e estados Flutter | MP-040B | MP-041 a MP-045 |
| Card de aplicação, item de aluno e indicador de sincronização | MP-042 | MP-043 a MP-045 |
| Guia de captura e feedback de qualidade da imagem | MP-043 | fluxo de captura |
| Grade de respostas, alertas de confiança e confirmação | MP-044 | MP-045 |
| KPI Card e gráfico estruturais com alternativa tabular | R4 | R5, MP-046A, MP-048 e MP-049 |
| Progresso, filtros, alertas, listas e especializações de dashboard | MP-046A | MP-048 e MP-049 |
| Estados de conexão, desatualização e reconexão | visual no MP-046A; comportamento no MP-049 | dashboard operacional |

Componentes não listados devem ser implementados junto da primeira necessidade funcional, usando a fundação compartilhada e sem duplicação local.

## 5. Desenvolvimento dos componentes

### Fundação web no MP-019A

A fundação foi concluída e aplicada retroativamente às telas das MP-019 e MP-028. Ela inclui o catálogo Blade inicial, temas claro e escuro, estados de interação, modal acessível e teste de contrato visual. Componentes adicionais devem surgir junto da primeira necessidade funcional, sem duplicação local.

### Fundação mobile nos MP-040A e MP-040B

O MP-040A converte tokens para tema Flutter. O MP-040B cria widgets compartilhados e estados fundamentais. As telas dos MP-041 a MP-045 devem compor esses widgets.

### Dashboard na R4 e no MP-046A

A R4 cria somente KPI e gráfico estruturais reutilizáveis. O MP-046A deve
especializá-los a partir do contrato consistente do MP-046, antes de conectá-los
aos eventos em tempo real no MP-048.

## 6. Dependências transversais

- `docs/ui_token_gov_brasil.json` e `docs/SDGB.md`.
- [`design-system.md`](design-system.md).
- [`acessibilidade.md`](acessibilidade.md).
- [`dark-mode.md`](dark-mode.md).
- ADR-D009 para tecnologia do painel web.
- Contratos de dados aprovados antes de criar visualizações.

## 7. Restrições

- Não criar frontend durante a integração documental do Design System.
- Não antecipar telas de fases futuras.
- Não criar estilos hardcoded sem justificativa documentada.
- Não usar componentes visuais como substitutos de autorização.
- Não bloquear o MP-011 com dependências de UI.
