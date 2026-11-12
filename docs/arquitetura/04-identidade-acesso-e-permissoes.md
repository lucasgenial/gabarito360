# Identidade, acesso e permissões

**Atualizado em:** 12/11/2026, por lucasgenial

Este documento descreve como o Gabarito360 autentica uma conta, escolhe o contexto de acesso e decide o que cada perfil pode fazer. Ele inclui o catálogo oficial de perfis e permissões, o motor de permissões (`App\Support\Permissao`), os acessos internos, a identidade pessoal e a imagem de perfil.

Este documento é o dono do catálogo de perfis e permissões. Para o modelo de clientes, espaços e vínculos, leia [07](07-clientes-espacos-e-vinculos.md). Para as tabelas, leia [03](03-modelo-de-dados.md). Funcionalidades relacionadas: FN-011, FN-012, FN-013, FN-014, FN-015, FN-016, FN-019, FN-022.

> **Regra de mudança.** Toda mudança nas permissões deste catálogo exige aprovação do responsável pelo produto. O código (`App\Support\CatalogoPermissoes`) e este documento devem permanecer iguais.

## 1. Conceitos

| Conceito | Significado |
|---|---|
| Conta (identidade) | Registro em `usuarios`. Tem e-mail, senha e dados pessoais. A conta não pertence a uma escola. |
| Pessoa | Identidade canônica em `pessoas` (seção 10). Ainda não se liga às contas. |
| Espaço | Dono dos dados. Tipos: pessoal, escola, núcleo, rede. Veja o documento 07. |
| Conta de acesso | Mesmo registro que "Conta (identidade)". Este termo distingue a credencial de entrada da pessoa e da contratação. |
| Vínculo | Relação de uma conta com um espaço, com perfil, situação e vigência (`usuario_vinculos`). |
| Instituição | Organização atendida: rede, núcleo ou escola. No Gabarito360, o espaço representa a instituição. |
| Conta contratante | Parte que contrata o serviço. Fica no aplicativo financeiro externo. O Gabarito360 não a guarda. [Diretriz] ([ADR-0062](../decisoes/0062-plano-e-uso-e-central-do-cliente.md)) |
| Contratação | Acordo vigente entre a conta contratante e o produto: plano, cobertura e recursos. Fica no aplicativo financeiro externo. |
| Contexto ativo | O vínculo (ou acesso interno) selecionado na sessão. Só ele define perfil, permissões e escopo. |
| Perfil | Papel do vínculo. Define o escopo e as permissões padrão. |
| Escopo | Até onde o usuário enxerga: secretaria, rede, núcleo, escola ou turmas. A API sempre verifica o escopo. |
| Permissão | Ação com chave estável, por exemplo `prova.criar`. |
| Padrão (v) | Permissão que todo vínculo do perfil tem. |
| Extra (o) | Permissão que o perfil não tem, mas que um gestor pode conceder a uma pessoa. |
| Nunca (—) | Permissão que o perfil não pode receber, nem como extra. |
| Retirada | Remoção de uma permissão padrão de um vínculo específico. |

Pessoa, conta de acesso, perfil, vínculo, instituição, conta contratante e contratação são conceitos distintos. Nenhum deles substitui outro. A área Plano e uso do Gabarito360 consulta o aplicativo financeiro externo pelo vínculo ativo. A contratação não muda de titular quando o vínculo muda. Veja [ADR-0062](../decisoes/0062-plano-e-uso-e-central-do-cliente.md) e o [contrato de integração](14-integracao-financeira.md).

## 2. Autenticação e conta

### 2.1 Login e token

1. A pessoa envia e-mail e senha a `POST /api/v1/auth/login`.
2. A API busca a conta ativa pelo e-mail e confere a senha (hash bcrypt).
3. A API cria um token Sanctum. Quando a conta tem `forcar_troca_senha`, o token é restrito e só abre a troca de senha (`SenhaTrocada`).
4. Com `VINCULOS_OBRIGATORIOS` ligado, o login devolve `perfil` nulo, permissões vazias e `selecionar_vinculo`. A pessoa escolhe o contexto antes de operar.

O token de identidade não expira por tempo (`sanctum.expiration = null`). A decisão do responsável é não impor validade máxima de oito horas ao token da API. A sessão web expira após 120 minutos de inatividade (`SESSION_LIFETIME`).

### 2.2 Rotas de autenticação

| Rota (`/api/v1/auth/...`) | Função | Limite |
|---|---|---|
| `POST login` | Entrar. | — |
| `POST esqueci-senha` | Pedir link de recuperação. | 5/min |
| `POST redefinir-senha` | Definir nova senha com token. O link vale 60 minutos. | 10/min |
| `POST confirmar-email` | Confirmar troca de e-mail. | 10/min |
| `POST convite`, `POST convite/recusar` | Consultar ou recusar convite de primeira conta. | 20/min e 10/min |
| `GET me`, `PUT me` | Ler e atualizar a própria conta. Senha nova exige 8 caracteres e confirmação. | — |
| `POST email` | Pedir troca de e-mail (confirmada por link). | 5/min |
| `POST logout` | Encerrar o token. | — |
| `GET vinculos`, `POST vinculos/selecionar` | Listar e escolher vínculo (seção 4). | 30/min e 20/min |
| `GET acessos-internos`, `POST acessos-internos/selecionar` | Listar e escolher acesso interno (seção 8). | 30/min e 20/min |
| `POST convites-vinculo/{id}/consultar`, `.../responder` | Consultar e responder convite de novo vínculo. | 20/min e 10/min |
| `POST imagem`, `GET foto` | Imagem de perfil (seção 11). | `imagem-perfil` |

### 2.3 Convite de primeira conta e troca de e-mail

- O gestor cadastra uma conta nova. O sistema cria um vínculo `convidado` e envia um convite de primeira senha.
- O token do convite fica em `password_reset_tokens`, com `vinculo_id` e `expira_em`. O uso é único.
- Aceitar o convite ativa só aquele vínculo. Recusar encerra só aquele vínculo. Ambos conservam a identidade global.
- Somente uma conta sem acesso anterior e com um único vínculo convidado usa esse fluxo.
- A troca de e-mail guarda o endereço novo em `trocas_email` até a confirmação por link.

### 2.4 Tabelas da conta

