# 91 — T5-E1: acompanhamento por contexto

Planejamento revisto antes de implementar. PR dependente de D3; merges, deploy e migrações no servidor continuam pendentes de aprovação.

T5-E é dividido em E1 acompanhamento somente leitura, E2 seleção/prévias/evidências e duas decisões, E3 execução/revogação/retentativa e consulta do acervo. Esta divisão permite validar contexto por aba e minimizar a exposição antes de adicionar ações de transferência. E1 não conclui T5-E nem ativa cópias.

## Escopo E1

- Flag própria de interface, desligada na API e web. Menu somente para vínculo explícito de administrador da rede, diretor escolar ou professor autônomo com capacidade de origem ou destino. A API revalida o contexto e a capacidade do lado escolhido.
- Espaços selecionáveis derivados do vínculo atual: escola/pessoal do vínculo ou escolas válidas da rede e do mesmo cliente. Não receber usuário/vínculo de autorizador no corpo/query. Um ID de espaço só funciona após conferência server-side.
- Lista paginada, 20 por página, filtrada antes da contagem. Origem vê lotes do espaço selecionado. Destino vê apenas lotes cuja decisão do destino já foi registrada; convites para decidir e divulgação prévia são contrato de E2. Isso evita expor ao destino uma seleção unilateral ainda não compartilhada.
- Lista positiva: finalidade, estado, data, total selecionado, quantidade publicada e sinal de recibos pendentes. Sem nomes de alunos/provas/contraparte, IDs dos espaços alheios, autorizações/documentos, hashes, payloads ou erros internos. Progresso não representa anonimização nem autorização.
- A página preserva o contexto por aba, oferece filtros de espaço e direção, estado vazio e atualização manual. Falha da API não é convertida em lista vazia nem expõe a resposta interna. Nenhuma operação de escrita nesta etapa.

## Verificação

API SQLite/MariaDB: isolamento entre escolas e pessoal da mesma identidade, rede/cliente, papel negado, flags, acesso ao lado, contagens/paginação após filtro e minimização. Web: menu, contexto, erro, filtros, HTML escapado e ausência de ações de escrita. Validar visualmente em Chrome local com dados fictícios, sem acessar o servidor. Manter cinco checks e restauração D3.

Tentativa de computer-use em 02/11: ferramenta interrompeu a sessão por não conseguir determinar a URL atual do Chrome com confiança suficiente para aplicar a política. Nenhuma interação posterior nem alternativa de automação foi usada para contornar o bloqueio. Conferência visual permanece pendente; testes automatizados não a substituem.
