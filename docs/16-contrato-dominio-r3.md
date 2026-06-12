# Contrato de Domínio da R3

## 1. Objetivo

A R3 fornece fontes de dados persistentes, relacionamentos e barreiras de
autorização para as telas canônicas do mockup. Ela não implementa a composição
visual, CRUDs completos, processamento OMR, tempo real ou geração de arquivos.

## 2. Princípios

- Cargo institucional, perfil de autorização e permissão permanecem separados.
- Todo vínculo profissional, acadêmico ou operacional possui vigência explícita.
- Dados de alunos, responsáveis, arquivos e resultados seguem minimização e LGPD.
- Aplicações, leituras, resultados e relatórios são contratos persistentes, mas
  seus fluxos completos pertencem às R5 e R6.
- Métricas futuras devem ser derivadas das tabelas operacionais, nunca de números
  estáticos do mockup.

## 3. Fontes de dados por módulo

| Módulo canônico | Fontes persistentes da R3 |
|---|---|
| Equipe escolar | `cargos`, `usuario_lotacoes`, `usuario_disciplinas`, `aplicadores_turmas` |
| Estrutura acadêmica | `periodos_letivos`, `series_anos`, `disciplinas`, `turmas` |
| Alunos | `alunos`, `responsaveis`, `aluno_responsaveis`, `matriculas_turmas`, `arquivos` |
| Provas | `provas`, `questoes`, `temas_habilidades`, `questao_temas`, `prova_turmas` |
| Correção | `aplicacoes`, `aplicacao_aplicadores`, `aplicacao_alunos`, `cartoes_resposta`, `leituras_cartao`, `respostas_detectadas` |
| Resultados | `resultados`, `resultado_questoes` |
| Relatórios | `relatorios`, `arquivos` |
| Perfil e configurações | `preferencias_usuario`, `preferencias_notificacao`, `solicitacoes_lgpd` |
| Mobile e sincronização | `dispositivos_mobile`, `logs_sincronizacao` |

## 4. Extensões de entidades existentes

- `usuarios`: referência opcional ao arquivo de foto.
- `escolas`: código INEP opcional.
- `turmas`: referências normalizadas a período letivo e série/ano, além de
  capacidade opcional. Os campos legados de ano e série continuam durante a
  transição para não quebrar contratos existentes.
- `alunos`: nome social e referência opcional ao arquivo de foto.
- `provas`: disciplina e série/ano opcionais durante a transição, valor total e
  relacionamento com aplicações.
- `questoes`: enunciado e metadados pedagógicos opcionais.

## 5. Contratos operacionais reservados

As estruturas abaixo são criadas para fechar contratos e consultas futuras:

- uma `aplicacao` executa uma prova publicada para uma turma autorizada;
- `aplicacao_alunos` congela os alunos previstos;
- `leituras_cartao` preserva tentativas e idempotência;
- `respostas_detectadas` separa detecção e resposta final;
- `resultados` mantém versões e somente um resultado vigente;
- `resultado_questoes` sustenta análises por questão e tema;
- `relatorios` registra solicitação, formato, filtros, escopo e expiração.

Nenhuma dessas tabelas autoriza, por si só, captura, confirmação, correção ou
download. Actions, policies e auditoria continuam obrigatórias.

## 6. Permissões consolidadas

Além do catálogo existente, a R3 formaliza:

- `configuracoes.gerenciar`;
- `relatorios.resultados.consultar`;
- `relatorios.consultar`;

Professor pode gerenciar provas somente quando seu perfil possuir
`provas_gabaritos.gerenciar` e o contexto operacional estiver explicitamente
vinculado.

## 7. Gate de conclusão

- migrations executam em MariaDB vazio;
- modelos expõem os relacionamentos centrais;
- constraints impedem duplicidades vigentes e estados inválidos prioritários;
- policies cobrem aplicações, resultados e relatórios;
- o catálogo de permissões é idempotente;
- testes demonstram que cada módulo canônico possui fonte persistente;
- documentação e OpenAPI identificam a fundação R3 sem declarar fluxos ainda não
  implementados.

## 8. Fora da R3

- telas e componentes finais;
- endpoints CRUD para os novos catálogos;
- criação, início e finalização real de aplicações;
- upload e processamento OMR;
- cálculo de resultados;
- geração ou download de relatórios;
- WebSockets, jobs operacionais e app Flutter.