| Tabela | Conteúdo |
|---|---|
| `usuarios` | Conta: perfil legado, nome, e-mail único, CPF e contatos protegidos, senha, `ativo`, `forcar_troca_senha`, `ultimo_acesso`, dados profissionais, `avatar_id`, `foto_path` e `imagem_versao`. |
| `usuario_escopos` | Escopo legado (secretaria, rede, núcleo, escola, turma, aluno). Permanece intacta e serve só à compatibilidade. O vínculo é a fonte de acesso atual. |
| `personal_access_tokens` | Tokens Sanctum de identidade, de contexto e internos. |
| `password_reset_tokens` | Recuperação de senha e convite de primeira conta. |
| `trocas_email` | Troca de e-mail pendente. |
| `permissoes`, `perfil_permissoes` | Catálogo e padrão por perfil. Semeados por `g360:permissoes-sincronizar` a partir de `CatalogoPermissoes`. |
| `usuario_permissoes`, `usuario_permissoes_log` | Extras e retiradas no regime legado, com histórico. Ficam preservadas após a conversão para vínculos. |

## 3. Perfis

### 3.1 Perfis institucionais e pessoal

| Perfil | Chave | Espaço | Faz (resumo) | Não faz |
|---|---|---|---|---|
| Secretário de Educação | `secretario_educacao` | Secretaria | Relatórios consolidados das redes da secretaria. Somente leitura. | Operar escola, prova, correção ou usuários. |
| Administrador da Rede | `admin_rede` | Rede | Tudo dentro da rede. | Ver outras redes. A titularidade da assinatura é separada (legado: a conta de acesso é a titular; veja a seção 12). |
| Diretor de Núcleo | `dir_nucleo` | Núcleo | Cadastra, edita e desativa escolas e anexos do núcleo. Gere diretores e coordenadores. Vê relatórios do núcleo. | Criar prova, corrigir, cadastrar professor ou aluno. |
| Diretor Escolar | `dir_escolar` | Escola | Edita dados e logo da escola. Gere a equipe, as turmas e os alunos. Acompanha e reabre correções. | Cadastrar escola, criar ou editar prova e gabarito (só como extra). |
| Vice-diretor Escolar | `vice_dir_escolar` | Escola | Acompanha toda a escola. Gestão só por extra concedido. | Conceder permissões. Herdar poderes da direção. |
| Coordenador | `coordenador` | Escola | Provas, gabaritos, cartões, correção (inclusive fechada), turmas, alunos, vínculo professor e turma, disciplinas. | Cadastrar escola, gerir diretor. |
| Professor | `professor` | Escola | Provas, cartões e correção das suas turmas. Fecha correção. | Alterar correção fechada, ver outras turmas, gerir usuários. |
| Aplicador | `aplicador` | Escola | Fotografar e enviar cartões das provas do dia. | Ver nota, gabarito ou relatório. Só provas com data de aplicação igual a hoje. |
| Aluno | `aluno` | Escola | Ver os próprios resultados. | Ver dados de outro aluno. Depende de `vinculo_alunos`. |
| Secretário Escolar | `secretario_escolar` | Escola | Administra turmas e alunos. | Notas, provas, relatórios, equipe, extras. |
| Auxiliar de Secretaria | `auxiliar_secretaria` | Escola | Cadastra e atualiza alunos. Consulta escola e turmas. | Desativar aluno, gerir turma, importar, ver desempenho, extras. |
| Professor Autônomo | `professor_autonomo` | Pessoal | Gere turmas, alunos mínimos, provas, correção e relatórios próprios. | Gestão de equipe, escolas, núcleo, rede, portal dos pais. Sem extras. |

Rótulo de interface proposto para o autônomo: "Professor independente". A chave técnica não muda.

### 3.2 Perfis internos da plataforma

| Perfil | Chave | Faz | Não faz |
|---|---|---|---|
| Desenvolvedor | `desenvolvedor` | Consulta e gere clientes. Gere Suporte e Financeiro. Gere exceções funcionais. Atende pedidos de titulares. | Dados pedagógicos neste contexto. Gerir outro Desenvolvedor ou a si mesmo. |
| Suporte | `suporte` | Consulta e gere clientes. Gere exceções funcionais. Atende protocolos atribuídos. | Dados pedagógicos neste contexto. |
| Financeiro | `financeiro` | Consulta e gere clientes. | Dados pedagógicos. |

O perfil Financeiro não tem tela em Plano e uso. A administração financeira da empresa fica no aplicativo financeiro externo. O papel futuro deste perfil é **[Pendente]** (FN-068, seção 8). Esta nota não altera permissões.

### 3.3 Compatibilidade entre perfil e tipo de espaço

O código define a compatibilidade em `App\Support\PerfisEspaco::POR_TIPO`.

| Tipo de espaço | Perfis permitidos no vínculo |
|---|---|
| Pessoal | `professor_autonomo` (só se o proprietário do espaço for a própria conta) |
| Escola | `dir_escolar`, `vice_dir_escolar`, `auxiliar_secretaria`, `secretario_escolar`, `coordenador`, `professor`, `aplicador`, `aluno` |
| Núcleo | `dir_nucleo` |
| Rede | `admin_rede` |

Regras:

- Não existe professor de núcleo com acesso pedagógico implícito.
- `secretario_educacao` não consta nessa tabela. Esse perfil existe no catálogo, mas não tem espaço contextual habilitado.
- O portal dos pais, quando existir, só admite espaço de escola (`PerfisEspaco::admitePortalPais`).
- Compatibilidade de perfil não significa que todos os consumidores do perfil estejam concluídos.

### 3.4 Quem cadastra e gere quem

A tabela abaixo vem de `App\Support\Cadastro::PODE_GERIR`. Ela vale para cadastrar, editar, desativar e conceder ou retirar permissões, sempre dentro do escopo do gestor.

| Gestor | Pode cadastrar e gerir | Onde |
|---|---|---|
| Administrador da Rede | admin da rede, diretor de núcleo, diretor escolar, vice-diretor, auxiliar de secretaria, secretário escolar, coordenador, professor, aplicador, aluno | Toda a rede |
| Diretor de Núcleo | diretor escolar, vice-diretor, auxiliar de secretaria, secretário escolar, coordenador | Escolas do núcleo |
| Diretor Escolar | vice-diretor, auxiliar de secretaria, secretário escolar, coordenador, professor, aplicador, aluno | Sua escola |
| Vice-diretor | secretário escolar, auxiliar de secretaria, coordenador, professor, aplicador, aluno. Só com `usuario.gerir` concedida. | Sua escola |
| Coordenador | professor, aplicador, aluno | Sua escola |
| Professor, Aplicador, Aluno, Secretário Escolar, Auxiliar | Ninguém | — |

O Secretário de Educação é cadastrado pela equipe do Gabarito360. Ninguém gere o próprio vínculo, pares ou superiores.

## 4. Vínculos e contexto ativo

### 4.1 Regime de vínculos

