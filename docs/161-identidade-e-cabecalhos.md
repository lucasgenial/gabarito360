# H1 — identidade oficial e cabeçalhos de documentos

Planejamento revisto em 05/11/2026 antes da implementação, com consulta prévia a UI/UX e Product Design. O título fica centralizado; a sugestão inicial de alinhamento à esquerda foi corrigida pela instrução explícita do responsável.

| Etapa | Entrega | Estado inicial |
|---|---|---|
| A | Inventário, SVG oficial, fontes e tokens da marca | Em implementação |
| B | Componente reutilizável e aplicação em layouts, páginas públicas, e-mails e carregamento | Em implementação |
| C | Favicons, ícones de aplicação, manifest e compartilhamento | Em implementação |
| D | Cabeçalhos institucionais/autônomos compartilhados, título centralizado, caixas do aluno e nota | Em implementação |
| E | Testes, revisão independente, CI e publicação na homologação | Pendente |

Identidade: símbolo oficial preservado, azul-marinho #001534, azul #0588FC e branco. Wordmark Figtree800, somente o6 em AlbertSans800; manter cores semânticas e contraste dos controles. Fontes locais e arquivos de licença. Animação opcional breve, sem loop padrão, respeitando redução de movimento.

Documentos: escola com rede usa logo da rede à esquerda e escolar à direita, mesmo se uma logo estiver ausente; escola sem rede usa apenas sua logo à esquerda. O modo autônomo prioriza a marca completa Gabarito360 e explicita o uso autônomo. Não apresentar o professor como emissor institucional nem inferir a escola a partir de referências pessoais sem associação com o documento. Dados institucionais existentes determinam o cabeçalho; esta entrega não muda contratação, vínculos, regras de negócio ou banco. A futura associação de turmas pessoais a uma instituição exige etapa própria, pois hoje suas provas/turmas têm escola_id nulo por projeto.

Usar o mesmo resolvedor de identidade para exportações e o partial de impressão compartilhado para relatórios HTML. A variante compacta dos cartões-resposta preserva marcadores, QR, campos e posições da grade óptica. Não regenerar arquivos antigos nem alterar notas/fotos. Novos PDFs do resultado mantêm selo, QR e numeração contínua, com identidade e título separados por respiro, identificação do aluno e nota em caixas próprias.

Preservar os layouts e funcionalidades atuais; inventariar compatibilidades antigas antes de remover arquivos. Nenhuma publicação em produção. Publicar a entrega completa primeiro na homologação, com backup e reversão do código.
