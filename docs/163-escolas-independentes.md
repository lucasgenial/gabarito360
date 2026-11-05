# EI1 — cadastro institucional de escolas independentes

Revisão de escopo em05/11/2026, solicitada durante H1. Escola independente não é espaço pessoal de professor autônomo e não é um cargo novo de usuário. O sistema já possui cliente tipoescola e espaço tipoescola, mas escolas.nucleo_id é obrigatório no esquema e no formulário; a criação herda o cliente do núcleo. Portanto a contratação direta sem hierarquia ainda precisa de implementação completa.

## Modelo funcional a preservar

- Separar categoria administrativa (estadual, municipal, federal, privada), contratação (cliente responsável) e organização institucional (sem rede, rede com/sem núcleo). Uma escola privada pode integrar uma rede; não inferir organização pela categoria ou pagamento.
- Escola independente tem sua própria identidade, espaço e equipe. Diretor, vice-diretor, coordenador, professor, secretaria e outros cargos seguem a matriz já aprovada. Não criar um superperfil escola_autonoma nem atribuir acesso administrativo a professores.
- Cadastro pergunta se a escola integra uma rede; rede e núcleo não devem ser obrigatórios para a modalidade independente. Quando houver rede, seleção restrita ao escopo autorizado; núcleo só quando aplicável. Não cadastrar rede/núcleo fictícios para contornar requisitos antigos.
- Reutilizar INEP/catálogo/UF/município, sigla e verificações de duplicidade. Reivindicar ou vincular uma instituição existente requer comprovação/autorização; selecionar escola do catálogo não concede acesso à sua base ou equipe.
- Suporte/desenvolvimento mantém atribuições de provisionamento. Cliente pagador não concede acesso pedagógico por si só. A escola pode ser contratante direta e manter sua rede institucional quando realmente houver vínculo; organização pedagógica e custeio são dimensões diferentes.
- Cabeçalhos derivados do contexto: rede à esquerda/escola à direita; escola sem rede apenas sua logo à esquerda. Professor autônomo permanece com Gabarito360 e indicação de uso autônomo, sem fingir emissão institucional.
- Não converter escolas ou dados atuais automaticamente. Migração deve ser aditiva/compatível, preservar provas/gabaritos/cartões/notas e registrar mudanças organizacionais; encerramentos e transferências continuam auditados.

## Complemento do responsável: cadastro, edição e adesão

Decisão de05/11/2026: o escopo inclui criação e edição da escola, bem como as instituições superiores/redes declaradas. A escola não cria uma rede no formulário. Seleciona registro já conferido ou solicita ao suporte seu cadastro/validação; somente Suporte ou Desenvolvimento cria redes neste fluxo. Reutilizar registros oficiais/operacionais existentes, sem duplicá-los por contrato ou pedido.

Conferir identificadores aplicáveis e fontes antes de associar rede/instituição, sem presumir que toda rede tenha código INEP nem usar o INEP de uma escola como identificador da rede. Inventariar os dados efetivamente disponíveis no catálogo e as relações já cadastradas. Preservar origem, evidência, situação da conferência, responsável e data; pendência de comprovação não vira vínculo verificado automaticamente. A dependência administrativa encontrada numa fonte pública não é contrato nem autorização de acesso.

| Caso | Comportamento acordado |
|---|---|
| Escola privada sem rede | Adesão direta, cliente/espaço próprios |
| Escola privada declara rede existente, com ou sem contrato | Pode aderir diretamente, mesmo sem licença/acesso concedido pela rede; conferência da associação não exige contrato ativo da rede |
| Rede/instituição declarada não existe | Abrir solicitação ao suporte; escola não cria rede e nenhuma aprovação comercial inventa a relação institucional |
| Escola pública com instituição superior identificada | Informar a instituição superior e orientar contato para solicitar cadastro/acesso por essa via; não inferir licença pela presença no INEP |
| Escola pública insiste em contratar diretamente | Exigir liberação explícita de Suporte ou Desenvolvimento, com motivo, ator, data e alcance da exceção; não liberar por pagamento ou simples alteração para categoria privada |

