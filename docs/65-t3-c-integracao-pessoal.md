# 65 — T3-C: integração do espaço pessoal

01/11/2026. PR #97/T3-B incorporado em `525ae3c`, com autorização para avançar sem deploy. Correção do painel institucional (PR #98) preservada.

Atualização: PR #99 aprovado e incorporado em `3b8aa5b`; CI final `a09e9f1` aprovado na execução 41353580112. T4 iniciado pela base descrita em docs/66, sem deploy. Os registros abaixo preservam os resultados e limites do ensaio anterior ao merge.

## Comportamento

O vínculo `professor_autonomo` válido do proprietário aparece na seleção por aba. O painel usa as consultas isoladas do professor, com turmas/provas do espaço pessoal e seu nome no cabeçalho. Menu móvel prioriza Painel, Corrigir, Provas e Turmas; Alunos permanece no menu completo. Não aparecem gestão de equipe, escolas, coluna escolar ou associação de professores na turma pessoal.

Cartões PDF usam o nome do espaço pessoal, sem escola fictícia, rede ou logotipo institucional. A permissão e o vínculo são revalidados depois da geração antes da entrega. Resultado individual e seu PDF usam o mesmo cabeçalho e preservam a turma da aplicação após movimentação do aluno; a listagem de cartões também mostra essa turma histórica.

## Habilitação e limites

`espacos.pessoal` passa a true **no código desta entrega**, condicionado a espaço/cliente/conta ativos e vínculo pessoal do proprietário. Não basta o perfil global nem um cliente pagador comum. O gate `vinculos.obrigatorio` não muda, nenhuma conta/espaço real é criada e nenhuma configuração foi aplicada no servidor. Provisionamento e contratação continuam T4; portal dos pais, importação escolar e cópia entre espaços não são concedidos.

Uma publicação futura exige o ensaio da release, migrações T3-B, conciliação dos vínculos e aprovação do corte. Não publicar a main apenas para testar esta integração. A correção pontual do PR #98 no servidor precisa ser reconciliada antes do próximo deploy (registro em docs/operacao/incidente-painel-professor-2026-11-01.md).

## Validação

- `IntegracaoPessoalTest`: seleção via tokens reais, painel, isolamento entre dois espaços pessoais e entre pessoal/escolar da mesma conta, suspensão, payload de cartões/resultados PDF, revogação durante geração, job serializado, nota e QR externo recusado. Incluído no CI MariaDB junto às regressões de integridade e vínculos.
- `DadosPessoaisTest`: resultado individual e turma dos cartões após mudança de turma, além do boletim e recálculo existentes.
- `IntegracaoPessoalWebTest` e `DadosPessoaisWebTest`: seleção, painel, menu, ausência de controles e consultas institucionais e preservação da identidade.
- Ensaio local com conta fictícia, SQLite descartável e API/web reais: login, seleção pessoal/escolar, turma e aluno mínimo. Evidências complementares e resultado final do CI registrados no PR.

### Fechamento do ensaio (01/11/2026)

- CI do commit `e82412f`: API, web, telas e integridade MariaDB aprovados ([execução 41349419315](https://github.com/lucasgenial/gabarito360/actions/runs/41349419315)).
- Chrome: login fictício, seleção do espaço pessoal, painel e gravação do gabarito confirmados. O Computer Use travou ao aceitar o diálogo JavaScript de publicação; a publicação foi concluída pela API local. A confirmação visual desse diálogo permanece uma limitação da automação, não uma validação concluída.
- Cartão-resposta e resultado individual gerados pelos processos Python reais e inspecionados visualmente: cabeçalho pessoal, aluno/turma fictícios, QR no cartão e resultado legível.
- Upload HTTP, repetição do envio e worker `queue:work database --queue=alta --once`: recibo concluído, cartão lido, uma resposta e nota persistida. Leitura OMR simulada; nenhuma fotografia real foi certificada.
- O SQLite descartável não tinha a tabela operacional `jobs` (o ambiente publicado usa sua infraestrutura de fila). A primeira tentativa foi preservada com erro recuperável; após preparar somente essa tabela local, a repetição entrou na fila e concluiu. Nenhuma migration ou configuração do servidor foi executada.

PR #99 pronto para revisão do código, com a limitação de automação acima registrada. Merge depende de autorização; a próxima etapa planejada é T4 (cadastro de clientes). Isso não libera publicação das fases acumuladas.

Os testes automatizados de fila usam OMR controlado para verificar autorização e persistência; isso não representa nova certificação da leitura óptica de fotografias. O ensaio local não substitui restauração e corte no ambiente equivalente antes da publicação. T3-C permanece em revisão até aprovação do PR.
