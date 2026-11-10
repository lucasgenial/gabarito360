# 072 — Site público e apresentação — Planejamento

**Estado no tronco:** Em homologação
**Prioridade no plano mestre:** sem onda própria; bloco "Marca e apresentação pública" da fila de produção (§7), aguardando a decisão §4 item 2; abertura comercial ligada ao §4 item 14 (FN-068)
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/28 E8; home estática (docs/101, 102); H1-P apresentação de pré-lançamento, etapas P1–P5 (docs/161, 162, 164)
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Apresentar o Gabarito360 a professores e gestores em páginas públicas claras, sem preços nem compra, com caminho de contato, e manter a página inicial disponível mesmo com a aplicação em manutenção.

## 2. Escopo
- **Inclui:** página inicial (`/`), modalidades (`/planos`) com as quatro categorias de atendimento (Professor autônomo, Escolas, Núcleos e redes, Secretarias), conteúdo local em `config/apresentacao.php`, sete frentes futuras com selo literal "Em breve", demonstrações com dados fictícios, FAQ, menu e rodapé públicos com redes sociais, metadados de busca e de compartilhamento; exportação estática da home servida pelo Nginx sem PHP, API, sessão ou banco; página de manutenção 503 legível.
- **Não inclui:** envio do Fale Conosco e Turnstile (FN-060, E7); termos e privacidade (FN-061); checkout, assinatura, preços e autocadastro (FN-030, FN-068); marca e favicons (FN-071); implementação das frentes "Em breve" (FN-043, FN-044, FN-051, FN-029); login e recuperação (FN-011).

## 3. Fontes de verdade
[docs/28 E8](../../28-plano-mestre-proximos-modulos.md), [docs/101](../../101-home-independente-manutencao.md), [docs/102](../../102-planejamento-l4-t6.md), [docs/161](../../161-identidade-e-cabecalhos.md), [docs/162](../../162-identidade-oficial-assets.md), [docs/164](../../164-apresentacao-publica-pre-lancamento.md). Mockup: landing aprovada em 19/06/2026 ([docs/01](../../01-inventario-dos-mockups.md), seção da landing); a apresentação de pré-lançamento substituiu a estrutura original por decisão do responsável em 05/11 (docs/164). Código: `apps/web/routes/web.php` (`home`, `planos.index`, `leads.*`), `HomeController`, `PlanoController`, views `home/*`, `planos/*`, `components/menu-publico.blade.php`, `components/rodape-publico.blade.php`, `config/apresentacao.php`, `public/css/apresentacao-publica.css`, `public/js/apresentacao-publica.js`; `scripts/operacao/publicar-home.sh`, `exportar-home.php`, `assets-publicos.php`, `manutencao.html`, `preparar-nginx-publico.py`; `deploy/nginx/publico-independente.conf`; testes `PaginasPublicasTest` e `MarcaWebTest`. Casos de uso: [casos-de-uso.md](casos-de-uso.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-071 marca | Bundle da marca na home estática | Em homologação |
| FN-070 identidade visual | Base visual das páginas públicas (V2) | Concluída |
| FN-060 e-mail (E7) | Destino do botão "Falar com a gente" | Entregue (produção) |
| FN-003 deploy (B5) | B5 regenera a home após reabrir os serviços | Rodapé e helpers a reconciliar antes do próximo B5 (§7) |
| FN-068 financeiro | Condições de lançamento comercial (§4 item 14) | Pendente |

**Funcionalidades que dependem desta:** FN-061 (links de termos e privacidade no rodapé); FN-030 (contratação parte das páginas públicas); FN-043, FN-044, FN-051 e FN-029 (retirada do selo "Em breve" quando entregues).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (E8 visual) | Landing e páginas públicas no padrão da página inicial | Auditoria das páginas públicas | Entregue (produção) | PR #59, #60 |
| 1.2 | Home estática independente da manutenção; links relativos; Instagram, Facebook e TikTok | Home e CSS 200 sem PHP; login 503 legível; API 503 JSON | Entregue (servidor) | PR #135–#137; docs/101 |
| 1.3 | YouTube no rodapé | Link oficial com nome acessível | Pendente no plano mestre; ⚠ presente no código da homologação | docs/162; `rodape-publico.blade.php`; ver §8 |
| 1.4 (H1-P P1–P2) | Matriz de evidências, narrativa e copy | Registradas no docs/164 | Entregue | docs/164 |
| 1.5 (H1-P P3) | Home, modalidades, demonstrações, navegação e metadados | Sem preços; selo "Em breve" em cada frente futura | Entregue (homologação) | PR #191 |
| 1.6 (H1-P P4) | Contrato estático e whitelist com CSS/JS da apresentação | SHA, MIME e versões preservadas | Entregue (homologação) | PR #191; docs/162 |
| 1.7 (H1-P P5) | Revisão, CI, merge e homologação | Versão final conferida | Entregue (homologação); versão final Incerta | PR #191; confirmar pesquisas restauradas e contato compacto |
| 1.8 | Reconciliar rodapé e helpers da home antes do próximo B5 | Home de produção gerada sem regressão | Pendente | §7; docs/102 |
| 1.9 | Abertura comercial e produção | Decisão comercial e condições de lançamento | Pendente | docs/164; §4 itens 2 e 14 |

## 6. Contrato técnico (proporcional ao risco)
- Home e `/planos` não consultam a API comercial; `exportar-home.php` recusa HTML com "R$", formulários, tokens ou contexto autenticado.
- Publicação: exporta e valida em pasta nova, instala o bundle e troca o symlink `current` de forma atômica; falha preserva a versão anterior. A origem pública é argumento explícito, para não gerar metadados apontando para outro ambiente.
- Durante manutenção: rotas web recebem 503 com página responsiva, API recebe 503 JSON, ambos com `Retry-After: 60` e `Cache-Control: no-store`.
- Sem migration. A configuração Nginx real é gerada a partir da existente e validada com `nginx -t`; não foi aplicada em produção na entrega H1.

## 7. Interface
Abertura com degradê #1D4F9E→#001534; conteúdo essencial em HTML; menu móvel funciona sem JavaScript; FAQ e ajudas em disclosures acessíveis; âncoras respeitam o cabeçalho fixo. Seção de pesquisas com três métricas e fontes primárias, sem tratá-las como medições do produto. Conferência registrada por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 2 — gate de produção | Promover em blocos | Revogar o gate L4 em favor do ADR-H1 | Pendente |
| §4 item 14 — condições de lançamento comercial | Definir condições e distribuição de recursos futuros por modalidade | Tratar no planejamento de FN-068 | Pendente |
| §4 item 13 — frentes "Em breve" sem plano | Manter o selo só para o que estiver priorizado | Registrar as demais como candidatas (feito: FN-043, 044, 051, 029) | Pendente |
| §4 item 5 — autocadastro e checkout | Declarar substituídos pela contratação por contato | Registrar no docs/06 | Pendente |
| Divergência plano mestre × código: YouTube | Plano mestre e docs/101 dizem "Pendente"; docs/162 e o código já trazem `https://www.youtube.com/@gabarito360` no rodapé (homologação) | Atualizar o plano mestre para "Entregue (homologação)"; produção segue pendente | — |
| Versão final da apresentação | Confirmar no deploy da homologação | Conferência registrada por texto | Incerto |

## 9. Ramos desta entrega
Nenhum aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