O requisito público foi entendido a partir da instituição superior identificada/vinculada, não apenas de sua condição de cliente. Quando não houver instituição superior confiavelmente identificada, encaminhar à conferência do suporte, sem inventar rede e sem habilitar adesão pública automaticamente.

A mesma política vale para edição: trocar rede, categoria administrativa ou instituição superior não contorna bloqueios. Mudanças relevantes são propostas/conferidas com trilha de auditoria; encerram relações antigas prospectivamente e não reescrevem documentos emitidos, provas ou históricos. Distinguir dados próprios editáveis da escola de correções de relações institucionais que precisam de suporte. Não bloquear a operação pedagógica já existente por uma solicitação de correção cadastral pendente.

Três dimensões independentes: associação institucional, cliente/contrato responsável e autorização operacional de compartilhamento/acesso. Associar à rede não concede aos gestores dela acesso automático ao espaço contratado diretamente pela escola. O desenho precisa definir concessões explícitas compatíveis com o modelo de isolamento antes de implementar qualquer mudança de hierarquia.

## Regra comercial adicional aprovada

O responsável confirmou a denominação **escola autônoma** para a adesão direta, inclusive de escola pertencente a uma rede. Seu espaço e seus dados permanecem isolados da instância superior. A instância superior somente poderá acessar esses dados se assumir formalmente a responsabilidade financeira junto ao sistema. A assunção precisa ser efetivada por fluxo próprio e auditável; não inferir acesso a partir de uma associação ao catálogo, de um contrato da rede com outras escolas ou de um pagamento avulso. A efetivação deve delimitar quais escolas/espaços passam à responsabilidade da instância superior e registrar o início da nova condição, preservando o histórico anterior.

Na transição, **não devolver valores anteriormente pagos pela escola, instituição ou professor**. Registrar esta política no planejamento financeiro e nas condições apresentadas no futuro fluxo. Esta entrega não realiza cobranças, reembolsos, cancelamentos, transferência de titularidade financeira nem ampliação de acesso. Ainda precisam ser especificados no módulo financeiro: adesão da instância superior, alcance e vigência da assunção, tratamento do período já pago e comportamento em eventual encerramento posterior. Não inventar crédito/prorrata, regra de vencimento ou retirada de acesso sem esse desenho.

As expressões escolaautônoma e professorautônomo não unificam seus espaços ou permissões. Escolaautônoma é modalidade institucional/comercial; seus usuários continuam com os cargos escolares aprovados. Professorautônomo continua no espaço pessoal. Mudança da responsabilidade financeira não deve apagar dados, registros de autoria, documentos emitidos ou vínculos históricos.

| Etapa | Entrega | Situação |
|---|---|---|
| EI1-A | Inventário de constraints, triggers, fontes oficiais, autorização, consultas e contratação dependentes de rede/núcleo | Diagnóstico inicial concluído; detalhamento pendente |
| EI1-B | Cadastro/edição: escola independente, instituição superior e seleção/solicitação de rede; revisão UI/UX prévia | Planejada |
| EI1-C | Esquema compatível e separação de associação, contrato e autorização sem hierarquia obrigatória | Planejada |
| EI1-D | Solicitação/conferência de redes pelo suporte e trilha de alterações institucionais | Planejada |
| EI1-E | Adesão privada direta, encaminhamento público e liberação excepcional auditada | Planejada |
| EI1-F | Provisionamento de cliente/espaço e equipe com permissões existentes | Planejada |
| EI1-G | Testes de isolamento, edição sem bypass, matriz pública/privada, dados antigos e homologação | Planejada |

Executar EI1 após fechar a entrega de identidade H1, com replanejamento antes da implementação. Não misturar a alteração de regras/banco com o pedido de branding, que explicitamente preserva essas camadas. As subdivisões técnicas não exigem novas autorizações; publicação primeiro na homologação. Produção depende da decisão do responsável.
