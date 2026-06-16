# 16 — GAP Analysis

Este documento mapeia a diferença entre o que o legacy tinha, o que o MVP atual define e o que ainda está pendente.

---

## Metodologia

Para cada domínio:
1. Estado no legacy (se existia)
2. Estado definido no MVP atual (documentação)
3. GAP: o que precisa ser construído do zero
4. Risco e mitigação

---

## 1. Autenticação e Perfis

| Aspecto                | Legacy                          | MVP Atual Definido                            | GAP                                     | Risco   |
|------------------------|----------------------------------|-----------------------------------------------|-----------------------------------------|---------|
| Mecanismo de auth      | Desconhecido/não reutilizável   | Laravel Sanctum (Bearer token)                | Implementação do zero                   | Baixo   |
| Perfis de acesso       | Estrutura diferente             | 6 perfis oficiais com RBAC                    | Novo modelo de perfis                   | Médio   |
| Escopo hierárquico     | Não documentado                 | rede → nucleo → escola → turma → aluno        | Nova lógica de escopo                   | Alto    |
| Cadastro com LGPD      | Ausente                         | Aceite obrigatório no cadastro                | Implementação nova                      | Baixo   |
| Redefinição de senha   | Desconhecido                    | Fluxo via e-mail                              | Implementação nova                      | Baixo   |

**Decisão:** Nenhum código de autenticação do legacy será reutilizado. Implementação do zero no MP-004 e MP-012.

---

## 2. Estrutura Hierárquica (Rede → Escola)

| Aspecto             | Legacy                        | MVP Atual Definido                            | GAP                                      | Risco   |
|---------------------|-------------------------------|-----------------------------------------------|------------------------------------------|---------|
| Entidade Rede       | Pode não existir              | Obrigatória, raiz da hierarquia               | Criação do zero                          | Baixo   |
| Entidade Núcleo     | Ausente ou diferente          | Nível intermediário rede → escola             | Criação do zero                          | Baixo   |
| Entidade Escola     | Existia parcialmente          | INEP obrigatório, tipos, ativo/inativo        | Reescrever com novos campos              | Baixo   |
| Entidade Turma      | Existia parcialmente          | Nome único por escola/ano, status             | Reescrever                               | Baixo   |
| Entidade Aluno      | Existia                       | Matrícula única, uma turma por período        | Reescrever com regras novas              | Baixo   |
| Cascade deactivation| Desconhecido                  | Desativar escola desativa membros ativos      | Lógica nova                              | Médio   |

---

## 3. Usuários e Membros da Equipe

| Aspecto                   | Legacy                   | MVP Atual Definido                          | GAP                                    | Risco   |
|---------------------------|--------------------------|---------------------------------------------|----------------------------------------|---------|
| Tabela de usuários        | Existia                  | Nova estrutura com perfil e escopos         | Reescrever do zero                     | Baixo   |
| Tabela de escopos         | Ausente                  | usuario_escopos (entidade_tipo + id)        | Nova entidade                          | Médio   |
| Vínculo professor-turma   | Desconhecido             | Professor → turmas via escopos              | Nova lógica                            | Médio   |
| Ativação/desativação      | Desconhecido             | Ativo/inativo com impacto em acesso         | Nova lógica                            | Baixo   |

---

## 4. Avaliações (Provas)

| Aspecto                   | Legacy                       | MVP Atual Definido                                  | GAP                                        | Risco   |
|---------------------------|------------------------------|-----------------------------------------------------|--------------------------------------------|---------|
| Ciclo de vida da prova    | Desconhecido                 | rascunho → publicada → em_correcao → corrigida      | Estado machine nova                        | Médio   |
| Configurações da prova    | Desconhecido                 | num_questoes, num_alternativas, disciplina, bimestre| Novos campos                               | Baixo   |
| Anulação de questão       | Desconhecido                 | Questão anulada conta como acerto para todos        | Nova regra de negócio                      | Médio   |
| Imutabilidade pós-publish | Desconhecido                 | Gabarito não pode ser editado após publicar         | Validação nova na API                      | Baixo   |
| Vinculação a turmas       | Desconhecido                 | Tabela prova_turmas com múltiplas turmas            | Nova lógica                                | Baixo   |

