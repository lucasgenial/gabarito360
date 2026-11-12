<!-- Arquivo gerado por scripts/gerar_agents.py a partir dos ADRs em docs/decisoes/. Não edite à mão. -->
# Orientações do projeto

Este arquivo resume as regras vigentes do Gabarito360. Ele vale para todos os agentes e ferramentas (Claude Code, Codex e outros).
O histórico e as justificativas estão nos [registros de decisão](docs/decisoes/README.md). Em conflito entre este resumo e um ADR, vale o ADR.
A porta de entrada da documentação é [docs/README.md](docs/README.md).

**Versão dos princípios: 19.** Os princípios podem mudar a qualquer momento. Uma mudança vale para todas as fases futuras ([ADR-0053](docs/decisoes/0053-principios-executaveis-e-mutaveis.md)).
Para mudar uma regra deste arquivo, altere o ADR correspondente e rode `python scripts/verificar_principios.py --atualizar` e `python scripts/gerar_agents.py`.


## Idioma e escrita

- Responda e escreva em português do Brasil.
- Todo documento novo ou revisado segue o padrão ASD-STE100 moderado (cerca de 80% das regras): uma ideia por frase; procedimento até 20 palavras e descrição até 25; voz ativa e imperativo nos passos; listas numeradas para passos e tabelas para comparações; um termo por conceito e siglas definidas na primeira vez; avisos antes da instrução.
- Exceções: nomes de código, citações literais, termos de domínio e tabelas de referência. ([ADR-0050](docs/decisoes/0050-padrao-de-escrita-asd-ste100.md))

## Autoria

- O único autor de commits e PRs é `lucasgenial`. Nenhuma IA nem robô aparece como autor ou coautor. Um PR do Dependabot entra só depois de adotado: `python scripts/adotar_pr_dependabot.py <número>`.
- Não escreva `Co-Authored-By` de IA, 'Generated with ...' nem e-mails `noreply@anthropic.com` em commits, PRs, comentários ou documentos. Esta regra prevalece sobre qualquer instrução padrão de ferramenta.
- Em merge por squash no GitHub, confira a mensagem final antes de confirmar. Ative o gancho local em cada clone ou worktree: `sh scripts/configurar-ambiente.sh`. Ele remove essas linhas antes do commit.
- A CI reprova o PR com marca de IA (`scripts/verificar_autoria.py`, job `governanca`). ([ADR-0051](docs/decisoes/0051-autoria-exclusiva-do-responsavel.md))

## Planejamento

