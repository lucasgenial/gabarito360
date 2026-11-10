# OMR e documentos

**Atualizado em:** 10/11/2026, por lucasgenial

Este documento descreve o motor de leitura óptica de marcas (OMR, Optical Mark Recognition), os cartões-resposta, o QR code, a leitura pela fila e a geração de PDFs. Funcionalidades relacionadas: FN-045 (motor OMR e acompanhamento), FN-046 (revisão de leituras), FN-047 (qualidade, tentativas e documentos verificáveis), FN-048 (driver externo, candidata), FN-049 e FN-071.

Decisão de origem: o responsável aprovou, em 29/10/2026, a funcionalidade total de geração do gabarito, marcação das respostas, turmas e correção por foto de celular.

## 1. Visão geral

O OMR lê a foto de um cartão-resposta preenchido, identifica prova e aluno, detecta as marcações e calcula a nota contra o gabarito oficial. Dúvidas vão para revisão humana.

O modo vigente é o **nativo**: o Gabarito360 gera o cartão e conhece seu layout. O **modo externo** (cartões de terceiros) é a FN-048 e não existe no código.

Componentes:

| Componente | Local | Função |
|---|---|---|
| `OmrDriverInterface` | `apps/api/app/Services/Omr/` | Contrato do motor: `processar(imagem, questões, alternativas)`. |
| `NativoOmrDriver` | idem | Executa `ler_cartao.py` por `Process`. |
| `SimuladoOmrDriver` | idem | Leituras determinísticas para desenvolvimento e testes. |
| `OmrIdentificacaoInterface` | idem | Entrega prova, aluno, língua e qualidade lidos do QR. |
| `ler_cartao.py` | `apps/api/omr/` | Visão computacional (OpenCV). |
| `layout.py` | idem | Geometria única, compartilhada pelo gerador e pelo leitor. |
| `gerar_cartoes.py` | idem | PDF dos cartões (ReportLab). |
| `qualidade.py` | idem | Barreira de qualidade da foto. |
| `gerar_imagens.py` | idem | Miniatura e foto endireitada. |
| `gerar_resultado.py`, `gerar_painel_turma.py`, `cabecalho_documento.py` | idem | PDFs de resultado e do painel da turma, e cabeçalho comum. |

### Configuração

Arquivo `apps/api/config/omr.php`:

| Chave | Variável | Padrão |
|---|---|---|
| `driver` | `OMR_DRIVER` | `nativo` (ou `simulado`) |
| `python` | `OMR_PYTHON` | `python3`. No servidor, aponta para o `.venv` de `apps/api/omr`. |
| `timeout` | `OMR_TIMEOUT` | 60 segundos |

Dependências Python (`requirements.txt`): `opencv-python-headless`, `numpy`, `reportlab`. A publicação cria o `.venv` e instala os pacotes.

## 2. Cartão-resposta

### Geração

1. O usuário abre a prova e escolhe **Cartões** por turma, ou cartões em branco.
2. A API recebe `GET /api/v1/provas/{id}/cartoes/pdf` (permissão `cartao.imprimir`).
3. O `GeradorCartoes` chama `gerar_cartoes.py` com os dados da prova.
4. A API devolve o PDF como download. Esta é uma exceção à regra "API só JSON": é arquivo, não tela.

### Conteúdo

- Cabeçalho com identidade da instituição.
- Nome e matrícula do aluno pré-impressos (cartão personalizado).
- Quatro marcadores quadrados pretos (8 mm) nos cantos da folha A4. Eles permitem corrigir a perspectiva.
- Grade de bolhas por questão.
- QR code com a descrição do layout.

### QR code

Formato do texto: `G360|P{prova}|A{aluno}|N{questões}|K{alternativas}|L{0/1}`.

| Campo | Significado |
|---|---|
| `P` | ID da prova. |
| `A` | ID do aluno. Vazio em cartão em branco. |
| `N` | Número de questões impressas. |
| `K` | Número de alternativas. |
| `L` | 1 se a prova tem opção de língua estrangeira. |

O QR descreve o layout impresso. A leitura usa esse layout, mesmo que a prova tenha mudado depois.

### Identificação do aluno

1. Cartão pré-impresso: o QR identifica o aluno.
2. Cartão em branco: o usuário vincula o aluno depois da leitura (`PUT cartoes/{id}/vincular-aluno`).

### Língua estrangeira

Em provas com `opcao_lingua`, as questões 1 a 5 têm gabarito de Inglês (`alternativa`) e de Espanhol (`alternativa_espanhol`). A língua marcada vai para `cartoes.lingua`. A `CalculadoraNota` usa o gabarito correspondente.

## 3. Leitura

### Regras do leitor