- A chave `VINCULOS_OBRIGATORIOS` (`config/vinculos.php`) liga o regime obrigatório. O padrão é `false`. Em produção, a conversão de seis contas em sete vínculos e a ativação da flag ocorreram em 02/11/2026 (FN-015).
- No regime obrigatório, `Permissao`, `Escopo`, `Instituicoes` e as consultas recusam um ator sem contexto. Desligar `espacos.ativo` não devolve acesso legado.
- Links assinados sem vínculo são recusados. Jobs da fila de cartões (B6) sem contexto não executam.
- Os importadores de alunos, provas e Censo aceitam `--vinculo`. No regime obrigatório, a falta dele é recusada.

### 4.2 Seleção do vínculo

1. A pessoa chama `GET /api/v1/auth/vinculos`. A API lista só vínculos válidos da própria conta.
2. A pessoa chama `POST /api/v1/auth/vinculos/selecionar` com `vinculo_id`.
3. A API emite um novo token com o nome `vinculo-contexto`. As habilidades `vinculo:ID` e `identidade:ID_DO_TOKEN_PAI` são gravadas só pelo servidor.
4. A cada requisição, o middleware `contexto_vinculo` reconstrói o contexto a partir do token persistido. Um ID enviado em header, query ou payload nunca seleciona o contexto.
5. O middleware recusa token com metadados incompletos, pai ausente ou expirado, conta divergente, vínculo revogado ou vencido e instituição suspensa.

`Usuario::noVinculo(id)` devolve outra instância da mesma conta com o perfil do vínculo. Ela não altera `usuarios.perfil`. Serializar essa instância em jobs é recusado. Os jobs transportam IDs explícitos e reconstroem o contexto validado.

`ContextoVinculo` revalida a cada uso:

- titularidade da conta e conta ativa;
- vínculo ativo, com início inclusivo e fim exclusivo;
- compatibilidade entre perfil e tipo de espaço (seção 3.3);
- cliente ativo e referências institucionais coerentes;
- hierarquia ativa (espaço, núcleo e rede).

`ContextoCorrecao::versaoAcesso` distingue vínculo, versão, alcance, restrições e permissões efetivas. Links assinados carregam o vínculo e o revalidam na abertura. Aumentar a versão ou suspender o vínculo invalida o link.

### 4.3 Contexto na web

A decisão UR4 (FN-018, 06/11/2026) fixa o seguinte:

1. O vínculo não expira separadamente da sessão. Escolher o vínculo faz parte do login ou de uma troca explícita.
2. O contexto ativo fica criptografado na sessão web. Ele é ligado à identidade e ao ID da sessão.
3. O navegador nunca recebe o token da API.
4. As rotas autenticadas usam o prefixo `/app`. O endereço não escolhe vínculo. Parâmetros `_ctx` e cabeçalhos de contexto antigos são ignorados ou recusados.
5. Um contexto perdido ou inválido encerra a sessão web inteira. O sistema não repete a operação.
6. Uma aba antiga recebe `409` depois de uma troca legítima. Ela não executa ações com o perfil novo.
7. A cada requisição, o middleware `ContextoPorAba` consulta `/auth/me`. Revogação ou resposta `401`/`403` encerra a sessão. Falha transitória da API devolve `503` e preserva o contexto.
8. O topo da tela mostra instituição e papel e permite trocar o contexto.

### 4.4 Gestão por vínculo

- Cada linha da equipe é um vínculo, identificado pelo seu ID. Uma pessoa com dois papéis aparece em duas linhas.
- A gestão recebe o `vinculo_id` do alvo. O ator vem do contexto autenticado.
- Suspender ou reativar muda só `usuario_vinculos.situacao`, aumenta a versão e grava evento. Não altera `usuarios.ativo`.
- Vínculo encerrado, convidado ou vencido não reativa por botão.
- A edição contextual recusa os campos da identidade global. Mudar papel ou espaço exige outro vínculo, com o histórico preservado.
- Alvo com mais de um vínculo visível exige `vinculo_id`. A API retorna `404` para vínculo fora do alcance.
- O gestor contextual não redefine a senha global de outra pessoa.
- Lotes de permissões são transacionais.

### 4.5 Convite de novo vínculo para conta existente

1. O gestor chama `GET /api/v1/usuarios/convites-vinculo/opcoes` (permissão `usuario.gerir`). A API lista só instituições, funções e turmas atribuíveis.
2. O gestor chama `POST /api/v1/usuarios/convites-vinculo` com e-mail, espaço, perfil e turmas opcionais.
3. A resposta é genérica para conta ausente, inativa, autoatribuição ou papel já existente. Ela não devolve token nem dados da pessoa.
4. O convite vale 7 dias. O token é guardado como hash SHA-256.
5. A pessoa autenticada abre `/app/vinculos/convite`. O ID e o token vêm no fragmento da URL. O navegador remove o fragmento do histórico e guarda os dados por até 20 minutos no `sessionStorage`.
6. A pessoa aceita ou recusa. O aceite revalida os poderes atuais do emissor. A recusa encerra só o vínculo convidado.
7. Um novo convite do mesmo vínculo invalida o anterior. Ele não reabre vínculo ativo, suspenso ou encerrado.

O e-mail do convite não pede nova senha. Aceitar não muda senha, perfil global nem outros vínculos. Início futuro com aceite antecipado e o fluxo "CPF-primeiro" estão no escopo da FN-017 (pendente).

### 4.6 Grupos funcionais

Decisão do responsável de 03/11/2026:

- Funções administrativas (Secretário Escolar, Auxiliar de Secretaria) não se acumulam com função pedagógica (Professor, Coordenador, Diretor, Vice-diretor, Aplicador) na mesma instituição.
- Encerrar um vínculo libera a contratação de outro grupo. O histórico fica preservado, sem manter o acesso antigo.
- Escolas diferentes são independentes.
- Uma exceção exige referência de chamado e liberação individual por usuário e instituição. Só Suporte ou Desenvolvedor concede. A exceção grava motivo, versão e evento imutável (`usuario_funcoes_excecoes`, `usuario_funcoes_eventos`).
- A exceção não cria vínculo nem une permissões.

A classe `GruposFuncionais` aplica a regra ao criar ou convidar vínculo. A rota `POST /api/v1/configuracoes/funcoes` registra a exceção.

## 5. Motor de permissões

### 5.1 Fórmula

`App\Support\Permissao::efetivas()` calcula as permissões na seguinte ordem:

1. Com contexto interno presente, usa as permissões internas (seção 8).
2. Com vínculo presente, `ContextoVinculo::permissoes()` usa o padrão do perfil do vínculo, mais as concessões válidas do vínculo, menos as retiradas do vínculo.
3. Sem contexto (só no regime legado), usa o padrão do perfil da conta, mais os extras de `usuario_permissoes`, menos as retiradas. Cache por requisição.