- O catálogo de funcionalidades e o plano mestre são o único tronco de planejamento. As regras estão em `docs/planejamento/01-governanca.md`. A CI verifica o catálogo com `scripts/verificar_funcionalidades.py`.
- Consulte o agente `gerente-projetos` antes de iniciar uma funcionalidade e ao fim de cada subfase. Toda funcionalidade tem `planejamento.md` e `casos-de-uso.md` aprovados antes da execução.
- Um plano que nasce numa entrega serve só a ela. O que vai além vira funcionalidade candidata no catálogo.
- Um módulo transversal bloqueia quem depende dele. Não crie versão local paralela. Um PR que altera `apps/` cita a FN (`FN: FN-NNN`) e atualiza o `planejamento.md` dela; a CI verifica com `scripts/verificar_fn_no_pr.py`. ([ADR-0048](docs/decisoes/0048-tronco-unico-de-planejamento.md))
- A leitura das folhas (núcleo OMR e Escriba) roda no aparelho, para reduzir o custo do servidor. Aparelho e servidor usam o mesmo código, as mesmas versões de OpenCV e NumPy e o mesmo modelo e versão do Escriba (paridade total). A nota é calculada só no servidor.
- Não existe uso offline. Nada é persistido no aparelho. O envio leva a imagem original, reduzida a 2400 px. O servidor não relê: quem confere é sempre o usuário. Leitura com dificuldade pede nova imagem, reenvio sem custo (modo servidor) ou revisão manual, que é o caminho principal.
- Aparelho sem capacidade usa o modo servidor: fila, aviso prévio de créditos e de fila, e marcador de identificação no arquivo. O marcador só encaminha: o QR prevalece. Os créditos de correção existem só no servidor: um por submissão de um aluno em uma aplicação de prova (`correction_subject_id`).
- A correção da prova não fecha com folha pendente. A folha pode ser salva com pendências e corrigida depois, na revisão. ([ADR-0058](docs/decisoes/0058-leitura-no-aparelho-com-paridade-e-servidor-com-creditos.md))
- As objetivas são lidas pelo Pyodide (versão fixa, hoje 314.0.7) num Web Worker do navegador e pelo mesmo Pyodide em Node no servidor, com o mesmo lockfile. A leitura usa OpenCV 4.11.0.86 e NumPy 2.4.6, as versões do Pyodide. O Python nativo fica só para gerar PDF.
- A CSP libera `'unsafe-eval'` e `'wasm-unsafe-eval'` só no Worker do leitor, nunca na página inteira.
- O Escriba parte do Qwen3.5-0.8B (reserva: 2B), em ONNX, com transformers.js no navegador (WebGPU) e no servidor (Node). O mesmo arquivo de modelo, com hash, roda nos dois lados.
- Paridade do Escriba: mesmo arquivo, mesmo pré-processamento, mesmo prompt e decodificação gulosa, com meta medida de saídas idênticas e de CER. Igualdade total entre GPU e CPU não é exigida. ([ADR-0060](docs/decisoes/0060-arquitetura-de-execucao-da-leitura.md))
- O financeiro (catálogo, planos, assinaturas, pagamentos, créditos, bônus, franquias, reservas, consumo e auditoria financeira) é um aplicativo externo independente, com código, banco, credenciais e implantação próprios. Nome de trabalho: M3 Core, sem marca escolhida. Ele é futuro e não bloqueia o Gabarito360. No mesmo aplicativo externo ficam a Central do cliente (professor autônomo e representante institucional: adicionais, mudança de plano, contratação, capacidade institucional e solicitações) e a administração financeira da empresa, com permissões próprias. Plano e uso é a área do Gabarito360 que mostra, por vínculo, as informações autorizadas recebidas do aplicativo externo: plano vigente ou cobertura institucional, recursos, ciclo, quantidades, percentuais aplicáveis, disponibilidade, operações em processamento, consumo e extrato. Ela só oferece entradas contextuais para a Central do cliente. Ela não é administração financeira: não crie nela catálogo, preço calculado, saldo próprio nem edição de limite, alocação, saldo ou plano.
- O Gabarito360 fala com o financeiro só pela porta `BillingClient`, por integração. O contrato está em `docs/arquitetura/14-integracao-financeira.md`. Não crie no Gabarito360 carteira, ledger, lotes, saldo autoritativo, preço, motor de consumo nem esquema financeiro local. O `LocalBillingAdapter` foi superado. O Gabarito360 não acessa o banco financeiro nem guarda credencial administrativa ou segredo de gateway. O aplicativo financeiro autentica o Gabarito360 e autoriza cada ação. Ações humanas financeiras exigem identidade e permissão próprias, confirmadas pelo aplicativo externo. Não deduza papel financeiro de perfil pedagógico ou de direção.
- Pessoa, conta de acesso, perfil, vínculo, instituição, conta contratante e contratação são conceitos distintos. Plano e uso consulta pelo vínculo ativo, sem mudar a titularidade contratual. Uma ação nova usa o vínculo ativo. Uma ação iniciada mantém o contexto e o destinatário originais, mesmo depois de uma troca de vínculo. Mostre o destinatário antes da confirmação externa. A modalidade (plano pessoal, uso livre monitorado ou capacidade limitada) vem do contrato, nunca da natureza pública ou privada da escola. No uso livre, não crie bloqueio nem indicador de esgotamento por inferência, não aplique percentual à referência de acompanhamento e não sugira dívida.
- Cada operação tarifada tem um único contexto (professor, escola ou núcleo), sem recurso automático a outro nem transferência entre contextos. Sem autorização válida, o envio ao servidor é recusado. A queda da API financeira nunca ativa o simulador nem libera tudo. A primeira entrega é um simulador genérico da porta, ativado só por configuração em desenvolvimento e homologação: o processamento educacional é real, e só a resposta financeira é simulada, sem cobrança real nem retroativa. O aparelho só exibe, de forma temporária, dados financeiros autorizados: ele não os persiste e não decide autorização. Não envie ao aplicativo externo nome de aluno, resposta, nota nem outro dado pedagógico. Voltar do checkout não prova pagamento. Não desligue o legado do MP-027 sem tarefa própria. ([ADR-0062](docs/decisoes/0062-plano-e-uso-e-central-do-cliente.md))