---

## 5. Gabarito

| Aspecto               | Legacy         | MVP Atual Definido                        | GAP                           | Risco   |
|-----------------------|----------------|-------------------------------------------|-------------------------------|---------|
| Editor de bolhas      | Desconhecido   | Interface interativa com toggle de bolhas | Frontend novo                 | Médio   |
| Pesos por questão     | Ausente        | peso definido por gabarito_questoes       | Novo campo (MVP usa peso=1)   | Baixo   |
| Exportação PDF        | Desconhecido   | window.print() via view especial          | Implementação nova            | Baixo   |
| Validação completo    | Ausente        | Não pode publicar com gabarito incompleto | Nova validação                | Baixo   |

---

## 6. OMR (Leitura de Cartões)

| Aspecto                  | Legacy                     | MVP Atual Definido                               | GAP                                        | Risco   |
|--------------------------|----------------------------|--------------------------------------------------|--------------------------------------------|---------|
| Motor de leitura         | Existia (não reaproveitável)| OmrDriverInterface + implementação a definir    | Motor novo ou integração                   | **Alto**|
| Estados do cartão        | Desconhecido               | pendente → processando → lido / ambiguo / erro   | State machine nova                         | Médio   |
| Threshold de confiança   | Desconhecido               | 98,6% acurácia target, < threshold = ambíguo     | Nova lógica de classificação               | Alto    |
| Resolução manual         | Desconhecido               | Tela com opções de resolução por questão ambígua | Fluxo completamente novo                   | Médio   |
| Processamento assíncrono | Desconhecido               | Laravel Queue + Job                              | Implementação nova                         | Médio   |
| Identificação do aluno   | Desconhecido               | QR Code / matrícula no cabeçalho do cartão       | Nova estratégia                            | Alto    |
| OMR externo (futuro)     | Ausente                    | OmrDriverInterface permite troca                 | Interface abstrata no MVP                  | Baixo   |

**Nota crítica:** O motor OMR é o maior risco técnico do projeto. A estratégia de implementação precisa ser definida antes de iniciar MP-021. Opções: OpenCV via microsserviço Python, biblioteca PHP, ou serviço externo.

---

## 7. Notas e Resultados

| Aspecto                   | Legacy       | MVP Atual Definido                                      | GAP                              | Risco   |
|---------------------------|--------------|---------------------------------------------------------|----------------------------------|---------|
| Cálculo de nota           | Existia      | (acertos / total) × nota_maxima, com anulação           | Reescrever com nova fórmula      | Baixo   |
| Status aprovação          | Desconhecido | nota >= media_aprovacao → aprovado, senão → recuperacao | Nova lógica                      | Baixo   |
| Média da turma            | Existia      | Calculada em tempo real ao completar leituras           | Reescrever                       | Baixo   |
| Tendência (↑↓→)           | Ausente      | Comparação com bimestre anterior                        | Lógica nova                      | Médio   |
| Revisão pós-correção      | Ausente      | Gestor pode solicitar revisão de cartão lido            | Fluxo novo                       | Baixo   |

---

## 8. Relatórios e Dashboards

| Aspecto                     | Legacy       | MVP Atual Definido                                    | GAP                             | Risco   |
|-----------------------------|--------------|-------------------------------------------------------|---------------------------------|---------|
| 6 dashboards por perfil     | Parcial      | Todos os 6 documentados com mockups                   | Implementação dos que faltam    | Baixo   |
| Folha de respostas corrigida| Desconhecido | Grid com ok/no/bl (verde/vermelho/amarelo)            | Frontend novo                   | Baixo   |
| Relatório por tema          | Desconhecido | Acertos por tema (quando temas forem adicionados)     | Novo módulo                     | Médio   |
| Exportação PDF de resultado | Desconhecido | Via print() da página de resultado                    | Implementação nova              | Baixo   |
| Breadcrumb dinâmico         | Ausente      | Baseado no parâmetro `from` na URL                    | Lógica nova                     | Baixo   |
| Alertas críticos            | Ausente      | Painel de alertas no dashboard admin                  | Novo componente                 | Baixo   |
| Comparativo aluno vs. turma | Ausente      | Badge "Acima/Abaixo da média da turma"               | Nova query                      | Baixo   |

