# ADR-D013 - Contrato de produto web da R1

- **Status:** aceita
- **Data:** 2026-06-12
- **Responsaveis:** Produto + Arquitetura + Design + Seguranca
- **Revalidacao:** antes de ampliar o escopo do MVP ou iniciar integracoes externas

## Contexto

O mockup funcional em `style-system/` ampliou a visao do painel web e revelou
decisoes que ainda nao estavam fechadas: cadastro aberto, autenticacao gov.br,
aluno autenticado, agenda, integracoes, plano comercial, cargos institucionais,
permissoes para provas e formatos de relatorio.

A R1 precisa transformar o mockup em contrato implementavel antes da criacao das
migrations MariaDB e das novas telas.

## Decisoes

1. Nao havera auto-cadastro aberto. Usuarios serao criados ou convidados por
   gestores autorizados. A aba de cadastro do mockup de login nao e canonica.
2. Recuperacao de senha faz parte do MVP. Autenticacao ou integracao gov.br fica
   adiada e nao se deve alegar vinculacao oficial com o Governo Federal.
3. O dashboard autenticado do aluno fica para V2. No MVP, resultados individuais
   sao consultados por profissionais autorizados.
4. Agenda e reunioes ficam para V2. O MVP pode exibir apenas o calendario de
   provas e aplicacoes derivado dos registros operacionais.
5. Integracoes externas, exportacao integral de dados e multiplos idiomas ficam
   para V2. Plano, faturamento e limites comerciais estao fora do escopo atual.
6. PDF passa a integrar o MVP para os relatorios canonicos de aluno, prova e
   turma/prova. CSV continua no MVP; XLSX permanece em V2.
7. Professor pode criar, editar e publicar provas somente quando possuir a
   permissao explicita `provas_gabaritos.gerenciar` e estiver dentro do escopo
   escolar e academico concedido. O cargo, isoladamente, nao concede permissao.
8. Diretor, vice-diretor, coordenador e professor sao cargos institucionais.
   Perfis e permissoes continuam sendo o mecanismo de autorizacao.
9. Um usuario pode possuir mais de um cargo, perfil, escola, disciplina e turma,
   sempre por vinculos explicitos, vigentes e auditaveis.
10. Exclusoes permanentes de registros operacionais e historicos nao fazem parte
    dos fluxos comuns. Devem ser usadas inativacao, encerramento, anonimizacao ou
    descarte controlado conforme LGPD.
11. A rota `/painel` seleciona a composicao do dashboard conforme permissoes,
    cargos e contexto ativo. Os HTMLs de dashboard nao geram rotas duplicadas.
12. O tema claro e o padrao. Tema escuro depende de escolha explicita e
    persistida pelo usuario ou dispositivo.

## Telas duplicadas e nao canonicas

| Arquivo | Decisao |
|---|---|
| `aluno-cadastrar.html` | Historico; substituido por `aluno-cadastrar-redesign.html` |
| `resultado.html` | Historico; substituido por `resultado-dinamico.html` |
| `dashboard.html` | Referencia compartilhada de composicao, sem rota exclusiva |
| `dashboard-aluno.html` | Referencia V2, sem rota habilitada no MVP |
| `app-android.html` | Link inexistente; nao criar como pagina web |

## Impactos

- Esta decisao substitui a restricao de criacao de provas por professor descrita
  na ADR-D011, em `RN013` e na matriz de permissoes anterior.
- Esta decisao antecipa PDF para os tres relatorios canonicos do mockup, sem
  antecipar XLSX.
- Cargo institucional deve ser separado de perfil de autorizacao na modelagem.
- A modelagem MariaDB deve suportar preferencias, equipe escolar, responsaveis,
  disciplinas, periodos letivos, temas/habilidades e relatorios.
- Nenhuma integracao adiada deve ser simulada como funcional na interface.

## Alternativas rejeitadas

- Copiar todas as acoes do mockup para o MVP sem decisao de escopo.
- Usar cargo como permissao implicita.
- Criar uma rota de dashboard para cada papel.
- Manter PostgreSQL como premissa da modelagem.
