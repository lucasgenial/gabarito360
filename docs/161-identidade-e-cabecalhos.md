# H1 — identidade oficial e cabeçalhos de documentos

Planejamento revisto em 05/11/2026 antes da implementação, com consulta prévia a UI/UX e Product Design. O título fica centralizado; a sugestão inicial de alinhamento à esquerda foi corrigida pela instrução explícita do responsável.

| Etapa | Entrega | Estado nesta revisão |
|---|---|---|
| A | Inventário, SVG oficial, fontes e tokens da marca | Implementada e validada na fonte |
| B | Componente reutilizável e aplicação em layouts, páginas públicas, e-mails e carregamento | Implementada e validada na fonte |
| C | Favicons, ícones de aplicação, manifest e compartilhamento | Implementada e validada na fonte |
| D | Cabeçalhos institucionais/autônomos compartilhados, título centralizado, caixas do aluno e nota | Implementada e validada na fonte |
| P | Apresentação pública de pré-lançamento, narrativa completa, demonstrações e modalidades sem preços | Replanejada em05/11 conforme novo pedido; em implementação |
| E | Testes, revisão independente, CI e publicação na homologação | Base7716868 aprovada; nova apresentação exige revisão/CI atuais antes da publicação |

Identidade: símbolo oficial preservado, azul-marinho #001534, azul #0588FC e branco. Wordmark Figtree800, somente o6 em AlbertSans800; manter cores semânticas e contraste dos controles. Fontes locais e arquivos de licença. Animação opcional breve, sem loop padrão, respeitando redução de movimento.

Documentos: escola com rede usa logo da rede à esquerda e escolar à direita, mesmo se uma logo estiver ausente; escola sem rede usa apenas sua logo à esquerda. O modo autônomo prioriza a marca completa Gabarito360 e explicita o uso autônomo. Não apresentar o professor como emissor institucional nem inferir a escola a partir de referências pessoais sem associação com o documento. Dados institucionais existentes determinam o cabeçalho; esta entrega não muda contratação, vínculos, regras de negócio ou banco. A futura associação de turmas pessoais a uma instituição exige etapa própria, pois hoje suas provas/turmas têm escola_id nulo por projeto.

Usar o mesmo resolvedor de identidade para exportações e o partial de impressão compartilhado para relatórios HTML. A variante compacta dos cartões-resposta preserva marcadores, QR, campos e posições da grade óptica. Não regenerar arquivos antigos nem alterar notas/fotos. Novos PDFs do resultado mantêm selo, QR e numeração contínua, com identidade e título separados por respiro, identificação do aluno e nota em caixas próprias.

Preservar os layouts e funcionalidades atuais; inventariar compatibilidades antigas antes de remover arquivos. Nenhuma publicação em produção. Publicar a entrega completa primeiro na homologação, com backup e reversão do código.

## Complemento: página inicial independente

O responsável pediu a atualização da página inicial estática e confirmou manter a prévia com degradê após considerar a versão clean. A composição e o conteúdo permanecem; somente a abertura recebe o degradê aprovado (#1D4F9E→#001534), texto branco e ações com contraste. Os exemplos continuam em superfícies brancas e as demais seções são preservadas. A marca usa o mesmo componente e fontes.

Decisão posterior explícita de05/11: ampliar a apresentação para o pré-lançamento, preservando a identidade, o degradê e a exportação independente. A instrução de conservar o conteúdo anterior foi substituída. Narrativa completa com recursos atuais comprovados e sete frentes futuras, cada ocorrência identificada pelo selo literal **Em breve**, sem rótulos públicos de planejamento, preços ou compra. Modalidades continuam Professor autônomo, Escolas, Núcleos e redes e Secretarias; não atribuir recursos futuros por plano sem decisão comercial. Esta ampliação não implementa os módulos futuros. Ver doc164 e evidências de marketing/revisão para a entrega final.

O exportador anterior congelava somente app.css. A nova exportação inclui bundle imutável da marca, fontes, favicons, manifestos e imagens públicas sob whitelist, com SHA, MIME correto e preservação das versões anteriores. Home e manutenção devem funcionar sem PHP/API. Não exportar formulários, tokens ou contexto de usuário. Uma falha de exportação preserva o pointer anterior; origem pública explícita evita links para IP interno.

A inspeção da VM120 confirmou que a homologação ainda não tinha a home independente configurada. A instalação será restrita a ela, mantendo BasicAuth, com backup da configuração Nginx, lint, ensaio protegido de indisponibilidade do PHP-FPM e rollback. Não aplicar esta configuração em produção nesta entrega. Suíte Nginx executada em instância isolada, sem alteração do serviço ativo; verificar a configuração efetiva no deploy após CI e revisão final.
