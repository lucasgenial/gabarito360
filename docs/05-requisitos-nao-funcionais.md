# 05 — Requisitos Não-Funcionais

---

## RNF-001 — Performance

### RNF-001.1 — Velocidade de leitura OMR
- Meta declarada no produto: **12 segundos por cartão lido**
- Medição: do upload da imagem até o resultado de correção disponível

### RNF-001.2 — Precisão do OMR
- Meta declarada no produto: **98,6% de precisão**
- Medição: percentual de cartões lidos corretamente sem intervenção manual

### RNF-001.3 — Tempo de resposta da API
- Endpoints de listagem: < 500ms (p95)
- Endpoints de leitura OMR: < 15s (inclui processamento de imagem)
- Endpoints de relatório: < 2s (p95)

### RNF-001.4 — Carga simultânea
- A arquitetura deve suportar uso simultâneo por múltiplas escolas e usuários
- Volume alvo MVP: 340 escolas, ~12.840 alunos (dados dos mockups)

---

## RNF-002 — Segurança

### RNF-002.1 — Autenticação
- Tokens JWT com expiração configurável
- Refresh token para sessões longas
- Opção "Manter conectado" persistente

### RNF-002.2 — Autorização
- Controle de acesso baseado em perfil (RBAC)
- Validação de escopo na API (não apenas no frontend)
- Nenhum dado fora do escopo do usuário pode ser retornado

### RNF-002.3 — Proteção de dados
- Conformidade com LGPD (Lei 13.709/2018)
- Dados de alunos menores de idade com proteção especial
- Aceite de termos e LGPD obrigatório no cadastro

### RNF-002.4 — Comunicação segura
- HTTPS obrigatório em todos os ambientes (exceto desenvolvimento local)
- Sem dados sensíveis em URLs

### RNF-002.5 — Proteção contra ataques comuns
- Proteção contra SQL Injection (via ORM/prepared statements)
- Proteção contra XSS
- Proteção contra CSRF (tokens Laravel)
- Rate limiting nos endpoints de autenticação

---

## RNF-003 — Disponibilidade e Confiabilidade

### RNF-003.1 — Disponibilidade
- SLA alvo: 99,5% (excluindo janelas de manutenção)

### RNF-003.2 — Backup
- Backup automático diário do banco de dados
- Retenção mínima de 30 dias

### RNF-003.3 — Integração SEGES
- Sincronização com tolerância a falhas
- Log de atrasos na sincronização (conforme alerta no dashboard-admin)
- Alertas visíveis quando sincronização atrasada (> limiar configurável)

---

## RNF-004 — Usabilidade e Acessibilidade

### RNF-004.1 — Padrão gov.br
- Interface no Padrão Digital de Governo brasileiro
- GovBar presente em todas as telas autenticadas
- Links de "Acessibilidade" e "Alto Contraste" no GovBar

### RNF-004.2 — Acessibilidade
- Conformidade com WCAG 2.1 nível AA
- Semântica HTML correta (headings hierárquicos, botões/links apropriados)
- Estados de foco visíveis
- Atributos aria-label em ícones sem texto
- Contraste de cores adequado

### RNF-004.3 — Responsividade
- Sem scroll horizontal em nenhum dos breakpoints definidos (ver docs/04-requisitos-funcionais.md RF-011.5)
- Layout adaptativo para todos os breakpoints

### RNF-004.4 — Tema claro/escuro
- Toggle disponível em todas as telas autenticadas
- Preferência do sistema respeitada por padrão

---

## RNF-005 — Manutenibilidade

### RNF-005.1 — Cobertura de testes
- Regras de negócio: 100% cobertos por testes unitários
- Integrações API-banco: cobertura de testes de integração
- Fluxos principais: testes end-to-end para o caminho feliz

### RNF-005.2 — Documentação de código
- Código autoexplicativo (nomes de variáveis e funções descritivos)
- Comentários apenas para lógica não-óbvia ou contornos específicos

### RNF-005.3 — Arquitetura extensível
- Arquitetura deve acomodar as evoluções planejadas (ver docs/02-visao-geral.md) sem grandes refatorações
- Sem acoplamento rígido entre módulos

---

## RNF-006 — Internacionalização

### RNF-006.1 — Idioma
- Interface em Português Brasileiro (pt-BR)
- Datas no formato DD/MM/AAAA
- Números decimais com vírgula (ex.: 6,8)
- Moeda não aplicável no MVP

---

## RNF-007 — Infraestrutura

### RNF-007.1 — Banco de dados
- MariaDB como único banco de dados aprovado
- Índices obrigatórios em colunas de filtro frequente (escola_id, turma_id, prova_id, aluno_id)

### RNF-007.2 — Armazenamento de imagens
- Imagens de cartões-resposta (OMR) devem ser armazenadas com segurança
- Estratégia de armazenamento a definir no plano de execução (disco local ou object storage)

### RNF-007.3 — Ambientes
- Desenvolvimento local
- Staging (homologação)
- Produção

### RNF-007.4 — Logging
- Logs de acesso e erro estruturados
- Log de falhas de integração (SEGES e outros)
- Log de leituras OMR com confiança e resultado

---

## RNF-008 — Evolução Futura

A arquitetura não pode inviabilizar:

| Capacidade Futura          | Impacto Arquitetural Previsto                              |
|----------------------------|-----------------------------------------------------------|
| Banco de Questões          | Modelo de dados deve prever entidade Questão separada de Gabarito |
| Múltiplas versões de prova | ProvaVersao deve ser entidade distinta de Prova           |
| Gabaritos individualizados | Cartão deve ter FK para ProvaVersao, não apenas Prova     |
| OMR externo                | Motor OMR deve ser abstrato/plugável                      |
| Mobile offline             | API deve suportar sincronização incremental               |
| Questões discursivas       | Correção deve ser extensível para correção manual         |
| Pontuação por peso         | Estrutura de questões deve incluir campo de peso          |
