# UR4 — recursos fora das URLs do navegador

Prioridade 0, autorizada pelo responsável em 06/11/2026. Substitui a sequência anterior que colocava AP1 antes da revisão de endereços. Implementação e publicação exclusivamente na homologação, preservando dados e o contrato HTTP da API.

| Parte interna | Entrega | Aceite |
|---|---|---|
| UR4-A | Catálogo de operações, URLs fixas e referências de recurso no corpo | Operação escolhida pela rota registrada; sessão, identidade e revisão conferidas; nenhum recurso global por sessão. |
| UR4-B | Motor JavaScript externo de navegação e hidratação | Entrada de histórico própria, atualização por GET seguido de POST de consulta, abas independentes, escolha explícita quando faltar estado. |
| UR4-C | Consumidores SSR, formulários, imagens, PDFs, polling e retornos | Sem IDs/referências em path, query ou fragmento de recursos; uploads, auditoria, CSRF e idempotência preservados. |
| UR4-D | Matriz de perfis, negativas, concorrência e revisão independente | Visitante, perfis escolares/pessoais/internos, contexto encerrado, conflitos e respostas atrasadas testados; desktop e celular no Chrome. |
| UR4-E | CI, integração e homologação | Fonte API byte-idêntica, backup, dados/arquivos conferidos, regressões e observação operacional. Sem produção. |

O navegador usa URLs semânticas fixas. JavaScript mantém uma referência não autorizadora por entrada do histórico e a envia no corpo, com CSRF e a revisão original do vínculo. Uma aba não troca o alvo da outra. Ausência/expiração do estado exige nova escolha; não recupera o último recurso globalmente. Troca de vínculo continua valendo para toda a sessão e bloqueia as telas antigas.

O BFF resolve a referência para a operação exata e os parâmetros mantidos no servidor. O controlador e os middlewares originais continuam executando; a API recebe os mesmos métodos, caminhos e campos de negócio. O navegador não escolhe controller, host, URL da API ou método de negócio arbitrário.

Código novo fica em arquivos JavaScript externos. HTML fornece atributos e dados não executáveis. A transformação das referências emitidas pelo SSR é complementada pela adaptação explícita dos consumidores JSON e JavaScript; apenas mudar o endereço aparente não satisfaz o requisito.

Filtros, buscas e paginação não são confundidos com autorização. O validador público de documentos preserva códigos públicos de conferência e QR codes existentes. Assets estáticos versionados não são recursos escolares privados. Caminhos REST internos da API permanecem inalterados.

Consulta prévia UI/UX aprovada em `ur4-direcao-design.md`; orientação de segurança em `ur4-motor-bff-revisao-previa.md`, ambos em `operacao-20261102`. Não substituem testes e revisão final da implementação.

## Verificação da implementação

A suíte Web local passou com 285 testes e 9.309 asserções. A emissão transversal cobre 119 operações parametrizadas e 123 pares operação/verbo, tanto em documento completo quanto em fragmento HTML. Essa verificação confere os destinos e as referências; não substitui as verificações de autorização dos controladores e da API.

Os 34 testes Node e os sete ensaios Chromium do job de telas passaram localmente. Foram preservados os cenários de contexto antigo, CSRF, duas abas, histórico, atualização sem repetição de alterações, falha de leitura, retorno de foco e conteúdo privado. A conferência no Chrome do usuário validou duas turmas fictícias no mesmo endereço, edição independente e atualização, além do modal com JavaScript externo.

O parecer `ur4-design-final.md` aprovou o motor e o piloto de Turmas em desktop e celular, incluindo carregamento, erro, recuperação, saída sem JavaScript e teclado. O complemento de foco do histórico foi aprovado separadamente. O alcance visual não equivale a uma inspeção manual de todos os perfis e módulos.

CI, comparação dos dados e arquivos, publicação e observação operacional na homologação continuam gates de entrega. A produção permanece fora desta autorização.
