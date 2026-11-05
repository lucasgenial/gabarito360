# OMR3 — leituras, análise pedagógica e documentos verificáveis

Planejamento revisto em 05/11/2026 antes da implementação. Direção prévia consultada com UI/UX e Product Design; aprovação visual final independente ainda necessária.

| Etapa interna | Entrega | Estado inicial |
|---|---|---|
| A | Carregamento regional das fotos, erro e nova tentativa da imagem | Em implementação |
| B | Itens coloridos com leitura anterior, resposta registrada e intervenção humana; seção somente com mais de uma leitura | Em implementação |
| C | Análise por questão para educadores, com turma e conjunto das turmas da prova | Em implementação |
| D | PDF do aluno e PDF pedagógico, impressão do PDF emitido, identificação do emissor | Em implementação |
| E | Registro imutável de emissão e validação pública de código/arquivo | Em implementação |
| F | Testes, PDFs renderizados, conferência desktop/celular, revisão independente e CI | Pendente |
| G | Merge, backup, migração aditiva e publicação somente na homologação | Pendente |

## Decisões e limites

- Preservar cartões, arquivos, respostas, notas e registros imutáveis existentes. Não reler fotos antigas nem recalcular notas para preencher lacunas de auditoria.
- Histórico significa leituras de um cartão na mesma aplicação. Consolidar as revisões preservadas por questão, inclusive quando uma pessoa confirmou a mesma alternativa. Ausência de evidência não prova ausência de intervenção; leitura automática original não preservada será identificada.
- Revisão do planejamento a pedido do usuário: manter o visual anterior de itens coloridos em questão por questão, incluindo a visão docente; acrescentar os percentuais dentro dos itens, sem tabela. No histórico, mostrar apenas leituras e alterações preservadas, sem percentuais. Exibir a seção somente se houver mais de uma leitura. As cores históricas dependem da referência do gabarito congelada no snapshot; registros antigos sem essa referência ficam neutros, sem comparar com o gabarito atual. A nova referência é gravada apenas em snapshots futuros, sem alterar os fingerprints já utilizados por propostas pendentes.
- Estudantes mantêm sua apresentação, sem percentuais agregados no payload ou PDF. Educadores com acesso à prova e ao resultado recebem percentuais, exclusivamente agregados, das turmas daquela prova no mesmo espaço. Essa autorização específica não amplia listas nem acesso individual a alunos de outras turmas.
- Denominador: resultados atuais com nota e cartão lido, uma vez por aluno; ausentes não entram, respostas em branco contam como erro e anuladas não têm percentual. Respeitar gabarito de língua por cartão. Mostrar também o tamanho da amostra. Guardar turma de aplicação quando disponível.
- Relatório do aluno e relatório do professor têm diagramações distintas. Impressão abre o PDF emitido, por padrão pedagógico para educadores. Lotes permanecem um PDF combinado; cada arquivo final tem um registro próprio.
- Identificar emissor autenticado, perfil/contexto, data/hora e código/QR em todas as páginas. Não é assinatura pessoal com certificado: é selo de emissão e integridade do sistema.

## ADR: validação de documentos

O documento precisa ser verificável sem login, sem tornar seus dados públicos. O código aleatório de 128 bits identifica emissão, mas sozinho não demonstra que um arquivo não foi alterado. O PDF contém código e URL confiável de validação; depois de gerado, calcular SHA-256 dos bytes finais. Registrar metadados mínimos, hash do código, hash do arquivo e selo HMAC com chave derivada da chave da aplicação. Não incluir o próprio hash final dentro do PDF, evitando circularidade. Registros são inseridos somente depois da revalidação de acesso e protegidos contra atualização/exclusão. Não manter outra cópia permanente do PDF.

Na página pública, consultar emissão e opcionalmente comparar o hash do arquivo calculado no navegador, enviando somente código/hash. A resposta pública não revela aluno, nota, nome do emissor ou arquivo. Distinguir emissão encontrada, arquivo confirmado e arquivo divergente. Limitar requisições, evitar cache e validar entradas. Preservar chaves anteriores para rotação; perda das chaves impede validar selos antigos e exige recuperação protegida. Documentos anteriores não possuem este selo e não são retroativamente autenticados.

Alternativas: somente QR/código foi rejeitado por não detectar alteração do PDF; certificado pessoal foi descartado porque não foi disponibilizado e não é necessário ao registro de emissão solicitado. A chave do sistema não substitui certificado pessoal.

## Publicação

Somente homologação, após revisão e CI. Migração cria apenas o registro de emissão e seus bloqueios de alteração. Backup protegido e comparação dos dados/arquivos anteriores, ensaio em banco isolado e rollback do código sem apagar registros novos. Produção permanece inalterada. A proteção adicional de acesso da homologação permanece; a funcionalidade pública dispensa login da aplicação, não a proteção de infraestrutura deste ambiente.
