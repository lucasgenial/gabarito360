# 061 — Termos, privacidade e aceite — Planejamento

**Implementação:** Parcial
**Etapa:** Em planejamento
**Conforme princípios:** versão 1
**Prioridade no plano mestre:** Onda 4 (proposta), item "061 termos e privacidade (L1)", dependente de D13 e D14
**Natureza:** evolução de existente (prévias sem rota já no código; publicação, aceite e cookies novos)
**Origem:** tronco original — docs/28 Fase 2, pacote L1; requisitos RL7, RL15 e RL16 do docs/33; prévias do docs/35; E8 passo 3 (termos e privacidade adiados para L1)
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Publicar o aviso de privacidade e as condições de uso do Gabarito360, com canal do encarregado, registrar a versão aceita por cada usuário e tratar cookies conforme a regra de necessários por padrão. Atende usuários das instituições, visitantes do site público e, depois, o portal dos responsáveis (FN-067).

## 2. Escopo
- **Inclui:** rotas públicas `/termos` e `/privacidade` a partir das prévias existentes; preenchimento dos dados da empresa (D13); tabelas `termos_versoes` e `termos_aceites`; exigência de aceite no próximo login quando houver versão nova; canal do encarregado no site (RL15); tratamento de cookies (RL16); links no rodapé público.
- **Não inclui:** termo de ciência do responsável e autorização de comunicações do portal (FN-067, P2); inventário, retenção e lista de suboperadores (FN-062); pedidos dos titulares (FN-064); cláusulas contratuais com clientes e preços (FN-068); revisão do conteúdo jurídico em si, que é decisão D14 do responsável.

## 3. Fontes de verdade
- Documentos de origem: docs/28 L1 e E8 (`docs/28-plano-mestre-proximos-modulos.md`), docs/33 §3, §6 e §8 (`docs/33-lgpd-privacidade-e-consentimento.md`), docs/35 (`docs/35-previas-juridicas.md`), docs/39 §1 (`docs/39-validacoes-l2.md`) (Cloudflare Web Analytics mantido e documentado no aviso).
- Código atual: `apps/web/resources/views/legal/termos.blade.php` e `legal/privacidade.blade.php` (prévias sem rota); `apps/web/tests/Feature/PaginasPublicasTest.php` garante 404 em `/termos` e `/privacidade`.
- Mockups: não há mockup específico; usa o layout público (`layouts/public`) e o rodapé público (`components/rodape-publico.blade.php`). Inventário: docs/01 (`docs/01-inventario-dos-mockups.md`) §30 (páginas sem mockup).
- Perfis: docs/03 (`docs/03-perfis-e-permissoes.md`). Casos de uso: [casos-de-uso.md](casos-de-uso.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| D13 (dados da empresa) | Razão social, CNPJ, endereço e encarregado preenchem os campos das prévias | Pendente (§4 item 9) |
| D14 (revisão jurídica) | Advogado ou encarregado revisa o docs/33 antes de publicar; aprovação de conteúdo não equivale a revisão jurídica (docs/35) | Pendente (§4 item 9) |
| FN-062 inventário e retenção | Aviso referencia `[LINK DOS SUBOPERADORES]` e `[LINK DA TABELA DE RETENÇÃO]` | Parcial |
| FN-072 site público | Rodapé e layout públicos recebem os links | Em homologação |

**Funcionalidades que dependem desta:** FN-067 portal dos responsáveis (P2 consentimento depende de L1).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (L1 prévia) | Prévias de privacidade e termos com campos a preencher, sem rota | Duas views renderizadas; rotas ausentes | Entregue (código) | PR #64; docs/35 |
| 1.2 (L1 passo 1) | Publicar aviso e canal do encarregado; links no rodapé | RL15 atendido; campos preenchidos sem valores inventados | Pendente | bloqueada por D13, D14 |
| 1.3 (L1 passo 2) | Tabelas `termos_versoes` e `termos_aceites` | Migrations aditivas; versão vigente única por documento | Pendente | — |
| 1.4 (L1 passo 3) | Aceite obrigatório no próximo login | RL7 atendido; aceite auditado; recusa impede uso | Pendente | — |
| 1.5 (L1 passo 4) | Cookies conforme RL16 | Só necessários por padrão; escolha para opcionais, se houver | Pendente | — |

## 6. Contrato técnico (proporcional ao risco)
- A API é dona das tabelas de versões e aceites; a web consulta a versão vigente e envia o aceite pelo BFF autenticado (padrão UR4, sem identificadores na URL).
- Aceite registra usuário, versão, data e contexto mínimo, sem guardar IP completo além do que a política de retenção (FN-062) aprovar.
- A exigência de aceite no login não pode bloquear o login de contas internas de recuperação sem caminho definido; detalhar no contrato da subfase 1.4.
- Páginas públicas de termos e privacidade entram na exportação estática da home (FN-072) somente se o responsável decidir; hoje a whitelist não as inclui.
- Sem medição de carga específica; o aceite é uma escrita por usuário por versão.

## 7. Interface
- Páginas públicas legíveis em celular e desktop, temas claro e escuro, sem rolagem horizontal (validação das prévias registrada no docs/35).
- Tela ou diálogo de aceite com texto acessível, ação explícita e estado de erro. Conferência registrada por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 9 — D13 dados da empresa | Fornecer razão social, CNPJ, endereço e encarregado | Responder D13 e D14 primeiro (destravam L1 e P2) | Pendente |
| §4 item 9 — D14 revisão jurídica | Advogado ou encarregado revisa o docs/33 | Idem | Pendente |
| Escopo dos cookies (RL16) | Hoje só cookies de sessão e XSRF (docs/39 §4); Cloudflare Web Analytics declarado sem cookies | Confirmar se há cookie opcional antes de construir um seletor | Pendente |

## 9. Ramos desta entrega
Nenhum aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