Perfis com política independente (`professor_autonomo`, `secretario_escolar`, `vice_dir_escolar`, `auxiliar_secretaria`) exigem vínculo explícito. No legado, eles não recebem permissão alguma.

Concessões vencidas (`valida_ate`) não valem. Uma concessão fora das permissões "extra" do perfil não cria poder. O motor não reutiliza o cache legado por pessoa em contexto explícito.

### 5.2 Escopo

- `App\Support\Escopo` é a fonte única das escolas, turmas e provas visíveis. Ele filtra antes de contagens, médias e listas.
- Permissão nunca amplia o escopo. Um professor com `relatorio.escola` vê o relatório da própria escola.
- A API aplica o escopo em toda consulta e alteração. A tela só esconde botões.
- O espaço é calculado no servidor. Receber `espaco_id` do cliente não é autorização. Um ID fora do escopo responde como inexistente.
- Professor mantém a restrição de turmas (`vinculo_turmas`). Aluno só vê os registros de `vinculo_alunos`.
- A hierarquia superior (núcleo, rede) vê os descendentes do seu espaço. Isso não transfere propriedade.

### 5.3 Regras de concessão

Valem para extras e retiradas (`Permissao::podeDefinir` e `GestaoVinculos`):

1. O gestor precisa ter `usuario.permissoes`.
2. Só concede quem também tem a permissão (padrão ou extra).
3. Só concede dentro do próprio escopo.
4. Só concede a quem pode gerir (seção 3.4).
5. Só concede como extra o que o perfil do alvo admite como extra. Só retira o que o perfil tem como padrão.
6. Toda concessão ou retirada é registrada: quem deu, para quem, o quê, escopo, quando e validade opcional.
7. O Vice-diretor nunca recebe `usuario.permissoes`.

### 5.4 Middlewares da API

| Alias | Função |
|---|---|
| `auth:sanctum` | Autentica o token. |
| `contexto_vinculo` | Resolve o contexto do token (vínculo ou acesso interno). |
| `senha_trocada` | Bloqueia tudo, exceto a troca de senha, quando `forcar_troca_senha` está ativo. |
| `permissao:chave` | Exige a permissão efetiva. |
| `perfil:chave` | Exige o perfil (usado em dashboards). |
| `assinatura_ativa` | Confere assinatura ativa. |

## 6. Catálogo de permissões

Colunas: **SEC** secretário de educação, **ADM** administrador da rede, **NUC** diretor de núcleo, **DIR** diretor escolar, **COO** coordenador, **PRO** professor, **APL** aplicador, **ALU** aluno. Símbolos: ✓ padrão, ○ extra concedível, — nunca.

Os perfis `professor_autonomo`, `secretario_escolar`, `vice_dir_escolar` e `auxiliar_secretaria` têm política independente (seção 7).

### 6.1 Rede, núcleos e escolas

| Chave | Ação | SEC | ADM | NUC | DIR | COO | PRO | APL | ALU |
|---|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| `rede.configurar` | Parâmetros da rede (metas) | — | ✓ | — | — | — | — | — | — |
| `rede.logo` | Enviar logo do ente | — | ✓ | ✓ | ○ | — | — | — | — |
| `nucleo.ver` | Ver núcleos | ✓ | ✓ | ✓ | — | — | — | — | — |
| `nucleo.gerir` | Criar, editar, excluir núcleo | — | ✓ | ○¹ | — | — | — | — | — |
| `escola.ver` | Ver dados da escola | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| `escola.criar` | Cadastrar escola e anexos | — | ✓ | ✓ | — | — | — | — | — |
| `escola.editar` | Editar dados da escola | — | ✓ | ✓ | ✓ | ○ | — | — | — |
| `escola.desativar` | Ativar ou desativar escola | — | ✓ | ✓ | — | — | — | — | — |
| `escola.logo` | Enviar logo da escola | — | ✓ | ✓ | ✓ | ✓ | — | — | — |

¹ O diretor de núcleo só edita o próprio núcleo.

### 6.2 Equipe e usuários

| Chave | Ação | SEC | ADM | NUC | DIR | COO | PRO | APL | ALU |
|---|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| `usuario.ver` | Ver a equipe do escopo | — | ✓ | ✓ | ✓ | ✓ | ○ | — | — |
| `usuario.gerir` | Cadastrar, editar, desativar (seção 3.4) | — | ✓ | ✓ | ✓ | ✓ | — | — | — |
| `usuario.permissoes` | Conceder e retirar permissões (seção 5.3) | — | ✓ | ✓ | ✓ | ○ | — | — | — |

### 6.3 Turmas, alunos e disciplinas

| Chave | Ação | SEC | ADM | NUC | DIR | COO | PRO | APL | ALU |
|---|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| `turma.ver` | Ver turmas (professor: as suas) | — | ✓ | ✓ | ✓ | ✓ | ✓ | — | — |
| `turma.gerir` | Criar e editar turma | — | ✓ | — | ✓ | ✓ | ○ | — | — |
| `turma.desativar` | Ativar ou desativar turma | — | ✓ | — | ✓ | ○ | — | — | — |
| `turma.vincular_professor` | Vincular e desvincular professor | — | ✓ | — | ✓ | ✓ | — | — | — |
| `aluno.ver` | Ver alunos (professor: das suas turmas) | — | ✓ | ✓ | ✓ | ✓ | ✓ | — | ○² |
| `aluno.gerir` | Cadastrar e editar aluno | — | ✓ | — | ✓ | ✓ | ○ | — | — |
| `aluno.importar` | Importar alunos por planilha³ | — | ✓ | — | ✓ | ✓ | ○ | — | — |
| `aluno.desativar` | Ativar ou desativar aluno | — | ✓ | — | ✓ | ✓ | — | — | — |
| `disciplina.gerir` | Organizar disciplinas e atribuições docentes da escola | — | — | — | — | ✓ | — | — | — |

² Aluno: somente o próprio cadastro.
³ O importador XLSX ainda não existe (FN-034 pendente). A permissão está preparada.

### 6.4 Provas e gabaritos

| Chave | Ação | SEC | ADM | NUC | DIR | COO | PRO | APL | ALU |
|---|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| `prova.ver` | Ver provas (professor: das suas turmas; aplicador: só a identificação das do dia) | — | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| `prova.criar` | Criar prova | — | ✓ | — | ○ | ✓ | ✓ | — | — |
| `prova.editar` | Editar, publicar e excluir rascunho | — | ✓ | — | ○ | ✓ | ✓ | — | — |
| `prova.duplicar_propria` | Copiar a própria prova como rascunho independente | — | — | — | — | — | ✓ | — | — |
| `gabarito.ver` | Ver gabarito | — | ✓ | ✓ | ✓ | ✓ | ✓ | — | — |
| `gabarito.editar` | Editar e publicar gabarito | — | ✓ | — | ○ | ✓ | ✓ | — | — |

