# 071 — Marca oficial e cabeçalhos de documentos — Planejamento

**Implementação:** Em homologação
**Etapa:** Em homologação
**Prioridade no plano mestre:** sem onda própria; bloco "Marca e apresentação pública" da fila de produção (§7), aguardando a decisão §4 item 2. Pendência no §3: "Produção"
**Natureza:** existente documentada a partir do código
**Origem:** pedido de branding do responsável — docs/161 e docs/162, etapas H1-A a H1-E. Atenção: "H1" também nomeia a homologação isolada (docs/127–129, FN-003)
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Aplicar a marca oficial do Gabarito360 (símbolo, wordmark, favicons e imagem de compartilhamento) em toda a aplicação e padronizar os cabeçalhos dos documentos impressos e em PDF conforme o emissor: escola com rede, escola sem rede ou uso autônomo. Atende todos os usuários e quem recebe os documentos.

## 2. Escopo
- **Inclui (entregue na homologação):** H1-A SVG oficial, fontes Figtree 800 e Albert Sans 800 (só no "6"), tokens da marca; H1-B componente `<x-marca>` (variantes `horizontal`/`icon`, tamanhos `sm`/`md`/`lg`, temas `auto`/`light`/`dark`, animação opcional) em layouts, páginas públicas, acesso, e-mails e carregamento; H1-C favicons, ícones de aplicação, manifests e imagem OG 1200×630; H1-D cabeçalhos institucionais e autônomos compartilhados, título centralizado, caixas de aluno e nota; H1-E testes, revisão, CI e publicação na homologação; bundle imutável da marca para a home estática.
- **Não inclui:** conteúdo e narrativa da apresentação pública (FN-072); identidade base, cores e gráficos (FN-070); selo presenteado/pagante nos documentos (FN-069); regras de geração dos PDFs de resultado e cartões (FN-047, FN-049); associação de turmas pessoais a uma instituição, que exige etapa própria e não está planejada (docs/161); service worker ou funcionamento offline.

## 3. Fontes de verdade
docs/161 (`docs/161-identidade-e-cabecalhos.md`), docs/162 (`docs/162-identidade-oficial-assets.md`). Código: `apps/web/resources/views/components/marca.blade.php`, `layouts/_marca-head.blade.php`, `apps/web/public/css/marca.css`, `apps/web/public/brand/*`, `apps/web/resources/brand/` (canônico e fontes com licenças), `relatorios/_cabecalho.blade.php`, `apps/api/app/Support/Cabecalho.php` (`paraPdf`, `contexto`), fontes em `apps/api/omr/fonts/`, `apps/api/resources/emails/base.html`, teste `apps/web/tests/Feature/MarcaWebTest.php`. Mockups e style system continuam sendo a referência das telas; a marca não altera layouts. Casos de uso: [casos-de-uso.md](casos-de-uso.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-070 identidade visual | Tokens e tipografia do corpo | Concluída |
| FN-047 OMR1–3 | PDFs de resultado recebem o novo cabeçalho | Em homologação |
| FN-003 deploy e homologação | Publicação e configuração Nginx | Decisão §4 item 2 para produção |

**Funcionalidades que dependem desta:** FN-072 (home estática usa o bundle da marca); FN-069 (selo convive com o cabeçalho nos documentos); FN-067 (P5/P6 usam logos e marca).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (H1-A) | SVG oficial, fontes e tokens | Vetor preservado; licenças OFL junto às fontes | Entregue (homologação) | PR #191; docs/162 |
| 1.2 (H1-B) | `<x-marca>` em layouts, público, acesso, e-mails e carregamento | Nome acessível único; `prefers-reduced-motion` respeitado | Entregue (homologação) | PR #191 |
| 1.3 (H1-C) | Favicons, manifests, OG | Maskable dentro da área segura; metadados sem dados privados | Entregue (homologação) | PR #191 |
| 1.4 (H1-D) | Cabeçalhos de documentos por emissor | Grade óptica dos cartões intacta; título centralizado | Entregue (homologação) | PR #191; docs/161 |
| 1.5 (H1-E) | Testes, revisão, CI, homologação | CI aprovada e publicação na VM 120 | Entregue (homologação) | PR #191 |
| 1.6 | Produção, incluindo a configuração Nginx | Decisão expressa; backup e reversão | Pendente | docs/161 "não aplicar em produção"; §7 |

## 6. Contrato técnico (proporcional ao risco)
- Sem migration, sem mudança de rotas, autenticação, banco, leitura ou notas.
- Um resolvedor de identidade único para exportações (`Cabecalho::paraPdf`) e um partial de impressão para relatórios HTML; arquivos antigos não são regenerados.
- Cabeçalho decidido por dados institucionais existentes; não inferir escola a partir de referências pessoais.
- Bundle público imutável em `/_institucional/marca-{sha256}/`, com whitelist, SHA e MIME conferidos (`scripts/operacao/assets-publicos.php`).

## 7. Interface
Marca no menu lateral, cabeçalho compacto, rodapé e telas de acesso (versão escura sobre o degradê #1D4F9E→#001534). Em celular, a marca permanece no cartão de acesso. Conferências futuras registradas por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 2 — gate de produção e fila acumulada | Promover em blocos ou manter só na homologação | Revogar o gate L4 em favor do ADR-H1 e promover em blocos, começando pela segurança | Pendente |
| Turmas pessoais associadas a uma instituição | Etapa própria futura | Registrar como candidata se for desejada | Não planejada |

## 9. Ramos desta entrega
Nenhum aberto. A etapa P do docs/161 virou FN-072.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
