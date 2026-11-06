# UR3 — navegação estável e acesso ativo na sessão

Decisão do responsável em05/11/2026: login → `/app/vinculos` → seleção explícita → `/app/painel`; ferramentas em endereços estáveis como `/app/turmas`. Um vínculo ativo por sessão. Trocar escola ou perfil vale para todas as abas dessa sessão; ações de telas antigas devem ser bloqueadas.

## Planejamento revisto

| Parte interna | Entrega | Critério |
|---|---|---|
| UR3-A | Seleção inicial e rotas `/app/*` | Seleção explícita mesmo com uma opção; menus e documentação sem identificador de vínculo. |
| UR3-B | Estado e concorrência | Credenciais no servidor, snapshot imutável por requisição, revisão da tela e mutex até salvar a sessão. |
| UR3-C | Formulários, consultas e arquivos | POST/PUT/DELETE com CSRF e revisão; fotos/PDF/JSON com revisão; nenhum reenvio automático após troca. |
| UR3-D | Compatibilidade | GET antigo encaminha sem selecionar; ações antigas recusadas; conta sem regime de vínculos tem seleção explícita própria. |
| UR3-E | Validação e homologação | Regressão, concorrência real, revisão independente, desktop/celular, CI e dados preservados. |

A publicação ocorre no fechamento coerente de UR3, somente na homologação. Produção permanece sob decisão expressa; PR194 continua sendo um hotfix separado de dependências e não é autorizado por esta mudança.

## Contrato

O cookie de sessão identifica a sessão; token de identidade, bearer do acesso selecionado e handles internos não são enviados em URLs nem ao JavaScript. A referência pública `vinculo-N` deixa de compor a navegação. Revisão opaca da tela é somente um comparador de estado: não autentica, não seleciona acesso e não concede permissão.

Cada seleção bem-sucedida por POST gera nova revisão e invalida o handle anterior. O servidor compara a revisão fixa do formulário ou do cabeçalho `X-G360-Revisao` antes de efeitos ou consultas contextuais. GET de navegação HTML renderiza o acesso atual; dados de AJAX, fotos e PDFs exigem revisão. Ausência/conflito/desatualização resulta em409, com a ação não executada. Corpo e cabeçalho contraditórios também são recusados. CSRF, permissões da API, revogação e duração de120minutos sem atividade permanecem.

Fotos são carregadas por fetch autenticado e Blob local. Downloads nativos usam POST com CSRF/revisão; o leitor PDF recebe dados por fetch. Formulários trazem a revisão no HTML e funcionam sem depender de sua inclusão pelo JavaScript. Uma aba antiga nunca recebe automaticamente a revisão nova nem reaproveita o rascunho no novo acesso.

O recorte da marcação também usa fetch contextual e Blob, mantendo a geometria anterior. Cargas antigas são canceladas e descartadas na troca; falha não representa uma questão em branco. A escolha de resposta aguarda a imagem válida, com tentativa de recarregamento disponível sem registrar correção.

GETs antigos não adotam o vínculo presente no endereço, cache ou query. Ações antigas não são convertidas nem repetidas. O regime anterior sem múltiplos vínculos oferece “Acesso da conta”, obtido da identidade autenticada no servidor, sem aceitar escola/perfil fornecidos pelo navegador. Quando o regime de vínculos estiver ativo, não existe essa alternativa de fallback.

## Concorrência e limites

No BFF atual de um único host, um mutex `flock` entre EncryptCookies e StartSession protege leitura, controller/API e salvamento da sessão. O arquivo recebe HMAC do SID, em storage privado e persistente compartilhado pelos workers/releases. Espera monotônica de15segundos; timeout423 sem executar a operação. O descritor permanece aberto até o salvamento; o SO libera o lock ao encerrar o processo. Não remover os arquivos de lock durante o uso: isso criaria inodes concorrentes.

Esta implementação não atende múltiplos hosts com filesystems separados. Antes de escalar o BFF, substituir a estratégia de exclusão por uma solução distribuída com garantias equivalentes. Operações longas podem causar423 em outra aba; não interromper relatórios para liberar concorrência.

A API continua responsável por vincular bearer, escopo e autorização. `/auth/me` atual não fornece todos os identificadores contextuais; o BFF confere os que forem fornecidos e recusa contradições. Não interpretar essa conferência opcional como substituta da ACL da API.

Os módulos possuem URLs estáveis; identificadores de registros específicos continuam no contrato atual dos recursos. Esta entrega remove o vínculo da navegação e não converte IDs de todos os recursos para UUID nem trata UUID como credencial. Uma revisão futura dessa identificação requer contrato próprio, preservando autorização e abertura dos registros.

## Referências

- [Sessões e concorrência no Laravel](https://laravel.com/docs/13.x/session).
- [Gestão de sessão OWASP](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html).
- Direção prévia e pareceres externos: `operacao-20261102/ur3-direcao-design.md`, `ur3-contrato-seguranca-rotas-app.md` e `ur3-seguranca-revisao-final.md`.