### 6.5 Cartões e correção

| Chave | Ação | SEC | ADM | NUC | DIR | COO | PRO | APL | ALU |
|---|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| `cartao.imprimir` | Gerar PDF dos cartões-resposta | — | ✓ | — | ✓ | ✓ | ✓ | ○ | — |
| `cartao.enviar` | Fotografar e enviar cartões | — | ✓ | — | ✓ | ✓ | ✓ | ✓ | — |
| `correcao.acompanhar` | Acompanhar correção (prova e turma) | — | ✓ | ✓ | ✓ | ✓ | ✓ | — | — |
| `correcao.alterar` | Resolver ambíguos, vincular aluno, revisar, excluir (correção aberta) | — | ✓ | — | ✓ | ✓ | ✓ | — | — |
| `correcao.fechar` | Fechar a correção | — | ✓ | — | ✓ | ✓ | ✓ | — | — |
| `correcao.reabrir` | Reabrir a correção e alterar com ela fechada | — | ✓ | — | ✓ | ✓ | ○ | — | — |

Para o aplicador, `cartao.enviar` mostra as marcações lidas só da folha atual, antes de salvar, para refazer a foto. O aplicador não corrige e não vê gabarito, nota, folhas anteriores, folha salva, leitura do servidor nem imagens de outros envios ([ADR-0059](../decisoes/0059-aplicador-ve-a-leitura-sem-corrigir.md)).

### 6.6 Resultados e relatórios

| Chave | Ação | SEC | ADM | NUC | DIR | COO | PRO | APL | ALU |
|---|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| `resultado.ver` | Acertos e erros do aluno (aluno: só o próprio) | — | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ |
| `resultado.compartilhar` | PDF e WhatsApp do resultado e boletim | — | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ |
| `relatorio.prova` | Relatório da prova e da turma na prova | — | ✓ | ✓ | ✓ | ✓ | ✓ | — | — |
| `relatorio.turma` | Relatório consolidado da turma e boletim | — | ✓ | ✓ | ✓ | ✓ | ✓ | — | — |
| `relatorio.escola` | Relatório da escola | — | ✓ | ✓ | ✓ | ✓ | ○ | — | — |
| `relatorio.nucleo` | Relatório do núcleo | — | ✓ | ✓ | — | — | — | — | — |
| `relatorio.rede` | Relatório da rede | ✓⁴ | ✓ | — | — | — | — | — | — |
| `relatorio.secretaria` | Consolidado da secretaria | ✓ | — | — | — | — | — | — | — |

⁴ O secretário vê as redes da sua secretaria.

### 6.7 Catálogo público, cópias e titulares

| Chave | Ação | SEC | ADM | NUC | DIR | COO | PRO | APL | ALU |
|---|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| `catalogo.manter_escopo` | Manter unidades do catálogo com autoridade institucional comprovada | — | ✓ | ✓ | ✓ | — | — | — | — |
| `catalogo.consultar` | Consultar referências públicas institucionais | — | — | — | — | — | — | — | — |
| `catalogo.referencia_pessoal.gerir` | Organizar instituições declaradas no espaço pessoal | — | — | — | — | — | — | — | — |
| `catalogo.solicitar` | Solicitar cadastro no espaço pessoal | — | — | — | — | — | — | — | — |
| `pedido.exportacao.autorizar` | Autorizar exportação delimitada do titular | — | ✓ | — | — | — | — | — | — |
| `copia.autorizar_origem` | Autorizar origem de cópia entre espaços | — | ✓ | — | ✓ | — | — | — | — |
| `copia.autorizar_destino` | Autorizar destino de cópia entre espaços | — | ✓ | — | ✓ | — | — | — | — |

As três chaves `catalogo.*` sem padrão são políticas do professor autônomo (seção 7). O recurso de cópias entre espaços permanece desligado (FN-024 substituída).

## 7. Perfis com política independente

Estes perfis não entram na matriz de oito colunas. Cada um tem lista própria em `CatalogoPermissoes`. Eles exigem vínculo aceito, ativo e vigente. O campo global da conta nunca os autoriza.

### 7.1 Professor autônomo (T3, FN-022)

Padrões, sem extras:

| Grupo | Chaves |
|---|---|
| Turmas e alunos | `turma.ver`, `turma.gerir`, `turma.desativar`, `aluno.ver`, `aluno.gerir`, `aluno.desativar` |
| Provas | `prova.ver`, `prova.criar`, `prova.editar`, `prova.duplicar_propria`, `gabarito.ver`, `gabarito.editar` |
| Cartões e correção | `cartao.imprimir`, `cartao.enviar`, `correcao.acompanhar`, `correcao.alterar`, `correcao.fechar` |
| Resultados | `resultado.ver`, `resultado.compartilhar`, `relatorio.prova`, `relatorio.turma` |
| Catálogo | `catalogo.consultar`, `catalogo.referencia_pessoal.gerir`, `catalogo.solicitar` |
| Exportação e cópia | `pedido.exportacao.autorizar`, `copia.autorizar_origem`, `copia.autorizar_destino` |

Regras:

- O perfil pertence só ao espaço pessoal cuja proprietária é a própria conta.
- O cadastro de aluno é mínimo: nome, identificação simples, turma, avatar e estado. O banco recusa CPF, nascimento, gênero e responsável. O retrato privado é permitido.
- Não recebe `escola.criar`, importação escolar, reabertura de correção nem poderes de diretor.
- Selecionar uma instituição no catálogo cria uma referência de atuação. Não cria acesso escolar.
- `pedido.exportacao.autorizar` é padrão só do Administrador da Rede e do autônomo proprietário. Exige contexto explícito e escopo vigente. Suporte, Desenvolvedor, Financeiro e Diretor Escolar não aprovam exportações. A operação permanece atrás de flag.
- As chaves de catálogo exigem contexto pessoal vigente do proprietário e respeitam retiradas. O autônomo não conquista poder de manutenção cadastral.
- `espacos.pessoal` está `true` no código. O acesso exige espaço, cliente e conta ativos e vínculo pessoal do proprietário.

### 7.2 Secretário Escolar (A1-C, FN-014)

| Padrão | Extras |
|---|---|
| `escola.ver`, `turma.ver`, `turma.gerir`, `turma.desativar`, `aluno.ver`, `aluno.gerir`, `aluno.importar`, `aluno.desativar` | Nenhum |