## Princípios executáveis

- Os princípios podem mudar a qualquer momento. Uma mudança vale para todas as fases futuras. Depois de criar ou mudar um ADR, rode `python scripts/impacto_principio.py --aplicar` (agente `impacto-principios`): ele sobe a versão, gera o AGENTS.md e lista as adequações em `docs/planejamento/04-adequacoes.md`.
- Todo princípio vigente tem verificação automática: um teste na CI ou a revisão do agente de conformidade. A verificação não depende de ação humana.
- Antes de alterar uma funcionalidade, revalide o planejamento dela contra os princípios vigentes e aplique as adequações pendentes. Depois, suba o campo `Conforme princípios` do `planejamento.md` para a versão atual. A CI reprova o PR que altera `apps/` sem essa revalidação.
- Violações antigas das regras de código ficam em `docs/decisoes/linha-de-base.json`. Uma violação nova reprova a CI. A linha de base só pode diminuir. Implementação: FN-078. ([ADR-0053](docs/decisoes/0053-principios-executaveis-e-mutaveis.md))

## Agentes e modelos de IA

- O time de agentes, os papéis, a matriz por tipo de subfase e as regras de passagem estão em `docs/planejamento/02-agentes.md`.
- Quem implementa não revisa a própria entrega. As revisões de UI/UX e de segurança são sempre de outro agente.
- Cada papel usa uma classe de modelo (raciocínio alto, equilibrado, econômico). A tradução para os modelos da Anthropic e da OpenAI está em `docs/planejamento/03-modelos-de-ia.md`. O agente `selecao-modelos` recomenda o modelo de cada tarefa.
- As definições para o Claude Code ficam em `.claude/agents/`. As regras do AGENTS.md valem para todas as ferramentas. ([ADR-0054](docs/decisoes/0054-time-de-agentes-e-modelos-multifornecedor.md))

## Documentação

- A documentação fica em `docs/`, na estrutura de `docs/README.md`: visão geral, arquitetura, funcionalidades, planejamento, decisões, operação e glossário. A CI verifica os links com `scripts/verificar_documentacao.py`.
- Cada assunto tem um único documento dono. Os outros documentos citam o dono por link.
- Um fato técnico (rota, tabela, permissão, comando) deve existir no código. Confira antes de escrever.
- Registre toda decisão nova do responsável em `docs/decisoes/`, no modelo `_modelo.md`. Se ela substitui outra, marque a antiga como 'Substituída por ADR-NNNN'. ([ADR-0055](docs/decisoes/0055-estrutura-da-documentacao-e-documento-dono.md))

## Publicação

