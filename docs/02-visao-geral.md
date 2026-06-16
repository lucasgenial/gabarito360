# 02 — Visão Geral do Produto

---

## O Que É o Gabarito360

O Gabarito360 é uma plataforma completa de gestão de avaliações educacionais para redes públicas de ensino.

Não é apenas um leitor de gabaritos.

É uma solução end-to-end que cobre todo o ciclo avaliativo:

```
Cadastro de questões
       ↓
Organização do banco de questões
       ↓
Montagem de provas
       ↓
Geração de versões da prova
       ↓
Geração dos gabaritos
       ↓
Aplicação
       ↓
Leitura OMR (cartão-resposta)
       ↓
Revisão humana
       ↓
Correção automática
       ↓
Consolidação dos resultados
       ↓
Relatórios pedagógicos
       ↓
Dashboards gerenciais
       ↓
Análises por aluno, turma, escola e rede
```

---

## Público-Alvo

### Rede Pública de Ensino
Secretarias municipais e estaduais de educação que precisam gerenciar avaliações em larga escala.

### Perfis de Usuário
- **Administrador da Rede:** gestão macro, visão consolidada de toda a rede
- **Diretor de Núcleo:** supervisão das escolas sob sua regional
- **Diretor Escolar:** gestão pedagógica de uma escola
- **Coordenador:** acompanhamento das provas e desempenho da escola
- **Professor:** criação de provas e acompanhamento das turmas
- **Aluno:** visualização dos próprios resultados

---

## Problemas Que o Gabarito360 Resolve

| Problema                              | Solução                                   |
|---------------------------------------|-------------------------------------------|
| Correção manual lenta e propensa a erros | Leitura OMR via foto do cartão          |
| Falta de visibilidade do desempenho   | Dashboards por perfil em tempo real      |
| Dificuldade de gestão em rede         | Hierarquia rede → núcleo → escola        |
| Relatórios manuais demorados          | Geração automática de relatórios         |
| Cartões ambíguos sem resolução rápida | Fila de revisão manual integrada         |
| Distribuição de provas desorganizada  | Ciclo completo dentro da plataforma      |

---

## Diferenciais

1. **Velocidade:** 12 segundos por cartão lido (conforme mockup da tela de login)
2. **Precisão:** 98,6% de precisão na leitura OMR
3. **Integração:** Sincronização com sistemas da Secretaria (ex.: SEGES)
4. **Visibilidade em múltiplos níveis:** aluno → turma → escola → núcleo → rede
5. **Design gov.br:** interface no Padrão Digital de Governo brasileiro

---

## MVP — O Que Está no Escopo Inicial

O MVP contempla o ciclo básico de avaliação:

### Gestão Estrutural
- Cadastro e gestão de redes
- Cadastro e gestão de núcleos
- Cadastro e gestão de escolas (com INEP, tipo, endereço, contato)
- Cadastro e gestão de turmas (com importação por planilha)
- Cadastro e gestão de alunos
- Cadastro e gestão de membros da equipe (usuários)

### Gestão de Avaliações
- Criação de provas (título, disciplina, série, nº de questões)
- Cadastro do gabarito oficial (editor de bolhas interativo)
- Configurações por prova (alternativas, nota máxima, pontuação)
- Publicação do gabarito

### OMR e Correção
- Leitura dos cartões-resposta via foto
- Acompanhamento em tempo real da leitura
- Resolução manual de cartões ambíguos
- Correção automática baseada no gabarito publicado
- Revisão da leitura

### Resultados e Relatórios
- Resultado individual por aluno (folha corrigida, nota, acertos por tema)
- Relatório consolidado da prova
- Relatório da turma por prova
- Histórico de avaliações do aluno
- Evolução bimestral de notas

### Dashboards
- Dashboard do Administrador da Rede (KPIs + alertas + últimos acessos)
- Dashboard do Diretor de Núcleo (comparativo entre escolas + visitas)
- Dashboard do Diretor Escolar (turmas + equipe + agenda)
- Dashboard do Coordenador (provas em andamento + alunos em atenção)
- Dashboard do Professor (minhas provas + ranking + alunos em atenção)
- Dashboard do Aluno (minhas notas + próximas provas)

### Autenticação e Configuração
- Login com e-mail institucional e senha
- Cadastro de usuário
- Perfil do usuário
- Configurações do sistema

---

## Evolução Planejada (Pós-MVP)

### Fase 2 — Banco de Questões
- Cadastro e importação de questões
- Organização por disciplina, assunto, habilidade, nível, série
- Reutilização de questões entre provas

### Fase 3 — Montador de Provas
- Criação manual e automática de provas
- Seleção de questões por filtros
- Montagem por habilidade, assunto ou disciplina

### Fase 4 — Múltiplas Versões
- Embaralhamento de questões e alternativas
- Geração de versões distintas da mesma prova
- Gabaritos individualizados por versão
- Rastreamento da versão aplicada a cada aluno

### Fase 5 — Questões Discursivas e Pontuação Avançada
- Questões abertas com correção manual
- Peso individual por questão
- Peso por bloco ou por prova

### Fase 6 — OMR Externo
- Suporte a cartões de terceiros
- Cadastro manual do layout
- Treinamento do motor OMR

### Fase 7 — Relatórios Avançados e Indicadores
- Relatórios por habilidade e competência
- Indicadores pedagógicos
- Análise histórica de desempenho

### Fase 8 — Mobile Offline
- App Android e iOS com suporte offline
- Sincronização quando houver conectividade

---

## Contexto Institucional

O sistema opera dentro do contexto do governo brasileiro:
- Interface no Padrão Digital de Governo (gov.br DS)
- E-mail institucional para acesso (nome@edu.gov.br)
- Integração com sistemas da Secretaria de Educação (ex.: SEGES)
- Conformidade com LGPD (Lei Geral de Proteção de Dados)
- Acessibilidade como requisito (GovBar com link de acessibilidade)