- Vínculo exclusivo de escola. Gestão de alunos e turmas, sem dados de desempenho.
- As respostas administrativas não contêm notas, médias, provas, correção nem relatórios.
- Administrador da rede, diretor de núcleo e diretor escolar gerem esse vínculo. O coordenador não gere.
- `aluno.importar` está preparada. O importador XLSX não existe.
- O código de acesso do responsável depende do portal (FN-067).

### 7.3 Vice-diretor Escolar (PF1, FN-014)

Matriz aprovada pelo responsável em 03/11/2026.

| Padrão (11) | Extras concedíveis (18) |
|---|---|
| `escola.ver`, `turma.ver`, `aluno.ver`, `usuario.ver`, `prova.ver`, `gabarito.ver`, `correcao.acompanhar`, `resultado.ver`, `relatorio.prova`, `relatorio.turma`, `relatorio.escola` | `escola.editar`, `escola.logo`, `turma.gerir`, `turma.desativar`, `turma.vincular_professor`, `aluno.gerir`, `aluno.importar`, `aluno.desativar`, `usuario.gerir`, `prova.criar`, `prova.editar`, `gabarito.editar`, `cartao.imprimir`, `cartao.enviar`, `correcao.alterar`, `correcao.fechar`, `correcao.reabrir`, `resultado.compartilhar` |

Regras:

- Alcance exclusivo da escola do vínculo. Sem herança automática da direção.
- O Vice-diretor nunca recebe `usuario.permissoes`, `prova.duplicar_propria`, `pedido.exportacao.autorizar`, `copia.*`, `escola.criar`, `escola.desativar`, `nucleo.*`, `rede.*`, `catalogo.*` nem relatórios de núcleo, rede ou secretaria.
- Com `usuario.gerir` concedida, gere os perfis da seção 3.4. Não há autogestão nem gestão de pares ou superiores.
- Se criar prova, grava `perfil_criacao = vice_dir_escolar`. A prova é institucional.
- Pode lecionar por um vínculo Professor separado na mesma escola, sujeito à regra de grupos funcionais (seção 4.6).

### 7.4 Auxiliar de Secretaria (PF1, FN-014)

| Padrão | Extras |
|---|---|
| `escola.ver`, `turma.ver`, `aluno.ver`, `aluno.gerir` | Nenhum |

- Não desativa aluno, não gere turma, não importa em massa, não vê desempenho e não administra contas.
- O campo `ativo` do aluno, no cadastro e na edição, exige `aluno.desativar`. `aluno.gerir` não contorna essa regra.
- Usa projeções administrativas sem notas, médias, provas, equipe ou relatórios.

### 7.5 Disciplinas (DC1) e consulta global de identidade

- `disciplina.gerir` é padrão só do Coordenador em vínculo escolar aceito e vigente. Atribuir ou encerrar docente em turma exige também `turma.vincular_professor`. Direção e Vice-direção leem a organização escolar com `turma.ver`. O Professor lê apenas suas atribuições nas turmas já autorizadas. A flag `DISCIPLINAS_ATIVAS` está desligada por padrão.
- Capacidades novas de consulta global de identidade continuam propostas, sem ativação (seção 10).

## 8. Decisões de autoria e cópia de provas

Estas decisões do responsável estão vigentes.

| Data | Decisão |
|---|---|
| 03/11/2026 (autoria, doc 132) | O professor vê as próprias provas e as provas institucionais criadas pela coordenação ou direção apenas nas turmas atribuídas a ele. Compartilhar turma não libera prova de outro professor. Prova institucional não pode ser copiada nem editada por professor. Coordenador e diretor escolar veem todas as provas e turmas da escola. O perfil de criação é registrado e não segue mudanças futuras de função. |
| 03/11/2026 (CP1) | `prova.duplicar_propria` é padrão só do Professor e do Professor Autônomo. Exige contexto aceito e vigente, autoria própria e as capacidades de prova e gabarito. Não concede cópia de colega ao coordenador (CP2) nem transferência institucional. |
| 29/10/2026 | O diretor escolar não cria prova por padrão. |
| 29/10/2026 | O professor vê todas as provas das turmas em que está vinculado. |
| 30/10/2026 | O coordenador vê o relatório da escola. |
| 30/10/2026 | Cadastro de escolas e anexos só por diretor de núcleo ou administrador. O diretor escolar edita a própria escola. |
| 30/10/2026 | Correção aberta ou fechada. Só a supervisão reabre. |
| 30/10/2026 | Logo da escola e do ente (governo, prefeitura ou mantenedora). |

## 9. Acessos internos (A1, FN-013)

### 9.1 Modelo

- A tabela `usuario_acessos_internos` guarda a atribuição de Desenvolvedor, Suporte ou Financeiro. Ela tem perfil, situação, vigência e `versao_autorizacao`. Há uma atribuição por conta e perfil.
- Não existe espaço pedagógico fictício. A atribuição não altera `usuarios.perfil`.
- Contexto interno e vínculo escolar ou pessoal são mutuamente exclusivos. A mesma conta pode ter os dois, mas escolhe um. Os poderes não se somam.
- Gravar um perfil interno no campo legado da conta não concede acesso.
- A flag `ACESSOS_INTERNOS_ATIVOS` (`config/acessos_internos.php`) está desligada por padrão. Sem ela, as rotas internas respondem 403 ou 404.
- Cada uso revalida conta ativa, titularidade, perfil, vigência, situação e versão.
- O perfil `financeiro` não dá acesso a Plano e uso. A visão de vários clientes pertence ao aplicativo financeiro externo. O papel futuro do perfil é **[Pendente]**. Veja a seção 3.2.

**Representante institucional — [Pendente].** Não existe perfil nem chave de permissão para "representante institucional" ou "responsável financeiro". O código não tem chave financeira em `CatalogoPermissoes`. Nenhum papel financeiro é deduzido de perfil pedagógico ou de direção. Quem vê o resumo institucional e quem abre a Central do cliente pela instituição aguardam decisão do responsável. O aplicativo financeiro externo confirma o papel em cada ação. Este documento não cria chave nova.

### 9.2 Seleção e token

1. `GET /api/v1/auth/acessos-internos` lista as atribuições próprias, ativas e vigentes.
2. `POST /api/v1/auth/acessos-internos/selecionar` emite um token com as habilidades `interno:ID`, `versao:N` e `identidade:ID_PAI`. A seleção interna é sempre explícita, mesmo com uma só atribuição.
3. Mudar a versão, suspender, expirar, desativar a conta ou desligar a flag invalida o contexto.
4. O logout da identidade encerra os contextos derivados. O logout contextual não encerra os irmãos.
5. O contexto interno não chama painel pedagógico nem oferece menu escolar.

### 9.3 Permissões internas