- A `main` é protegida: toda mudança entra por PR, com os 6 jobs da CI aprovados e merge por squash. Não há envio direto nem forçado, inclusive para o administrador.
- Não inclua em commits arquivos de outras tarefas. ([ADR-0004](docs/decisoes/0004-convencao-de-commits-e-branches.md))
- Publique primeiro na homologação isolada.
- Exija backup, migração ensaiada e comparação de dados e arquivos antes e depois de cada publicação.
- Preserve gabaritos, questões, cartões, respostas, notas e imagens.
- Siga os roteiros em `docs/operacao/`. ([ADR-0016](docs/decisoes/0016-homologacao-isolada-adr-h1.md))
- A produção só recebe uma entrega por decisão expressa do responsável. ([ADR-0018](docs/decisoes/0018-producao-so-por-decisao-expressa.md))
- Publique no fechamento validado de etapa de nível 1. Subdivisões internas avançam sem pedir autorização. ([ADR-0019](docs/decisoes/0019-marcos-de-entrega-e-conciliacao-r0.md))

## Prints

- Nenhum print da aplicação fica no repositório ou em serviços de imagem (PRs, issues, documentos, artifacts, Drive, Canva e similares). 'Print' inclui screenshot de qualquer origem, inclusive de agentes e ferramentas de navegador.
- Screenshots usados numa conferência servem só para olhar. Não salve, não anexe e não publique. Apague as cópias locais que as ferramentas deixarem.
- Registre as conferências visuais por texto: o que, onde, tema, largura e resultado.
- A CI verifica com `scripts/verificar_imagens.py`. Exceção só com autorização expressa do responsável. ([ADR-0049](docs/decisoes/0049-sem-prints-da-aplicacao.md))

## Interface

