# 082 — Família de ícones G360 — Planejamento

**Implementação:** Parcial
**Etapa:** Em execução
**Conforme princípios:** versão 19
**Posição no plano mestre:** fora das ondas (entrega única aprovada pelo responsável em 13/11/2026)
**Natureza:** nova
**Origem:** conversa "icone" do projeto, 13/11/2026; inventário de ícones da homologação; decisão no [ADR-0063](../../decisoes/0063-familia-de-icones-propria-em-fonte.md)
**Atualizado em:** 2026-11-13, por lucasgenial

## 1. Objetivo

O app desenha o mesmo conceito de ícone de várias formas. Há duas famílias oficiais em conflito, SVGs avulsos e emojis no lugar de ícone. Esta funcionalidade põe no repositório uma família própria, a G360 Icones, entregue como fonte. Ela é a base única para a migração das telas.

## 2. Escopo

- **Inclui:**
  - As fontes compiladas nas duas variantes, contorno e preenchido, com os mesmos códigos: WOFF2 e TTF em `apps/web/public/fonts/icones/`.
  - O CSS da família: `apps/web/public/css/g360-icones.css`.
  - A fonte única em `style-system/icones/`: desenhos de traço (`src/`), lotes, motivos, scripts de compilação, `registro.json`, `catalogo.json`, diretriz e guia para adicionar ícone.
  - A documentação: ADR-0063, regra R-ICONES planejada, seção Iconografia do `style-system/` e doc 09.
- **Não inclui:**
  - A migração das telas para a fonte. Fica para outra funcionalidade, a registrar.
  - A troca de `x-icone` e `x-icone-estrutura` pelo glifo da fonte. Fica na mesma funcionalidade de migração.
  - O script da regra R-ICONES. Ele só reprova com sentido depois da migração. Fica na funcionalidade de migração.
  - Os PDFs de catálogo e de prancha. Eles ficam fora do Git.

## 3. Fontes de verdade

- [ADR-0063](../../decisoes/0063-familia-de-icones-propria-em-fonte.md): a decisão.
- `style-system/icones/DIRETRIZ.md`: a especificação do desenho e o mapa de metáforas.
- `style-system/icones/COMO-ADICIONAR.md`: o fluxo de um ícone novo.
- `style-system/README.md`, seção Iconografia, e [doc 09](../../arquitetura/09-interface-e-design-system.md).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).

## 4. Dependências

| Depende de | Por quê | Situação |
|---|---|---|
| Aprovação da família completa | O ADR-0063 só admite a entrada no repositório com a família aprovada | Aprovada em 13/11/2026 (141 ícones, U+E001 a U+E08D) |

**Funcionalidades que dependem desta:** a funcionalidade de migração das telas, ainda a registrar.

## 5. Fases e subfases

| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 | Família desenhada e aprovada | 141 ícones nas duas variantes, conferidos com o checklist da diretriz | Concluída | Aprovação do responsável em 13/11/2026, conversa "icone" |
| 1.2 | Fontes, CSS, fonte única e documentação no repositório | Arquivos nos caminhos do §2; regras ativas aprovadas; nenhuma tela alterada | Em execução | PR da branch `ui/g360-icones` |
| 1.3 | Publicação na homologação | `/css/g360-icones.css` e os dois WOFF2 servidos; conferência registrada por texto | Pendente | |

## 6. Contrato técnico (proporcional ao risco)

- Sem API, sem banco e sem migration. Só arquivos estáticos do `apps/web`.
- Os códigos ficam na área de uso privado, a partir de U+E001, em `style-system/icones/registro.json`. Um código existente nunca muda. Um ícone novo entra no fim.
- O CSS carrega as fontes com `font-display: block` para não mostrar caractere errado durante a carga.
- O CSS não está ligado a nenhuma tela nesta entrega. Nenhuma página carrega a fonte ainda.

## 7. Interface

Nenhuma tela muda nesta entrega. A especificação visual está na diretriz: grade 24, traço 1,8, pontas redondas, folga de 1,2 px. A cor vem só de token. A conferência visual é registrada por texto, sem prints (ADR-0049).

## 8. Riscos e decisões pendentes do responsável

| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| Versão dos princípios em conflito com a PR #276 (ADR-0062), que também sobe a versão | Mesclar uma e regenerar a outra | Regenerar VERSAO, AGENTS.md e adequações com os scripts na PR que entrar depois | Pendente |
| Registro da funcionalidade de migração das telas | Registrar agora ou depois do merge | Registrar depois do merge desta entrega | Pendente |

## 9. Ramos desta entrega

Nenhum.

## 10. Histórico

| Data | Mudança | Por |
|---|---|---|
| 2026-11-13 | Criação da funcionalidade; família aprovada e entrada no repositório | lucasgenial |