| Permissão | Desenvolvedor | Suporte | Financeiro |
|---|:-:|:-:|:-:|
| `cliente.ver`, `cliente.gerir` | ✓ | ✓ | ✓ |
| `acesso_interno.gerir` | ✓ | — | — |
| `usuario.excecao_funcional.gerir` | ✓ | ✓ | — |
| `catalogo.solicitacao.analisar`, `catalogo.manter_global` (com `catalogo.interface_ativa`) | ✓ | ✓ | — |
| `pedido.atender` (com `pedidos_titulares.ativo`) | ✓ | ✓ | — |
| `pedido.abrir` (com `pedidos_titulares.ativo`) | ✓ | — | — |

Nenhum perfil interno recebe provas, alunos, notas, relatórios ou arquivos pedagógicos. O atendimento com acesso a dados depende do fluxo auditado "ver como" (FN-005, pendente). Os pedidos de titulares seguem a atribuição vigente (FN-064). Ser criador do protocolo não concede leitura automática.

### 9.4 Gestão dos acessos

Rotas: `GET/POST /api/v1/acessos-internos` e `PUT /api/v1/acessos-internos/{id}`. Interface web: `/acessos-internos`.

- Só um contexto Desenvolvedor válido consulta a gestão e concede Suporte ou Financeiro.
- O alvo é uma conta existente, ativa e com a senha inicial já trocada. O sistema não cria conta nem envia convite.
- O sistema recusa a própria conta e qualquer conta que já tenha atribuição de Desenvolvedor, mesmo suspensa. Não existe concessão, edição ou remoção de Desenvolvedor pela API ou pela web.
- Pessoa e perfil da atribuição são imutáveis. Suspender, reativar ou alterar o fim da vigência exige a versão atual e gera nova versão, o que invalida tokens antigos.
- Uma linha de `acessos_internos_controle` serializa as escritas. O ator é revalidado depois do bloqueio.
- O evento e a alteração são atômicos. O evento guarda IDs, estado anterior e novo, motivo e origem, sem senha, token, nome ou e-mail.

### 9.5 Primeiro Desenvolvedor

1. Simule: `php artisan g360:iniciar-desenvolvedor ID`.
2. Aplique em janela aprovada, com manutenção e `ACESSOS_INTERNOS_ATIVOS=false`: `--aplicar --confirmar-usuario=ID --motivo=REFERENCIA`.
3. Confira o evento antes de ligar a área.
4. Faça novo login.

O comando não cria conta, senha ou token. Ele não roda de novo depois do bootstrap, nem se já existir atribuição de Desenvolvedor. Suspender a única conta não autoriza repetir o bootstrap. Não existe promoção automática do administrador escolar.

## 10. Identidade pessoal (ID1, FN-016)

Estado: parcial. A fundação técnica e os mapas provisórios existem. A consulta global e a conciliação real não estão habilitadas.

### 10.1 Decisões

- A pessoa tem ID interno e UUID estáveis, separados de conta, matrícula e função. O CPF não é chave primária, URL, token nem credencial.
- CPF: normalização estrita com validação dos dígitos, cifra autenticada e índice HMAC com versão e separação de domínio (`identidade:CPF:BR:v1`). O valor em claro não aparece em relatório, exceção ou serialização.
- Documento declarado não significa titular verificado. Pessoa provisória não recebe CPF fictício.
- Documentos, índices e eventos são imutáveis. Uma correção futura cria novo evento e conciliação explícita.
- O índice atual de CPF de aluno e usuário usa o texto recebido. O mesmo CPF com máscara diferente pode gerar outro hash. O inventário normaliza em memória e só conta a coincidência. Ninguém corrige hashes em massa antes do contrato de conciliação.
- Repetição de CPF entre matrículas locais pode ser legítima. Ela não autoriza fusão.

### 10.2 Comandos

| Comando | Função |
|---|---|
| `g360:identidades-inventariar --limite=N` | Somente leitura. Devolve contagens agregadas (ausentes, inválidos, ilegíveis, repetidos). Não imprime dado pessoal. |
| `g360:identidades-provisorias [--aplicar] [--lote=N]` | Simula por padrão. `--aplicar` exige `IDENTIDADE_PROVISORIOS_ATIVOS=true` (desligado por padrão). Lote de 1 a 1000. Cria pessoa, mapa e evento por unidade, em transação. Retoma após falha. |

O comando aplica uma pessoa provisória por conta e por cadastro de aluno. Ele não reconhece duas linhas como a mesma pessoa. Nome igual, CPF repetido ou ausente não aproxima registros. O comando não rodou em base operacional.

### 10.3 Proposta de consulta de identidade (não ativa)

O responsável autorizou registrar a proposta. Nenhuma chave existe nos seeds ou nas rotas.

| Operação proposta | Elegíveis | Limite |
|---|---|---|
| `identidade.usuario.consultar` | Administrador da Rede, Diretor de Núcleo, Diretor Escolar, Coordenador, com `usuario.gerir`. Vice-diretor só com concessão explícita. | Instituição no alcance. Consulta exata para emitir convite. |
| `identidade.aluno.consultar` | Diretor Escolar, Coordenador, Secretário Escolar, com `aluno.gerir` | Ingresso na própria escola. Não revela escola de origem, notas ou histórico. |

Regras da proposta:

- A resposta traz uma intenção opaca. Não revela e-mail completo, outra escola, funções, telefone, nascimento ou histórico.
- A consulta tem limite por operador e auditoria sem CPF em claro. Saber um CPF não concede acesso.
- Não se associa dois cadastros por nome, máscara, matrícula ou CPF com dígitos válidos. A associação exige comprovação, revisão institucional documentada e segunda conferência em caso de conflito.
- Professor, Aplicador e Financeiro não consultam. Suporte e Desenvolvedor atuam por caso atribuído.
- Decisão humana aberta: definir a comprovação admitida antes de associar cadastros legados. Recomendação: conta existente confirma no próprio acesso verificado.

## 11. Imagem de perfil (IM1, FN-019)

### 11.1 Quem altera

- O usuário altera a própria imagem, por `POST /api/v1/auth/imagem`.
- O aluno recebe imagem só por `POST /api/v1/alunos/{id}/imagem`, com `aluno.gerir` e alcance do vínculo ativo. A leitura exige `aluno.ver`.
- A turma recebe imagem por `POST /api/v1/turmas/{id}/imagem`, com `turma.gerir`.
- A imagem não altera identidade, papéis nem permissões.

### 11.2 Fluxo de interface

