# ADR-D005 - Motivo de correcao manual

- **Status:** aceita para o MVP
- **Data:** 2026-06-10
- **Responsaveis:** Produto + Auditoria
- **Prazo:** resolvida; aplicar antes da implementacao da confirmacao

## Contexto

Correcoes manuais alteram a resposta usada no resultado e precisam ser rastreaveis.

## Decisao

Toda alteracao manual de resposta exige motivo obrigatorio, alem do valor detectado, valor final, usuario e horario.

- O motivo deve ter entre 10 e 500 caracteres depois de normalizado.
- A interface pode oferecer motivos padronizados, mas deve permitir complemento textual.
- Digitacao manual do codigo impresso quando a leitura automatica falhar deve registrar motivo estruturado `codigo_impresso_nao_detectado`.
- Troca de um codigo impresso detectado por outro exige justificativa textual.
- Nao e permitido editar silenciosamente uma leitura confirmada.

## Justificativa

A obrigatoriedade reduz alteracoes acidentais, apoia auditoria e permite identificar falhas recorrentes do OMR.

## Impactos

- Requests e app devem impedir confirmacao sem motivo aplicavel.
- Auditoria deve preservar valor anterior e novo.
- Relatorios operacionais podem agregar motivos sem expor dados pessoais.
