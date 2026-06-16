# 08 — OMR (Optical Mark Recognition)

---

## O Que É o OMR no Gabarito360

O OMR é o motor de leitura automática de cartões-resposta (gabaritos de alunos).

Permite que o sistema leia as marcações feitas pelos alunos nas folhas de resposta e as compare com o gabarito oficial, gerando a correção automática.

**Meta de desempenho declarada no produto:**
- Velocidade: 12 segundos por cartão lido
- Precisão: 98,6% de acertos sem intervenção manual

---

## Modos de Operação

### Modo Nativo (MVP)
- O cartão-resposta é gerado pelo próprio Gabarito360
- O layout é padronizado e conhecido pelo motor de leitura
- Leitura via foto tirada pelo celular

### Modo Externo (Futuro)
- Suporte a cartões produzidos por terceiros
- Requer:
  - Cadastro manual do layout do cartão
  - Definição das áreas de leitura
  - Definição do padrão de marcação
  - Treinamento do motor OMR para o novo layout

---

## Fluxo de Leitura OMR (MVP)

```
1. Aplicação da prova
       ↓
2. Captura do cartão pelo celular (foto)
       ↓
3. Upload da imagem para a API
       ↓
4. Processamento pelo motor OMR
       ↓
5. Detecção das marcações por questão
       ↓
6. Cálculo do índice de confiança
       ↓
7. Decisão: Lido (confiança OK) ou Ambíguo (confiança baixa)
       ↓
8. Se Ambíguo: fila de revisão manual
       ↓
9. Comparação com gabarito oficial
       ↓
10. Cálculo da nota
       ↓
11. Armazenamento do resultado
```

---

## Estados do Cartão

| Estado    | Descrição                                              | Ação Necessária     |
|-----------|--------------------------------------------------------|---------------------|
| Pendente  | Ainda não processado pelo OMR                         | Aguardar            |
| Lido      | Processado com confiança suficiente                   | Nenhuma             |
| Ambíguo   | Confiança abaixo do limiar ou marcações múltiplas     | Resolução manual    |

---

## Cartões Ambíguos — Tipos de Problema

Os seguintes cenários geram cartões ambíguos (identificados nos mockups):

| Problema                              | Exemplo                                    |
|---------------------------------------|--------------------------------------------|
| Múltiplas marcações na mesma questão  | "Questão 7 — marcações em B e D"          |
| Nenhuma marcação detectada            | "Questão 14 — nenhuma marcação detectada" |
| Confiança de leitura abaixo do limiar | Marcação muito fraca ou rasurada           |

---

## Resolução Manual de Ambíguos

Na tela `acompanhar-correcao.html`:

1. Cada cartão ambíguo exibe:
   - ID do cartão (ex.: "#021")
   - Questão problemática e tipo de problema
   - Botões com as opções possíveis (ex.: [B] [D])

2. O usuário clica na alternativa correta

3. O sistema:
   - Registra a resolução com usuário e timestamp
   - Move o cartão para "Lido"
   - Decrementa contador de ambíguos
   - Incrementa contador de lidos
   - Atualiza barra de progresso e donut

---

## Revisão Pós-Correção

Na tela `resultado.html`:

- Botão "Revisar leitura" disponível
- Permite corrigir uma leitura individual após a correção estar concluída
- A revisão altera a nota do aluno e atualiza todos os relatórios dependentes
- A revisão é registrada com usuário responsável e motivo

---

## Confiança da Leitura

- Exibida na tela de resultado: "Leitura OMR · 98,6% de confiança"
- Confiança por questão: cada questão tem seu próprio índice
- Confiança da leitura total: média ponderada das questões

---

## Estrutura de Dados do Cartão (Referência para Modelo)

| Campo              | Tipo     | Descrição                                        |
|--------------------|----------|--------------------------------------------------|
| id                 | UUID     | Identificador único                              |
| prova_id           | FK       | Prova a que pertence                             |
| aluno_id           | FK       | Aluno identificado no cartão                     |
| imagem_url         | string   | URL da imagem do cartão capturado                |
| status             | enum     | pendente / lido / ambiguo                        |
| confianca_geral    | decimal  | Confiança geral da leitura (0.0 a 1.0)          |
| respostas          | json     | Array de {questao, alternativa_marcada, confianca}|
| resolvido_por      | FK       | Usuário que resolveu ambiguidade (nullable)      |
| resolvido_em       | datetime | Timestamp da resolução (nullable)               |
| revisado_por       | FK       | Usuário que revisou pós-correção (nullable)      |
| revisado_em        | datetime | Timestamp da revisão (nullable)                 |

---

## Cartão-Resposta (Folha do Aluno)

### Geração (MVP Nativo)
- O sistema gera o cartão-resposta em PDF
- Opção habilitada nas configurações da prova: "Gerar cartão-resposta em PDF"
- O cartão contém:
  - Cabeçalho com nome da escola, prova, disciplina, data
  - Campo para nome do aluno
  - Campo para matrícula
  - Grid de bolhas (A-E) para cada questão
  - Código de identificação da prova (QR code ou código de barras — a definir)

### Identificação do Aluno no Cartão
- O motor OMR precisa associar o cartão ao aluno correto
- Estratégias possíveis:
  1. Aluno preenche matrícula manualmente no campo → OMR lê + validação humana
  2. Cartão pré-impresso com nome/matrícula → OMR identifica automaticamente
  3. Vinculação manual pós-leitura → usuário associa cartão ao aluno
- **Estratégia para o MVP:** a definir no plano de execução (MP-021)

---

## Requisitos Técnicos do Motor OMR

### Entrada
- Imagem da foto do cartão (JPEG/PNG)
- Resolução mínima recomendada: 1200×1600px

### Processamento
- Detecção e correção de perspectiva (skew correction)
- Detecção das bolhas por posição
- Análise de intensidade do preenchimento por bolha
- Cálculo de confiança por questão

### Saída
- Array de respostas com confiança por questão
- Status geral do cartão (lido/ambíguo)

### Bibliotecas Candidatas (a confirmar no MP-021)
- Python: OpenCV + NumPy (via microsserviço ou fila)
- PHP: integração via shell ou API interna
- Alternativa: serviço externo de OMR via API REST

---

## Considerações de Arquitetura

### O motor OMR deve ser:
- **Abstrato/plugável:** implementado como serviço separado ou driver substituível
- **Assíncrono:** o processamento de imagem não bloqueia a UI
- **Rastreável:** toda leitura registrada com log de confiança
- **Extensível:** preparado para suporte a cartões externos no futuro

### Fluxo assíncrono recomendado:
```
Upload da imagem → Job na fila → Motor OMR → Resultado no banco → Notificação via WebSocket ou polling
```

---

## Escopo Futuro — OMR Externo

Para suportar cartões de terceiros, será necessário:
1. Tela de cadastro do layout (definição das regiões de interesse)
2. Definição do número de questões e alternativas por região
3. Upload de cartão de referência para calibração
4. Treinamento ou ajuste do motor para o novo layout
5. Validação com cartões de teste antes de uso em produção

Este escopo será tratado em um MP específico pós-MVP.
