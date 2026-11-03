# CI1-E — solicitações privadas e manutenção do catálogo

03/11/2026. Revisão anterior ao código, após PR157 integrado e conferência visual CI1-D aprovada pelo responsável. Sem deploy, migration, carga ou e-mails na produção. A decisão posterior127 autoriza preparar homologação isolada e publicar nela todas as entregas; produção depende de decisão explícita do responsável. Gabaritos e demais tabelas acadêmicas existentes não são alvos desta entrega.

## Sequência e autorização

| Parte | Entrega |
|---|---|
| E1 | Contrato versionado, persistência e poderes separados |
| E2 | Protocolos pessoais: enviar, acompanhar, complementar e cancelar |
| E3 | Fila interna: analisar, solicitar complemento, indeferir ou resolver para referência existente/nova |
| E4 | Cadastro manual e correções auditáveis; manutenção pela gestão apenas com autoridade comprovada |
| E5 | API/web, integridade SQLite/MariaDB, isolamento, falhas, concorrência, Chrome e CI |

Professor autônomo usa contexto pessoal vigente, com capacidade específica para solicitar. Não cadastra diretamente. Suporte/desenvolvedor usam contexto interno revalidado, capacidades analisar/manter_global sob flag do catálogo; financeiro não as recebe. Diretor escolar, diretor de núcleo e administrador da rede recebem manter_escopo, condicionado a vínculo vigente e associação explícita de autoridade entre escola operacional e unidade pública, verificada internamente. Não deduzir autoridade de município, nome ou código informado no formulário. Primeiro cadastro sem autoridade vai à revisão interna. Secretario_educacao continua dependente do regime VI1; enum legado não habilita esse papel. Nenhuma capacidade de catálogo libera dados acadêmicos.

Pedidos pessoais pertencem ao proprietário e espaço de origem, com vínculo original guardado como evidência, e nunca aparecem nas consultas/filas/contagens/exportações da escola, núcleo ou rede. A gestão não vê solicitantes. Protocolos UUID não são credenciais. Estados: enviada → em_analise → aguardando_complementacao → em_analise → aprovada/indeferida; cancelamento antes da decisão final. Toda transição exige versão atual e evento transacional imutável. Chave de intenção por autor/espaço e digest canônico protege reenvio; divergência409. Complemento reutiliza protocolo. Recibo de decisão impede efeitos repetidos.

## Cadastro manual sem reescrever a fonte oficial

Decisão: reutilizar as projeções imutáveis e FKs do D com uma terceira fonte explicitamente `manual`, identificada na interface como **Cadastro da plataforma**. Cada revisão gera edição manual completa e atômica, conservando as edições/entradas anteriores. Não alterar, anular FKs ou reatribuir referências pessoais existentes. Observações dessa fonte são produzidas por operação humana auditada, com schema próprio `cadastro-plataforma-v1` e manifesto `origem=operador`, sem alegar arquivo/censo INEP. O campo técnico arquivo_sha256 registra o digest do snapshot manual; não representa download oficial. O custo é copiar o conjunto manual por revisão; rever para preparação em lotes se esse conjunto crescer, antes de comprometer a transação.

Código INEP informado exige conferência explícita do analista, URL pública de evidência e largura/namespace válidos. Identidades determinísticas usam os mesmos namespaces e natureza do importador; unique e FKs mantêm a mesma instituição/unidade quando a fonte oficial chegar. Sem código oficial, namespace `plataforma` e ULID gerado, nunca um INEP fictício. Não fundir por semelhança de nome. Natureza escola/sede administrativa permanece explícita; campi sem fonte identificadora não são inventados.

Revisão manual tem precedência por unidade sobre fonte oficial corrente, mas não se anuncia como dado censitário. A origem é sempre exibida; nova edição oficial não apaga ajuste humano. Histórico pessoal conserva a edição exata escolhida, exige reconfirmação explícita e sinaliza edição diferente. Formulários de manutenção guardam revisão esperada: se outra edição entrou,409 antes de publicar. Geografia é a oficial corrente; município inexistente na nova geografia exige revisão, sem substituir silenciosamente. Gestor mantém unidade autorizada; identidade/código/natureza não podem ser trocados por manutenção.

Edições manuais não carregam protocolo, observação privada, autor ou contato na projeção pública. Motivo, evidência e ator/contexto ficam em eventos próprios imutáveis. Aprovação aponta à unidade/edição resultante, mas não adiciona referência pessoal, contrato, vínculo profissional, escola operacional ou acesso. O professor confere e seleciona depois.

## Interfaces, dados e falhas

Página de solicitações lista somente os protocolos próprios e a ação Solicitar cadastro; não apresenta formulário de cadastro oficial. Formulário mínimo: nome, sigla opcional, tipo, UF/município, código/fonte se conhecidos, site público e observação limitada. Sem anexos, CPF ou dados de alunos. Busca indisponível é503/tentar novamente; vazio pode levar à solicitação. Fila interna separada, com detalhe e ação conforme estado. Gestão vê somente suas unidades formalmente autorizadas e formulário de manutenção, sem protocolos pessoais.

Aviso autenticado ao próprio solicitante é gravado na mesma transação do evento/decisão e apresentado no acompanhamento. Não enviar e-mail nesta entrega: aviso no sistema atende o fluxo sem transmissão externa e sem simular envio. Se e-mail for adicionado depois, exigir outbox/recibo idempotentes e conteúdo mínimo. Sem rotina de expurgo: política específica de retenção de protocolos/evidências continua a definir; não herdar prazo de logs nem excluir dados agora.

Rotas separadas: catalogo/minhas-solicitacoes, catalogo/atendimento e catalogo/gestao. Whitelist de payload, paginação, contexto e revalidação em todas as operações. Ordem de escrita de catálogo: controle interno quando aplicável → fontes em ordem estável → usuário/vínculo/cliente/escopo → protocolo/unidade → publicação/eventos/avisos. Seleção D passa a fixar/compartilhar as fontes em ordem estável e rejeita edição encoberta por revisão manual. Publicador oficial continua com suas próprias fontes; identidade determinística e unique evitam duplicação entre processos, sem remover constraints.

## Verificação e saída

Fixtures acadêmicas não vazias antes/depois; dois proprietários, IDs adivinhados, financeiro/gestores negados à fila, retirada de permissão/revogação, escopo cruzado, versão obsoleta, reenvio/decisão duplicados, falha no evento/aviso revertendo tudo. Cadastro sem código, código conferido coincidindo com importação, revisão manual preservada após fonte nova, histórico pessoal imutável, FKs/triggers e operações concorrentes em MariaDB. Web testa permissões e encaminhamento seguro; Chrome local com API fictícia, desktop e celular. Gate CI1-F nacional/20consultas/recuperação integrada permanece distinto. Atualizar a tabela ao finalizar; não pedir autorização por subdivisões internas.
