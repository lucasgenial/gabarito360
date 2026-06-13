# Arquitetura V2 e Reaproveitamento da V1

## Decisão estrutural

A V2 continua no mesmo repositório e parte da branch R7. A arquitetura alvo é:

```text
Web Blade/Livewire ------\
Flutter Android ----------> API/Aplicação Laravel 12 ---> MariaDB
Integrações externas ----/           |  |  |
                                     |  |  +--> Storage privado/S3
                                     |  +-----> Redis, filas e cache
                                     +--------> Reverb/WebSockets
Flutter/Workers ------------------------------> OpenCV/OMR
```

## Matriz de reaproveitamento

| Área V1/R7 | Decisão V2 | Motivo |
|---|---|---|
| Laravel 12, Actions, Requests, Resources, Policies e Services | Reaproveitar | Estrutura testada e compatível |
| MariaDB, migrations base, Eloquent e constraints | Reaproveitar e ampliar | ORM já existe; faltam capacidades do mockup |
| Sanctum, escopos, auditoria e idempotência | Reaproveitar e endurecer | Fundamentos necessários |
| Aplicações, leituras, resultados e relatórios | Reaproveitar e ampliar | Fluxo operacional já testado |
| Docker, Nginx, Redis, Reverb, CI, backup/restauração | Reaproveitar | Gates técnicos aprovados |
| Tokens e componentes UI R4 | Reavaliar componente a componente | Úteis, mas paridade com mockup não foi garantida |
| Páginas Blade R5 | Refatorar ou substituir | Não implementam integralmente o mockup |
| Matriz funcional, rotas e planos R1-R7 | Manter como histórico | Reduziram indevidamente o escopo |
| Flutter R6 | Reaproveitar fundação; reconstruir jornadas | Apenas login/listas/snapshot estão prontos |
| OMR pré-homologação | Reaproveitar contrato; concluir implementação | Falta dataset real, câmera e homologação |

## Lacunas que exigem novas estruturas

- Onboarding/cadastro, recuperação de senha e aluno autenticado.
- Agenda, reuniões, visitas, notificações e central de atividades.
- Configurações completas, integrações, plano/uso e solicitações LGPD.
- Relatórios e exportações integrais do mockup.
- Paridade visual automatizada para todas as telas.
- Android com câmera, revisão, sincronização e operação real.
- OMR OpenCV homologado em cartões e dispositivos reais.

## Limites de refatoração

- Não remover contratos válidos antes de existir substituto testado.
- Não reescrever domínio já correto apenas por mudança visual.
- Não copiar dados estáticos ou JavaScript do protótipo como regra de negócio.
- Cada substituição deve preservar testes de segurança e operação existentes.