1. A pessoa clica no avatar. O diálogo oferece Avatar (galeria de 36 imagens) ou Foto.
2. Em Foto, há duas ações: Tirar foto (captura nativa `capture=user`, só ao toque) e Escolher arquivo. A disponibilidade da câmera depende do aparelho.
3. A foto é preparada no navegador: orientação, redução, enquadramento circular, zoom, rotação fina de −180° a +180° e geração do arquivo final antes do envio.
4. Cancelar não salva e preserva a imagem anterior. Erro de envio conserva a edição.
5. No aluno novo, a imagem fica preparada em "Usar esta imagem" e vai junto com o cadastro, na mesma transação.

Limites do navegador: entrada até 32 MB e 48 megapixels. HEIC/HEIF usa decodificador carregado só quando necessário. DNG lê a prévia JPEG incorporada. O Cropper.js 2.3.1 (MIT) roda localmente, sem serviço externo.

### 11.3 Contrato do servidor

| Item | Regra |
|---|---|
| Entrada aceita | JPG, PNG ou WebP, até 2 MB e 4096 px por lado. O limite de 2 MB é defesa, não o tamanho esperado. |
| Reprocessamento | Corte central quadrado, WebP de 384×384, remoção de metadados. Alvo de 96 KiB e teto de 128 KiB. |
| Armazenamento | Arquivo privado com UUID. O banco guarda só referência e `imagem_versao`. Sem original nem base64. |
| Substituição | Grava o novo arquivo antes de trocar a referência. Remove o anterior só depois do sucesso. |
| Acesso | Autenticado e por alcance. Nunca por URL pública. |
| Limite de uso | Limitador nomeado `imagem-perfil`: 10 requisições por minuto por usuário e rota. Navegação comum não consome essa cota. |
| Proibido | Reconhecimento facial e envio a serviço externo. |

Para aluno em espaço pessoal, o retrato é permitido sem liberar CPF, nascimento, gênero ou responsável.

### 11.4 Cabeçalho

O avatar do topo usa um botão de 44×44 px e um círculo de 36×36 px com borda de 1 px (`--border`) e foco visível de 2 px. A imagem usa `object-fit: cover`.

## 12. Regras transversais

- **Escopo na API.** Toda consulta e alteração passa por `Escopo` e `Instituicoes`.
- **Correção fechada.** Com `provas.correcao_fechada_em` preenchida, só quem tem `correcao.reabrir` envia, exclui, vincula ou revisa.
- **Cartão de outra prova.** O QR direciona o cartão para a prova correta da mesma escola e espaço. Se não for possível, o envio é recusado com motivo.
- **Aplicador.** Nunca recebe nota, gabarito ou relatório. Só provas com data de aplicação igual a hoje.
- **Arquivos.** Fotos de cartões e PDFs só chegam a quem acessa a prova. URLs de imagem carregam ator, espaço e versão do acesso. Revogar o acesso invalida os links.
- **Assinatura.** Só o titular da conta vê e gere plano, pagamentos e cancelamento. Isso independe do perfil (FN-030, substituída). **Limitação do legado:** o MP-027 usa a conta de acesso como titular (`assinaturas.titular_id` = id do usuário). Essa regra descreve o legado, que segue como está (FN-068, subfase 3.1). No modelo novo, a titularidade contratual é da conta contratante, no aplicativo financeiro externo. A gestão acontece na Central do cliente ([ADR-0062](../decisoes/0062-plano-e-uso-e-central-do-cliente.md)).
- **Conta individual antiga.** O modelo antigo dava ao professor de conta individual as permissões de diretor numa escola criada para ele. Esse modelo foi substituído pelo Professor Autônomo em espaço pessoal. Não crie contas dessa forma.
- **Jobs.** Jobs transportam IDs de ator, vínculo e espaço e revalidam o contexto antes de ler e antes de gravar.
- **Rate limit.** Rotas sensíveis têm `throttle` próprio (valores nas tabelas deste documento).

## Divergências encontradas

| Assunto | Documento antigo | Código atual |
|---|---|---|
| Divergências do `docs/03` (seção 7) | Listava rotas que permitiam ao diretor escolar editar prova e ao professor cadastrar aluno sem extra. | O catálogo do código coincide com a matriz. O diretor escolar tem `prova.*` e `gabarito.editar` só como extra. O professor tem `aluno.gerir` só como extra. |
| Aluno | `docs/03` diz "depende do vínculo usuário e aluno (pendente)". | O vínculo existe (`vinculo_alunos`). O acesso próprio completo do aluno ainda é a FN-020 (pendente). |
| Secretário de Educação | `docs/03` o trata como perfil operante. | Não há espaço de secretaria em `PerfisEspaco::POR_TIPO`. O perfil não seleciona contexto. O desenho contextual é requisito da FN-017 e do replanejamento (doc 115). |
| Contexto por aba | Docs 61 a 62 descrevem identificador por aba, `_ctx` e `sessionStorage`. | A decisão UR4 (06/11/2026) trocou o modelo por contexto único por sessão web. A web ignora ou recusa `_ctx` e os cabeçalhos de contexto. O `sessionStorage` só guarda o token temporário do convite. |
| Conta individual | `docs/03` seção 6: professor com extras automáticas de diretor numa escola própria. | Substituída por `professor_autonomo` em espaço pessoal, sem extras. |
| Importação de alunos | Docs 03 e 70 citam `aluno.importar` e uma tela "em breve". | A permissão existe. O importador XLSX não existe (FN-034). Há só o comando CLI `g360:importar-alunos`. |
| Cópias entre espaços | `docs/03` registra `copia.autorizar_origem` e `copia.autorizar_destino` como padrão de Administrador e Diretor Escolar. O doc 115 manda remover as capacidades genéricas de execução do Diretor Escolar. | O catálogo ainda mantém as duas chaves para o Diretor Escolar. A revisão pertence à FN-025 (pendente). O recurso de cópia permanece desligado. |
| Vice-diretor | Doc 133 chama o Vice-diretor de "ausente". | O perfil existe desde PF1-C, validado na homologação (FN-014). Produção depende de decisão do responsável. |
| Limite da imagem | Os docs 135 a 136 citam 2 MB como limite da foto e relatam HTTP 429 ao salvar avatar. | O limite de 2 MB vale só para o servidor. O navegador prepara a foto antes do envio. O limitador `imagem-perfil` é separado da cota numérica de navegação. |
| Ativação de A1 | Docs 67 a 69 dizem "habilitação desligada por padrão". | A flag continua desligada por padrão no código. O planejamento da FN-013 registra entrega em produção. O valor da flag no servidor é configuração de ambiente e não consta do repositório. |
| Perfis do vínculo | A migration V1-A (doc 55) lista sete perfis no vínculo. | O enum de `usuario_vinculos.perfil` tem onze valores. O de `usuarios.perfil` tem onze, mas sem `professor_autonomo`. |