---

## 9. Integrações

| Aspecto              | Legacy       | MVP Atual Definido                                | GAP                              | Risco   |
|----------------------|--------------|---------------------------------------------------|----------------------------------|---------|
| Integração SEGES     | Desconhecido | Sincronização periódica com alerta de atraso      | Novo serviço                     | Alto    |
| Alerta SEGES no UI   | Ausente      | Banner de alerta no dashboard quando atrasado     | Novo componente                  | Baixo   |

**Nota:** A integração SEGES depende de documentação da API SEGES que ainda não foi levantada. É um risco de dependência externa.

---

## 10. Design System

| Aspecto                   | Legacy       | MVP Atual Definido                       | GAP                                  | Risco   |
|---------------------------|--------------|------------------------------------------|--------------------------------------|---------|
| Gov.br Design System      | Não aplicado | Todos os tokens CSS definidos            | Importar e aplicar os tokens         | Baixo   |
| GovBar                    | Ausente      | Presente em todas as telas autenticadas  | Componente novo                      | Baixo   |
| Toggle tema claro/escuro  | Ausente      | Presente em todas as telas               | Implementação nova                   | Baixo   |
| Responsividade            | Desconhecida | WCAG 2.1 AA, mobile-friendly             | Testar em cada MP                    | Médio   |

---

## 11. Aplicativo Mobile

| Aspecto                  | Legacy       | MVP Atual Definido                       | GAP                                  | Risco   |
|--------------------------|--------------|------------------------------------------|--------------------------------------|---------|
| App Android              | Desconhecido | React Native, captura de cartão, API     | Implementação do zero                | Alto    |
| App iOS                  | Desconhecido | React Native (pós-MVP)                   | Pós-MVP                              | Baixo   |
| Captura via câmera       | Desconhecido | Câmera nativa do device                  | Implementação nova                   | Médio   |

---

## Itens Sem Mockup (Risco de Spec)

Os seguintes módulos estão documentados mas não têm mockup correspondente:

| Módulo                    | Mockup         | Ação Recomendada                                           |
|---------------------------|----------------|------------------------------------------------------------|
| Núcleos (CRUD)            | Não existe     | Criar mockup antes de iniciar MP-014                       |
| Configurações de ano letivo| Parcial        | Verificar se configuracoes.html cobre                      |
| Visitas (gestão)          | Não dedicado   | Aparece no dashboard; criar tela de gestão antes de MP     |
| Importação de turmas (planilha)| Não existe | Criar mockup do fluxo de upload antes de MP-016           |
| Integração SEGES          | Não existe     | Decisão: modal no dashboard ou tela dedicada              |

---

## Riscos Priorizados

| Risco                                     | Probabilidade | Impacto | Mitigação                                                          |
|-------------------------------------------|---------------|---------|--------------------------------------------------------------------|
| Motor OMR sem estratégia definida         | Alta          | Alto    | Definir estratégia técnica antes de MP-021 (spike antes do MP)     |
| SEGES API sem documentação disponível     | Média         | Alto    | Criar mock do SEGES para MVP; integração real na fase 2            |
| Escopo hierárquico com edge cases         | Média         | Médio   | Testes de integração cobrindo todos os perfis × escopos            |
| Mockups ausentes para alguns módulos      | Alta          | Médio   | Criar mockups antes de iniciar o MP correspondente                 |
| Identificação do aluno no cartão OMR      | Alta          | Alto    | Definir estratégia (QR, matrícula, facial) antes de MP-021         |

---

## Conclusão

O MVP da nova geração do Gabarito360 foi documentado do zero, sem dependência de arquitetura do legacy.

**O que pode ser consultado no legacy (somente regras de negócio):**
- Fórmula de cálculo de nota
- Estrutura básica de questões e alternativas
- Alguns critérios de aprovação

**O que NÃO será reaproveitado:**
- Arquitetura de banco (nova modelagem)
- Código de autenticação
- Código frontend
- Código de OMR
- Rotas e controllers
- Configurações de ambiente

**Próximo passo imediato:** Iniciar MP-001 (estrutura do monorepo) e MP-003 (banco de dados).
