# 67 — A1: planejamento revisto e autorização interna

01/11/2026. T4-A/PR #100 aprovado e incorporado. Continuidade autorizada, sem deploy.

## Revisão antes da fase

T4 depende de A1: os perfis atuais são institucionais/pessoais, enquanto os operadores internos precisam atuar sobre clientes da plataforma. Um vínculo de rede não pode virar acesso comercial global. A1 será antecipado como dependência, sem declarar concluída a Fase 3.

| Parte | Resultado | Limite |
|---|---|---|
| A1-A (esta entrega) | Acessos internos com vigência, situação e versão; contexto exclusivo; política comercial e testes de revogação | Sem API de seleção, telas, promoção de contas ou ativação no servidor |
| A1-B | Transporte por token, seleção por aba e gestão controlada dos acessos; bootstrap do primeiro Desenvolvedor sob procedimento próprio | Acesso interno não pode usar token escolar/identidade como autorização; sem autopromoção |
| A1-C | Secretário Escolar no vínculo de escola; catálogo, hierarquia de cadastro, menus e regressões | Não confundir com Secretário de Educação; não conceder poderes de núcleo/rede |
| T4-B | API e telas do cadastro de clientes com consulta controlada de espaços/vínculos e referência contratual | Validar pelo Computer Use; contratos completos são F1 |

## Política desta base

- `usuario_acessos_internos` representa atribuição explícita de Desenvolvedor, Suporte ou Financeiro; não cria espaço pedagógico fictício nem altera `usuarios.perfil`.
- Contexto interno e vínculo escolar/pessoal são mutuamente exclusivos. A mesma conta pode ter ambos, mas precisa selecionar um; os poderes não são unidos.
- Os três perfis podem consultar e gerir cadastro comercial de clientes. Nenhum recebe provas, alunos, notas, relatórios ou arquivos pedagógicos por esse contexto. Atendimento com acesso a dados depende do fluxo auditado de AU3; Financeiro permanece sem acesso pedagógico.
- Somente Desenvolvedor pode gerir perfis internos inferiores (Suporte e Financeiro). Concessão de Desenvolvedor e primeiro acesso não são expostos nesta base; não há promoção automática de administrador de rede.
- Política interna explícita separada da matriz institucional de oito colunas: evita deslocar permissões escolares ao acrescentar perfis de plataforma.
- Habilitação desligada por padrão. Revalidar conta, titularidade da atribuição, perfil, vigência, situação e versão a cada uso; não reutilizar cache legado. Jobs não podem serializar apenas a conta e perder o contexto.

## Verificação necessária

Testar negativa sem contexto, sem habilitação e por atribuição alheia; suspensão, expiração, conta desativada e mudança de versão; troca exclusiva escolar/interno; rejeição de serialização incompleta; cadastro comercial autorizado e ausência de acesso pedagógico, inclusive quando o perfil global legado é administrador de rede. Executar regressões SQLite e MariaDB antes de pedir merge.

Nenhuma conta real, política de retenção, cobrança ou infraestrutura é alterada por esta entrega. A1 completo e T4-B continuam pendentes; publicação exige ensaio e aprovação separados.

## Resultado local

Regressões selecionadas de A1, cadastro, perfis e integração pessoal: 49 testes/433 verificações aprovados. Após acrescentar a revalidação dentro da transação comercial, a execução final de A1/cadastro passou com 16 testes/181 verificações. CI inclui as novas verificações também em MariaDB. Revisão por `engineering:code-review` aplicada à separação de contexto, à política comercial e à revogação; confirmação do CI completo registrada no PR.
