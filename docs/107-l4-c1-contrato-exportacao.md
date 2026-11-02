# 107 — L4-C1: contrato da exportação controlada

Planejamento revisto em 02/11/2026 após autorização para prosseguir. Origem: docs. 102–106 e rascunho técnico de L4-C. Esta entrega define e testa o ciclo do artefato; não cria permissões, endpoints, tabelas, arquivos exportados ou downloads. Publicação somente após fechar L4 completo (A–E).

## Sequência e critérios

| Parte | Entrega | Critério de saída |
|---|---|---|
| C1 | Contrato de escopo, autorização e ciclo do artefato | Estados e limites temporais testados; dependências explícitas |
| C2 | Vinculação explícita do sujeito e autorização delimitada | Sujeito pertencente ao espaço, concessão válida e revogável, sem busca global ou associação por coincidência de nome |
| C3 | Geração privada e idempotente | Campos permitidos versionados, manifesto, cifragem, falha segura, testes com duas instituições |
| C4 | Consulta e download autenticados | Revalidação a cada acesso, expiração, auditoria obrigatória, ausência de links públicos |
| C5 | Interface e ensaio integrado | Confirmação do escopo, conferência visual e ensaio somente com dados fictícios |

Cada parte deve rever este planejamento antes de implementar. C1 não representa exportação disponível para uso.

## Escopo e concessão

Uma exportação pertence a um protocolo, cliente, espaço e sujeito explicitamente vinculados. Identificação declarada não equivale a aluno identificado. A vinculação deverá registrar responsável, evidência protegida e versão; mudar o sujeito exige nova autorização e invalida artefatos anteriores. Revalidar a pertença ao espaço na vinculação, geração e entrega, inclusive transferência de aluno.

Atender um protocolo não concede leitura escolar. Desenvolvedor e Suporte continuam limitados aos protocolos atribuídos; Financeiro não recebe exportação. O serviço futuro deverá exigir concessão específica, verificável e revogável, além das conferências vigentes de identidade/representação e autoridade institucional. O catálogo atual de perfis não será ampliado por C1. C2 deve detalhar quem pode conceder e como comprovar o escopo antes de habilitar qualquer rota de dados.

Na primeira versão planejada, tratar um aluno por solicitação. Outros sujeitos ou múltiplos espaços exigem contrato específico. Não usar CPF em URL, nomes como chave de associação, listagem global, relatório coletivo ou serialização integral do model Aluno.

A concessão deverá fixar protocolo, ciclo das verificações, sujeito e versão, cliente/espaço, destinatário autorizado, seleção de campos, versão do formato, emissor e prazo. O cliente HTTP nunca fornece booleanos de autoridade. Ausência de qualquer requisito bloqueia geração/entrega; verificação documental isolada não concede poder de exportar.

## Artefato e conteúdo

Usar referência opaca e armazenamento privado cifrado. Manifesto mínimo deve registrar versão do esquema, instante da geração, seleção autorizada e integridade do conteúdo. O conjunto de campos será enumerado positivamente em C3; campos novos do banco não entram automaticamente. Dados de colegas, rankings de turma, credenciais, hashes de CPF e caminhos privados ficam fora. Imagens/anexos e exportação coletiva não entram na primeira versão.

Prazo do arquivo é obrigatório e explícito, limitado pela validade da concessão e das verificações. Não se define prazo legal de guarda nesta entrega. O instante de expiração é exclusivo: em agora igual a expira_em, não há entrega. Horários são instantes UTC; a comparação deve respeitar o fuso informado, sem depender do relógio do navegador.

Download exige autenticação, destinatário e autorização atuais, integridade do arquivo e evento obrigatório persistido antes de liberar bytes. Sem URL pública, token permanente ou e-mail automático. Concluir administrativamente o protocolo não prova que o arquivo foi entregue e não autoriza uma nova geração.

## Estados do artefato

| Origem | Destinos permitidos |
|---|---|
| solicitada | gerando, revogada, expirada |
| gerando | disponivel, falhou, revogada, expirada |
| disponivel | revogada, expirada |
| falhou | nenhum |
| revogada | nenhum |
| expirada | nenhum |

Estados terminais não reabrem. Uma tentativa nova após falha recebe nova referência e requer autorização revalidada; repetição idempotente da solicitação original retorna seu recibo, sem criar outro artefato. Retomada técnica de job em gerando mantém a referência e demanda controle de concorrência no serviço, não uma transição gerando→gerando. Idempotência não pode devolver conteúdo após revogação ou expiração.

O enum EstadoExportacaoTitular expressa apenas transições e disponibilidade temporal. Não consulta banco, não autoriza destinatário, não gera arquivos e não exclui dados. Os serviços C2–C4 deverão verificar todos os fatos antes de usá-lo. Revogação/expiração bloqueiam entrega imediatamente pela política de acesso, mesmo antes de um job atualizar a situação persistida. A limpeza física terá rotina própria; jamais apagar dados escolares ou backups como efeito colateral dessa máquina de estados.

## Integração e testes ainda obrigatórios

C2–C4 devem serializar geração/entrega com revogação, mudança do sujeito e do escopo; usar versão esperada, recibo por ator/ação/chave e transação para eventos obrigatórios. Uma gravação incompleta de arquivo não pode aparecer como disponível. Falha de auditoria não libera conteúdo. Nenhum conteúdo pessoal em logs, mensagens de erro ou payload de auditoria geral.

Ensaiar duas instituições, aluno transferido, vínculo revogado, conferência vencida, destinatário diferente, arquivo adulterado, falha de armazenamento/auditoria, expiração durante geração, mesma chave concorrente e payload divergente. Esta parte C1 testa a matriz inteira de transições, terminais, prazo ausente, fronteira exata e equivalência de fusos; testes de integração ficam nas partes que implementarem seus mecanismos.

## Evidência de C1

Cinco testes unitários e 53 asserções aprovados localmente em 02/11/2026. Cobertura da matriz completa de 36 combinações, terminais, fronteira com microssegundos, prazo ausente e fusos equivalentes. Sem acesso ao banco e sem efeitos sobre arquivos ou dados escolares. CI completo deve ser aprovado antes do merge. Não há tela nova para conferência visual nesta parte.