| Regra | Valor |
|---|---|
| Bolha marcada | Fração preenchida do miolo igual ou maior que 0,55. |
| Bolha em dúvida | Fração entre 0,30 e 0,55. A questão fica ambígua. |
| Marcação múltipla | Duas ou mais bolhas marcadas. A questão fica ambígua. |
| Confiança | `0,5 + (f1 - f2)`, limitada entre 0 e 1. `f1` e `f2` são as duas maiores frações. |
| Fundo | Normalizado para reduzir sombra. |

### Barreira de qualidade

Antes de aceitar a foto, a API executa `ler_cartao.py --qualidade`. A barreira recusa foto com baixa resolução (menos de 600 por 900 pixels) ou sem geometria válida. Ela nunca inventa respostas. Se a verificação estiver indisponível, a API responde 503 e mantém a correção atual.

### Resultado do motor

O driver devolve, para cada questão: `numero_questao`, `alternativa`, `confianca`, `ambigua`, `multiplas_marcacoes` e `alternativas_detectadas`. Se a leitura falhar, todas as questões ficam ambíguas e vão para revisão manual.

## 4. Leitura pela fila

### Envio

1. A tela envia a foto para `POST /api/v1/provas/{id}/cartoes`, com uma chave única (UUID) por envio.
2. `FilaCartoes::receber` confere prova, permissão (`cartao.enviar`) e bloqueio de correção.
3. A chave é contextual: o mesmo usuário repetir a chave não duplica o envio.
4. A API avalia a qualidade, guarda a foto em `cartoes/espacos/{espaço}/{prova}` e cria o registro `cartao_envios`.
5. A API enfileira `ProcessarCartaoJob` na fila `alta`.

### Processamento

- O job tenta até 3 vezes, com espera de 5 e 20 segundos.
- O prazo do job é `3 × OMR_TIMEOUT + 30` segundos. O job pode ler duas vezes (redirecionamento pelo QR) e gerar as imagens leves.
- `WithoutOverlapping` impede dois processamentos do mesmo cartão.
- O job revalida espaço, ator e vínculo antes de gravar.

### Estados do envio

Registro `cartao_envios.status`: `na_fila`, `processando`, `concluido`, `recusado` e `erro`.

| Consulta | Rota da API |
|---|---|
| Estado de um envio | `GET provas/{id}/cartoes/envios/{chave}` |
| Repetir um envio | `POST provas/{id}/cartoes/envios/{chave}/repetir` |
| Estado resumido da prova | `GET provas/{id}/cartoes/status` |
| Lista de cartões | `GET provas/{id}/cartoes` |

Com `QUEUE_CONNECTION=sync`, o envio roda na requisição e devolve o resultado na hora. Em produção, o worker Redis processa a fila `alta`. A tela consulta o estado por polling. Veja [01](01-contexto-e-containers.md).

### Estados do cartão

`cartoes.status`: `pendente`, `lido`, `ambiguo`.

| Estado | Significado | Ação |
|---|---|---|
| `pendente` | Ainda sem leitura. | Aguardar. |
| `lido` | Leitura com confiança suficiente. | Nenhuma. |
| `ambiguo` | Marcação múltipla, em branco ou fraca. | Resolução manual. |

### Estado da prova

`AtualizadorStatusProva` muda o estado da prova: `publicada`, `em_correcao`, `corrigida`.

1. A prova vira `corrigida` só quando todos os alunos ativos das turmas têm um cartão lido.
2. Um novo cartão em prova corrigida a reabre para `em_correcao`.
3. O fechamento manual da correção usa `correcao/fechar` e `correcao/reabrir` (permissões `correcao.fechar` e `correcao.reabrir`).

## 5. Revisão e tentativas

| Ação | Rota da API | Permissão |
|---|---|---|
| Vincular aluno | `PUT cartoes/{id}/vincular-aluno` | `correcao.alterar` |
| Resolver ambiguidade | `POST cartoes/{id}/resolver-ambiguidade` | `correcao.alterar` |
| Revisar leitura | `POST cartoes/{id}/revisar` | `correcao.alterar` |
| Excluir cartão | `DELETE cartoes/{id}` | `correcao.alterar` |
| Ver cartões da prova em revisão | `GET provas/{id}/revisao` | `correcao.acompanhar` |
| Listar e ver tentativas | `GET provas/{id}/tentativas`, `GET tentativas/{id}` | `correcao.acompanhar` |
| Aplicar ou descartar tentativa | `POST tentativas/{id}/aplicar`, `.../descartar` | `correcao.alterar` |

Uma **tentativa** é uma leitura preservada. Cada nova foto de um aluno gera uma tentativa numerada (tabela `cartao_tentativas`, com eventos em `cartao_tentativa_eventos`). A tentativa guarda hash da imagem, dados lidos e o cartão anterior. O usuário compara e escolhe aplicar ou descartar.

