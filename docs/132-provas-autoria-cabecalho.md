# Autoria das provas e contexto compartilhado

## Planejamento revisado — 03/11/2026

Correção prioritária anterior a ID1. A vinculação de dois professores à mesma turma não compartilha suas provas. Professor institucional consulta suas próprias entradas e provas criadas pela coordenação/direção somente quando vinculadas a uma turma atribuída a ele. Não pode copiar as provas institucionais, nem mesmo se a mesma conta as criou em um vínculo de gestão. Professor independente consulta suas próprias entradas no espaço pessoal. Coordenador e diretor escolar mantêm acesso às provas e turmas de todos os professores de sua escola. Nenhuma dessas funções concede acesso a outra instituição. A cópia para colega continua CP2, sem antecipação neste pacote.

Não mudar autoria dos registros existentes para fazer o botão aparecer. A mesma consulta central deve proteger listagem, painel, turma, gabarito, resultados, relatórios, cartões e operações diretas. O nome do criador da entrada é projetado na API e mostrado como **Criada por** em lista/card e detalhe; sem expor CPF, e-mail ou conta serializada. A procedência da cópia permanece distinta do criador da nova entrada.

Decisão final aprovada pelo usuário: **Meu espaço** é o título do bloco recolhível, inicialmente fechado no desktop e no celular. O resumo mostra **somente Meu espaço e o indicador de expansão**, sem nome de instituição depois do título. Aberto, apresenta duas colunas60/40: rótulos na primeira linha e valores na segunda, com quebra natural para nomes longos. Vínculo escolar usa Minha escola/Meu perfil; professor autônomo usa Tipo de espaço: Pessoal/Meu perfil, sem sugerir acesso institucional. Sem borda, fundo extra, sombra ou linha azul. O resumo é apenas texto de peso600 e seta discreta, sem aparência de botão, inclusive no tema escuro. `details/summary` nativo, foco visível e teclado; expansão não troca o vínculo. Não há card no menu lateral.

A troca permanece na barra superior por **ícone de44px em todas as resoluções**, sem texto visível. Legenda nativa e nome acessível identificam Trocar vínculo ou Trocar acesso e o contexto ativo. Aplicação pelo layout compartilhado e um único componente. Menu da conta usa botão semântico, estado expandido, Escape e retorno de foco.

O ícone só existe quando a identidade possui alternativa distinta do acesso atual. A lista é obtida no servidor pela identidade, sem reaproveitar o token contextual para enumerar vínculos e sem transmitir tokens à página. Com apenas o acesso atual, nem ícone nem modal são renderizados. Em falha de consulta, não inventar disponibilidade; orientar atualização dentro de Meu espaço.

Ao clicar, abre um modal nativo com nome do espaço, perfil e marcador Atual sem ação. As alternativas têm Entrar explícito; a seleção usa os endpoints existentes e é revalidada pela API. JSON devolve somente URL com novo handle vinculado à sessão, sem token. O handle da outra aba continua válido. CSRF e formulários sem propagação do contexto anterior permanecem. Sem JavaScript, existe alternativa para a página de seleção.

Escape/Fechar retornam foco ao ícone. A solicitação tem AbortController e limite20s; os controles são restaurados no finally e o foco vai para o erro. Falha de rede, resposta inválida ou5xx informa confirmação incerta, sem afirmar que a troca foi recusada. Erros confirmados de seleção permanecem no modal. Não há repetição automática da operação.


| Entrega | Critério | Situação ao preparar implementação |
|---|---|---|
| A — escopo por autoria | Dois professores na mesma turma não compartilham provas; institucionais exigem turma atribuída; coordenação/direção mantêm visão da escola | Implementado e testado |
| B — Criada por | Nome escapado em lista/card/detalhe; conta não serializada | Implementado e testado |
| C — contexto compartilhado | Meu espaço recolhível, duas colunas, troca por ícone, temas e teclado | Aprovado em revisão independente |
| D — integração e homologação | CI final, backup, comparação acadêmica e conciliação explícita do legado; produção inalterada | Aguardando CI/publicação |
| ID1 — identidade | Próxima etapa principal, fora deste pacote | Planejada |

Migration aditiva: perfil_criacao registra a função usada na criação e não acompanha mudanças futuras do perfil da pessoa; não é aceito do formulário. Não inferir essa função histórica para todo o legado. As quatro provas existentes confirmadas pelo responsável como criadas pela coordenação serão conciliadas explicitamente, com recibo e conferência do autor, sem trocar criado_por. O comparador do deploy desconsidera somente essa coluna nova ao confrontar os conteúdos acadêmicos anteriores; todos os campos anteriores e todas as nove tabelas continuam comparados. Publicar o pacote concluído somente na homologação. Promoção da produção continua dependente de decisão expressa posterior.

## Validação e parecer

Suíte API local:677 aprovados/24 ignorados por exigirem MariaDB; alvos finais de escopo, consulta, cópia e fila43 testes/455 asserções. O CI acrescenta ProvasAutoriaTest à suíte MariaDB. Web final148 testes/1429 asserções, incluindo vínculo único/múltiplo, interno, CSRF, escape, lookup indisponível, erro de seleção, confirmação incerta e preservação da outra aba sem exposição de tokens. Os mocks dos módulos anteriores agora distinguem explicitamente a consulta de identidade das operações contextuais. Sintaxe JavaScript e Python conferida. Os heads2d733cf e5258826 passaram nos cinco checks antes do último modal; a versão final executa seus próprios checks.

O revisor independente de UI/UX e Product Design aprovou explicitamente a configuração final após reabrir seis capturas atuais: estados aberto/fechado, temas claro/escuro, escola com nome longo em320px e espaço pessoal. Resumo textual sem aparência de botão, duas colunas e nomenclatura adequados; expansão por teclado conferida. Parecer também preserva aprovação da autoria e da orientação do rascunho institucional. Este parecer substitui os desenhos intermediários, não comprova integração API por imagens, não é certificação WCAG integral e não autoriza produção.

As prévias usam dados fictícios fora do repositório; integração e contexto por aba são conferidos separadamente. Nenhum stub de API ou JavaScript de preview integra esta entrega.

Revisão adicional independente do modal aprovada após corrigir espera indefinida e foco: desktop/320px, tema escuro com modal aberto, vínculo único sem ícone, erro de seleção e limite20s. Ensaio no Chrome com resposta local simulada de22s confirmou mensagem de confirmação incerta, foco em troca-contexto-erro, Fechar reabilitado e aria-busy removido. Esta aprovação adicional resolve os bloqueios do modal e preserva o parecer da autoria/rascunho.
