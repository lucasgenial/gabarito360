# ADR-D011 - Corte de escopo e matriz de permissoes do MVP

- **Status:** aceita para o MVP
- **Data:** 2026-06-10
- **Responsaveis:** Produto + Arquitetura + Seguranca
- **Prazo:** revisar antes de alterar o gate do MVP ou iniciar nova release

## Contexto

A documentacao descreve a evolucao completa do Gabarito360, mas o MVP precisa de uma fronteira objetiva para evitar que funcionalidades de V2 bloqueiem o piloto. A matriz anterior tambem agrupava Professor e Aplicador e continha permissoes condicionais sem escopo preciso.

## Decisao

O MVP deve comprovar um fluxo online controlado de ponta a ponta:

1. preparar cadastros, prova, gabarito vigente e aplicacao;
2. capturar um cartao no app Android usando um modelo homologado;
3. processar, revisar, vincular ao aluno e confirmar a leitura;
4. corrigir automaticamente e atualizar o dashboard simples da aplicacao;
5. consultar e exportar em CSV o relatorio basico por turma;
6. registrar auditoria das operacoes criticas.

Funcionalidades de V2 ou posteriores nao bloqueiam a liberacao do MVP. Isso inclui offline completo, PDF/XLSX, dashboards avancados, multiplos modelos produtivos, recorrection em lote e interface completa de auditoria.

A matriz canonica de permissoes esta em `docs/05-casos-de-uso.md` e segue estes principios:

- negar por padrao;
- autorizar por perfil e escopo explicitos;
- separar os perfis Professor e Aplicador;
- impedir que perfis administrativos operem cartoes implicitamente;
- exigir perfil operacional e vinculo com a aplicacao para capturar, revisar, corrigir ou confirmar leituras;
- limitar Leitor/Consulta a leitura concedida;
- limitar Suporte Tecnico a diagnostico controlado e auditado.

## Justificativa

O corte reduz o risco de ampliar o prazo do piloto com capacidades que nao sao necessarias para validar o fluxo principal. A separacao explicita de perfis e escopos reduz privilegios excessivos e fornece uma base verificavel para Policies e testes de autorizacao.

## Impactos

- O roadmap deve tratar somente itens incluidos como bloqueadores da liberacao.
- Requisitos e casos de uso V2 permanecem documentados, mas nao entram no gate do MVP.
- Policies e testes devem cobrir cada acao da matriz antes da entrega da funcionalidade correspondente.
- Um gestor que precise atuar como aplicador deve receber o perfil operacional e o vinculo exigido.
- Mudancas no corte ou na matriz exigem revisao deste ADR e dos documentos canonicos afetados.