Toda resolução e revisão registra usuário, momento e motivo.

## 6. Imagens dos cartões

1. Cada cartão tem três versões: `miniatura`, `endireitada` e `original`.
2. A API entrega a imagem em `GET cartoes/{id}/imagens/{tipo}`, com permissão `correcao.acompanhar`.
3. O Web entrega a imagem por rota autenticada sob `/app` (`cartoes/{id}/imagens/{tipo}`). Ele usa `fetch` e Blob local, sem URL assinada no navegador.
4. As respostas são privadas e sem cache.
5. O recorte da marcação usa a mesma rota autenticada.

## 7. PDFs e documentos verificáveis

| Documento | Gerador | Rota da API |
|---|---|---|
| Cartões-resposta | `gerar_cartoes.py` | `GET provas/{id}/cartoes/pdf` |
| Resultado do aluno | `gerar_resultado.py` | `GET alunos/{id}/resultados/{prova}/pdf` |
| Resultados em lote | `gerar_resultado.py` | `GET provas/{id}/resultados/pdf` (limite de 5 por minuto) |
| Painel da turma | `gerar_painel_turma.py` | `GET turmas/{id}/painel/pdf` |

Regras comuns:

1. O cabeçalho vem de `cabecalho_documento.py`, com a identidade da instituição. Ele não altera a grade OMR.
2. O PDF do aluno traz nota, resumo e a folha questão por questão (acertou, errou, em branco, anulada).
3. O Web aceita GET ou POST com CSRF e revisão para baixar PDFs.
4. Documentos emitidos têm um código público de conferência. A página `/validar-documento` do Web e a rota pública `POST documentos/validar` da API conferem o código. A URL pública vem de `DOCUMENT_VALIDATION_URL`.
5. O código público não é credencial de acesso.

## 8. Comandos de importação

| Comando | Função |
|---|---|
| `php artisan g360:importar-alunos {escola} arquivo.csv` | Importa alunos (colunas turma, nome, matrícula). É idempotente pela matrícula. |
| `php artisan g360:importar-provas arquivo.json` | Cria provas e gabarito a partir de um arquivo de simulado. Com espaços ativos, sem `--aplicar` o comando apenas simula. |
| `php artisan g360:cartoes-imagens` | Gera as imagens leves dos cartões existentes. |

## 9. Qualidade e limites conhecidos

- Validação feita em fotos simuladas (perspectiva, rotação, sombra, ruído, JPEG): zero erros de leitura em 10, 20, 45 e 90 questões, com 4 e 5 alternativas.
- Ponta a ponta já passou em MariaDB local: PDF, upload pelo celular, nota com gabarito de Espanhol, vínculo manual e relatório da turma.
- Pendência registrada no documento original: teste com foto real de cartão impresso. Confirme o estado na [FN-045](../funcionalidades/045-omr-correcao/planejamento.md) e na [FN-047](../funcionalidades/047-omr-qualidade-resultado/planejamento.md).
- A meta de produto era 12 segundos por cartão e 98,6% de acerto sem intervenção. Nenhuma medição de produção confirma essa meta.

## Divergências encontradas

| Documento antigo | Afirmação | Situação no código |
|---|---|---|
| `08-omr.md` | Tela de ambíguos com contadores, donut e barra de progresso. | Existe `ProgressoCorrecao` e `QuadroLeituras` na API. O desenho exato da tela está em [09](09-interface-e-design-system.md). |
| `08-omr.md` | Rota `POST /provas/{id}/cartoes` com upload simples. | Exige chave UUID, barreira de qualidade e fila (`cartao_envios`). |
| `08-omr.md` | Campos `imagem_url`, `respostas` em JSON no cartão. | Respostas ficam em `cartao_respostas`. Há `cartao_envios`, `cartao_tentativas` e `cartao_tentativa_eventos`. Modelo completo em [03](03-modelo-de-dados.md). |
| `08-omr.md` | Opção de OMR externo "a definir". | Não existe. É a FN-048 (candidata). |
| `18-omr-nativo-e-cartoes.md` | O motor só lê e a foto é reduzida no celular a 2400 px. | O envio também passa pela barreira de qualidade (resolução mínima 600 por 900). O JavaScript de câmera está em `correcao-camera.js`. |
| `18-omr-nativo-e-cartoes.md` | O servidor instala o `.venv` com `infra/deploy.sh`. | O roteiro oficial é `scripts/operacao/deploy.sh`. |
| `12-arquitetura.md` | Fila e polling a cada 5 segundos para a tela de acompanhamento. | A tela consulta o estado do envio e o status da prova (`correcao.status`). O intervalo está no JavaScript (`correcao-acompanhamento.js`). |
