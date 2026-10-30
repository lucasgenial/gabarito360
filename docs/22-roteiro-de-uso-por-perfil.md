# 22 — Roteiro de uso por perfil (etapa 7)

> Fechamento do plano `21-plano-perfis-permissoes-e-telas.md`. Cada perfil tem um roteiro curto para
> conferir, **no computador e no celular**, que vê o que deve ver e não vê o que não deve.
> O que cada perfil pode fazer está em `03-perfis-e-permissoes.md` (v2).

## Usuários de teste (produção)

| Perfil | E-mail | Escopo |
|---|---|---|
| Administrador da rede | `admin@gabarito360.dev` | toda a rede |
| Diretor de núcleo | `dir.nucleo@gabarito360.dev` | núcleo 4 (NTE 00027) |
| Diretor escolar | `dir.escolar@gabarito360.dev` | escola 8 — Col. Est. do Campo Dr. Eraldo Tinoco |
| Coordenador | `coordenador@gabarito360.dev` | escola 8 |
| Professor | `professor@gabarito360.dev` | escola 8, turmas 3A e 3B |
| Aluno | `aluno@gabarito360.dev` | (sem vínculo com aluno ainda) |

Aplicador e secretário de educação **não têm usuário de teste**: crie um pelo diretor escolar
(aplicador) ou pelo admin, se quiser percorrer esses roteiros.

**Como testar no celular:** abra o site no celular (ou no navegador do computador com a largura de
celular). A navegação fica na **barra inferior**; o restante está em **Mais**.

## Roteiros

### Administrador da rede
1. Menu: Painel, Provas, Corrigir, Turmas, Relatórios, Escolas + **Mais** (Alunos, Equipe). Celular: Painel, Escolas, Relatórios, Equipe + Mais.
2. **Escolas → Nova escola**: digite um INEP (ex.: `29491665`) — os dados do Censo são preenchidos. Cancele ou salve.
3. **Relatórios**: abre o relatório da rede; troque para um núcleo e uma escola.
4. **Equipe → Permissões** de qualquer membro: todas as permissões alteráveis.

### Diretor de núcleo
1. Menu do celular: Painel, Escolas, Relatórios, Equipe + Mais.
2. **Escolas → Colégio… Eraldo Tinoco → + Cadastrar anexo** → INEP `29491657`: vínculo de anexo, sede e núcleo já marcados. Marque turnos/etapas e salve. Repita com `29491665`.
3. Tente abrir uma escola de outro núcleo pela URL: **não encontrada**.
4. **Relatórios**: relatório do núcleo, com o comparativo entre as escolas.
5. **Equipe → + Novo membro**: só oferece diretor escolar e coordenador.

### Diretor escolar
1. Menu do celular: Painel, Provas, Turmas, Relatórios + Mais.
2. **Escolas → (sua escola) → Editar dados da escola**: INEP e núcleo aparecem bloqueados; altere telefone/turnos e salve.
3. Não aparece **Nova escola**; abrir `/escolas/nova` pela URL é recusado.
4. **Equipe → Permissões** do professor: conceda "Relatório da escola" até uma data → o professor passa a ver **Relatórios** no menu. Depois volte ao padrão.
5. **Relatórios**: relatório da escola em A4 (Imprimir/PDF) com os logos.

### Coordenador
1. Menu do celular: Painel, Corrigir, Provas, Turmas + Mais.
2. **Provas → nova prova** (criar e publicar gabarito).
3. **Corrigir → uma prova → fechar a correção**; depois **reabrir** (o coordenador pode).
4. **Equipe → + Novo membro**: cadastra professor (com turmas), aplicador e aluno.

### Professor
1. Menu (computador e celular): Painel, Corrigir, Provas, Turmas — sem Equipe, Escolas ou Relatórios da escola.
2. **Turmas**: só 3A e 3B.
3. **Corrigir → prova → câmera**: guias nos cantos, captura automática; o aluno corrigido aparece na lista com a foto.
4. **Resultado do aluno**: acertos/erros, PDF e WhatsApp.
5. Com a correção **fechada** pelo coordenador: não consegue enviar nem excluir cartões, nem reabrir.

### Aplicador *(criar usuário de teste)*
1. Menu: só o Painel; a prova do dia → **Enviar cartões** (modo câmera).
2. Não vê notas, gabarito nem relatórios.

### Aluno
1. Menu: só o Painel com as próprias notas (quando o usuário estiver ligado a um aluno).

### Secretário de educação *(criar usuário de teste)*
1. Menu: Painel, Relatórios (rede), Escolas.

## Testes automáticos

`apps/api/tests/Feature/PerfisTest.php` (rodar com `php artisan test`, banco SQLite em memória):

- **16 ações principais × 7 perfis**: cada perfil recebe exatamente o que o catálogo permite
  (ver escolas, cadastrar escola, consultar o Censo, editar escola, ver/cadastrar equipe, turmas,
  alunos, provas, criar prova, gabarito, relatórios de prova/turma/escola/núcleo, permissões);
- **escopo**: diretor de núcleo e diretor escolar não veem escola/núcleo de fora; professor só vê as suas turmas;
- **quem cadastra quem**; **extra concedida libera**, **retirada bloqueia**, **extra vencida não vale**,
  ninguém concede o que não pode dar;
- **correção fechada**: professor e aplicador não reabrem; coordenador reabre;
- login devolve as permissões efetivas.

Resultado em 30/10/2026: **32 testes, 158 verificações, todos passando**.
