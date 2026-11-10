# ADR-0012 — Coordenador muda aluno de turma; professor autônomo tem cadastro mínimo; o aluno é representado por avatar

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-10-31
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** docs/28-plano-mestre-proximos-modulos.md, seção 4 "Já definidas" (alunos, professor autônomo, avatar)
**Afeta:** [FN-026](../funcionalidades/026-matricula-enturmacao/planejamento.md); [FN-027](../funcionalidades/027-professor-independente/planejamento.md); [FN-033](../funcionalidades/033-estrutura-escolar/planejamento.md); [FN-066](../funcionalidades/066-avatares-alunos/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

O sistema é pedagógico: guarda provas, gabaritos e respostas. Não precisa de foto nem vídeo do aluno. Era preciso definir quem muda aluno de turma e que dados o professor autônomo mantém.

## Decisão

- O coordenador muda aluno de turma. O professor autônomo tem o mesmo poder sobre os próprios alunos.
- O professor autônomo tem cadastro mínimo de alunos: nome, turma e identificação simples. Não há CPF, nascimento nem responsável. Ele não tem portal dos pais.
- Dados de alunos só seguem com o professor se o contrato da escola permitir. Sem a cláusula, ele leva provas, materiais e notas anonimizadas.
- Cadastro completo e portal dos pais são exclusivos de escolas e perfis superiores.
- O sistema não guarda foto de aluno nem vídeo. O aluno é representado por avatar. A foto do cartão-resposta permanece, porque é o documento da correção.
- Dados copiados para uma escola permanecem nela se o professor sair. Se a escola sair, os dados dela são preservados e cada professor pode continuar pagando.

## Consequências

- O campo `foto_path` e as fotos existentes são preservados. A remoção de fotos não foi autorizada.
- Matrícula e enturmação por período seguem em [FN-026](../funcionalidades/026-matricula-enturmacao/planejamento.md).

## Verificação

- A galeria de 36 avatares e as iniciais estão publicadas ([FN-066](../funcionalidades/066-avatares-alunos/planejamento.md)).
- Confirme que nenhuma tela nova de aluno pede foto.