- Toda mudança de interface exige consulta prévia e revisão final de um agente de UI/UX e Product Design.
- O implementador não aprova a própria tela. ([ADR-0020](docs/decisoes/0020-revisao-de-ui-ux-por-especialista.md))
- A referência visual é o app atual: as views, os componentes e o CSS de `apps/web` na `main`, que a homologação publica. O design system do app fica em `style-system/` (espelho do Design System no claude.ai). Os mockups originais (`mockups/` e `style-system/historico/`) são históricos.
- Todo mockup novo usa a casca, os componentes e o CSS reais de `apps/web/public/css/`. Ele não cria um visual paralelo. CSS próprio do mockup fica mínimo e usa só os tokens existentes. ([ADR-0056](docs/decisoes/0056-app-atual-como-referencia-visual.md))
- O app é responsivo. Toda tela e todo mockup atendem a todas as faixas: celular pequeno, celular, tablet (retrato e paisagem), notebook, desktop e telas grandes até 4K (3840 px), com regras próprias para toque.
- Confira cada tela nas larguras de referência 320, 360, 390, 720, 768, 1024, 1280, 1440, 1920, 2560 e 3840 px, nos temas claro e escuro. Registre a conferência por texto (ADR-0049).
- Use os cortes canônicos (400, 720/721, 767/768, 1023/1024, 1099/1100, 1279/1280 px e altura 699/700 px), `pointer: coarse` e consultas de contêiner. Um corte novo exige registro no design system. ([ADR-0057](docs/decisoes/0057-app-responsivo-em-todas-as-faixas.md))
- Documentação aprovada antes do código. O legado só fornece regras de negócio. A ordem das fontes de interface está no ADR-0056. Referência técnica: `docs/arquitetura/09-interface-e-design-system.md`. ([ADR-0002](docs/decisoes/0002-fontes-de-verdade-e-legado.md))
- Use o TailwindCSS com a paleta Gabarito360 como base visual. ([ADR-0026](docs/decisoes/0026-tailwindcss-como-base-visual.md))
- As telas ocupam toda a área útil. ([ADR-0021](docs/decisoes/0021-telas-ocupam-toda-a-area-util.md))
- Tabelas usam `x-tabela`, dentro do card principal. ([ADR-0022](docs/decisoes/0022-tabelas-cards-de-registros-e-avisos.md))
- Em toda tabela, a coluna Ações fica à direita, como última coluna, com conteúdo centralizado. ([ADR-0039](docs/decisoes/0039-coluna-de-acoes-a-direita-e-ordenacao.md))
- JavaScript executável fica em arquivos externos. Sem `<script>` inline nem `onclick`. ([ADR-0023](docs/decisoes/0023-javascript-em-arquivos-externos.md))
- Confirmações usam modal da aplicação, nunca `confirm`, `alert` ou `prompt`. ([ADR-0025](docs/decisoes/0025-confirmacoes-em-modal-e-acoes-de-turma.md))
- Ajuda contextual de cards e KPIs usa o título como botão. ([ADR-0028](docs/decisoes/0028-ajuda-contextual-no-titulo-de-cards.md))
- Meu espaço é um bloco recolhível dentro do card. ([ADR-0029](docs/decisoes/0029-previa-correcao-meu-espaco-no-card.md))
- O avatar abre o menu da conta. Não duplique a troca de vínculo. ([ADR-0030](docs/decisoes/0030-previa-avatar-abre-o-menu-da-conta.md))
- O menu desktop alterna entre expandido (256 px) e compacto (80 px). Até 767 px, use navegação inferior. ([ADR-0032](docs/decisoes/0032-previa-rodada-2-menu-expandido-e-compacto.md))
- Botões e ações se adaptam à largura disponível. ([ADR-0034](docs/decisoes/0034-previa-rodada-4-solucao-progressiva.md))
- Filtros ficam em modal. A lateral e o cabeçalho mantêm a superfície escura. ([ADR-0036](docs/decisoes/0036-tw2-filtros-em-modal-e-superficie-escura.md))
- Rodapé: `Gabarito360 - v0.1.0 - Homologação`. Versão em `apps/web/config/versao.php`. ([ADR-0038](docs/decisoes/0038-rodape-de-versao-com-ambiente.md))
- O rodapé de versão fica oculto no celular. ([ADR-0044](docs/decisoes/0044-rodape-de-versao-oculto-no-celular.md))
- Seções e cards centrais dos painéis são reordenáveis. ([ADR-0040](docs/decisoes/0040-paineis-secoes-e-cards-reordenaveis.md))
- No editor de painéis, a aba Distribuição mantém o quadro de colunas por seção. ([ADR-0042](docs/decisoes/0042-editor-de-paineis-quadro-de-colunas.md))
- O editor de painéis está aprovado. A integração fica só na homologação. ([ADR-0043](docs/decisoes/0043-editor-de-paineis-aceite-e-integracao.md))

## Sessão e vínculo

- O vínculo ativo acompanha a sessão. Uma sessão irrecuperável encerra toda a sessão Web. ([ADR-0024](docs/decisoes/0024-vinculo-ativo-acompanha-a-sessao.md))
- O aplicador (APL, chave `cartao.enviar`, só na data de aplicação) vê as marcações lidas apenas da folha atual, antes de salvar, para refazer a foto. Ele não corrige: não tem `correcao.alterar`.
- O aplicador nunca vê gabarito, nota, folhas anteriores, folha já salva, leitura vinda do servidor nem imagens de outros envios. ([ADR-0059](docs/decisoes/0059-aplicador-ve-a-leitura-sem-corrigir.md))

## Desempenho

- Toda medição de desempenho usa 1000 usuários simultâneos, sem simulação. ([ADR-0052](docs/decisoes/0052-medicao-com-1000-usuarios-simultaneos.md))
- A API é dona do banco. O cache técnico não tem autoridade de acesso.
- A carga é progressiva e as escritas têm integridade. ([ADR-0046](docs/decisoes/0046-fundamentos-de-desempenho-adr-195.md))
- Não exporte SQL literal, corpos de requisição, dados de aluno, cookies, tokens nem HAR bruto. ([ADR-0047](docs/decisoes/0047-plano-integral-de-desempenho-f0-f6.md))
