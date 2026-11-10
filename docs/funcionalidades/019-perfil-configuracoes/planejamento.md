# 019 — Meu perfil, configurações e imagem de perfil — Planejamento

**Estado no tronco:** Concluída
**Prioridade no plano mestre:** sem posição nas ondas do §5 (concluída). Pendências sem priorização: limpeza de imagens órfãs e confirmação das configurações para o Diretor Escolar (MP-040). IM1 está só na homologação (§7).
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/13 MP-024, docs/19 MP-040; IM1 surgiu como ramo do PF1-C e virou módulo próprio (docs/134–137)
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Dar a cada pessoa uma página para ver e manter os próprios dados (nome, senha, e-mail, foto ou avatar) e dar ao Administrador da Rede a tela de parâmetros da rede (metas e limiar de alerta).

## 2. Escopo
- **Inclui:** "Meu perfil" (dados da conta e edição do nome); imagem de perfil (galeria de 36 avatares ou foto com recorte, HEIC e DNG parcial); exibição da foto própria e de membros da equipe; configurações da rede (`meta_media`, `meta_minima`, `limiar_seges_min`).
- **Não inclui:**
  - Troca de senha e de e-mail (regras e casos de uso): FN-011; aqui só a tela que as hospeda.
  - Avatares e fotos de alunos: FN-066.
  - Imagens de turmas e de educadores: FN-037.
  - Parâmetros do espaço pessoal: FN-022.
  - Seções do mockup de configurações sem implementação (integrações, calendário letivo, notificações): FN-056, FN-057, FN-039.
  - Preferências dos painéis: FN-054.

## 3. Fontes de verdade
- Mockups: `mockups/perfil.html`, `mockups/configuracoes.html` ([inventário §24 e §25](../../01-inventario-dos-mockups.md)).
- Origem: [docs/13](../../13-roadmap.md) MP-024, [docs/19](../../19-plano-telas-por-perfil.md) MP-040, [docs/134](../../134-pf1-b-matriz-perfis-prioritarios.md), [docs/135](../../135-pf1-c-perfis-escolares.md), [docs/136](../../136-im1-preparo-fotos.md), [docs/137](../../137-im1-avatar-cabecalho.md).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md); UC-011 e UC-012 de [docs/07](../../07-casos-de-uso.md).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md) (`rede.configurar`).
- Código: `apps/web/app/Http/Controllers/PerfilController.php`, `ConfiguracaoController.php`; `apps/api/app/Http/Controllers/Api/AuthController.php` (`me`, `update`), `ImagemUsuarioController.php`, `ConfiguracaoController.php`; `apps/api/app/Services/ImagemPerfil.php`; migration `2026_11_03_000010_imagem_perfil_usuarios`.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-011 autenticacao-conta | Sessão e rotas `auth/me` | Parcial |
| FN-012 permissoes-motor | `rede.configurar`, `usuario.ver` | Concluída |
| FN-014 perfis-institucionais | IM1 nasceu no PF1-C | Concluída |
| FN-018 seguranca-urls-sessao | Fotos por proxy autenticado (UR1-C) | Em homologação |

**Funcionalidades que dependem desta:** FN-037 (reutiliza o preparo de imagem), FN-052 (metas da rede nos painéis).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (MP-024) | Perfil e configurações da rede | Edição do próprio perfil; metas da rede | Entregue (produção) | 076e759 |
| 2.1 (PF1-C, IM1-A/B) | Foto/avatar com recorte circular, zoom e rotação | WebP 384×384 | Entregue (homologação, validado) | PR #164, #166 |
| 2.2 (IM1-C) | HEIC/HEIF | Conversão no navegador | Entregue (homologação, validado) | PR #166 |
| 2.3 (IM1-D) | DNG pela prévia JPEG | Sem RAW universal, por decisão | Parcial (por decisão) | docs/136 |
| 2.4 (IM1-E/F) | Ciclo de substituição, testes, homologação | Arquivo antigo removido após sucesso | Entregue (homologação, validado) | PR #166, #167; docs/139 |
| 3.1 | Limpeza de imagens órfãs | Reconciliada e auditada; sem exclusão em massa | Pendente | docs/136 |
| 3.2 (MP-040) | Configurações da escola para o Diretor Escolar | Confirmar se existe ou planejar | Pendente (confirmação) | §3 do plano mestre; ver §8 |

## 6. Contrato técnico (proporcional ao risco)
- **API:** `GET|PUT auth/me`; `POST auth/imagem` (limite `imagem-perfil`); `GET auth/foto`; `GET usuarios/{id}/foto`; `GET|PUT configuracoes` (`rede.configurar`).
- **Imagem:** entrada `jpg`, `jpeg`, `png`, `webp`, até 2 MB e 4096×4096; o servidor recodifica para WebP 384×384 (remove metadados), grava em disco privado `usuarios/fotos/{uuid}.webp`, guarda só caminho e `imagem_versao`. Novo arquivo é gravado antes de trocar a referência; em falha, o novo é apagado; o antigo só sai após sucesso.
- **Autorização da foto de outra pessoa:** própria conta, ou vínculo selecionado com `usuario.ver` e a pessoa dentro da gestão do vínculo; fora disso, 404.
- **Contexto interno:** `auth/me`, `auth/imagem`, `auth/foto` e `auth/email` funcionam em acesso interno.
- **Cache:** respostas de foto com `private, no-store`; a versão vai na URL interna só para atualizar a exibição.
- **Configurações:** com mais de uma rede no alcance, é preciso escolher a rede; validação `meta_media` e `meta_minima` de 0 a 10, `limiar_seges_min` de 1 a 1440.

## 7. Interface
`perfil/show` (dados, senha, e-mail, modal Avatar/Foto) e `configuracoes/index`. Estados: erro de imagem em mensagem própria; 429 com "Muitas tentativas seguidas. Aguarde alguns instantes e tente novamente." IM1 foi validado em celular real na homologação. Conferência por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 2 — Gate de produção | Promoção em blocos | IM1 (PR #164–#167) está na fila do §7 junto com FN-014 | Pendente |
| ⚠ Configurações para o Diretor Escolar (MP-040, mockup §25) | Planejar configurações da escola / retirar do escopo | Divergência confirmada no código: `rede.configurar` é padrão só do Administrador da Rede e não há configuração por escola | Pendente |
| ⚠ Seções do mockup `configuracoes.html` não implementadas | Aguardar os módulos transversais | Usar "Em breve" ou estado vazio, conforme a governança | Pendente |
| Limpeza de imagens órfãs | Comando de reconciliação auditado | Planejar junto com o pacote de retenção (§4 item 11) | Pendente |

## 9. Ramos desta entrega
IM1 começou como ramo do PF1-C e foi promovido a módulo próprio depois do teste real no celular (docs/136).

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | documento criado a partir do inventário e do código | lucasgenial |
