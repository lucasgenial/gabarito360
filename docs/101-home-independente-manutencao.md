# 101 — Página inicial independente da manutenção

Solicitação aprovada pelo responsável em 02/11/2026, após observar a home retornando a página genérica 503 durante a manutenção. A apresentação pública deve continuar disponível enquanto o acesso ao sistema estiver interrompido.

## Comportamento

Em 02/11/2026, o responsável informou o perfil oficial Instagram `@gabarito360`. O rodapé público passa a oferecer o link `https://www.instagram.com/gabarito360/`, em nova aba identificada de forma acessível; demais redes continuam em preparação.

- Nginx entrega `/` e seu CSS diretamente de `/var/www/gabarito360-publico/current`, sem PHP, Redis ou banco na requisição pública. É a mesma home Blade existente, renderizada previamente de forma anônima; não há duplicação manual do conteúdo ou mudança de identidade visual.
- As seções da home continuam navegáveis. Login, recuperação de senha, contato com envio e demais funções da aplicação continuam sujeitos à manutenção; não há aceitação silenciosa de formulários enquanto o sistema estiver parado.
- Login/rotas web recebem 503 com uma página responsiva e links para voltar à home ou tentar novamente. API recebe 503 JSON. Ambos usam `Retry-After: 60` e `Cache-Control: no-store`, inclusive na indisponibilidade do PHP.
- Respostas de erro produzidas pela aplicação em funcionamento são preservadas, inclusive JSON 503 de uma operação. O aviso trata manutenção declarada e falhas de conexão do Nginx com o PHP.
- Não se libera a aplicação por exceção ao middleware de manutenção. O bloqueio de gravações e o procedimento de backup permanecem válidos.

## Publicação

Instalar `publicar-home.sh`, `exportar-home.php` e `manutencao.html` juntos em `/root/g360-operacao`. Executar o publicador enquanto a aplicação estiver disponível. Ele obtém planos válidos da API, recusa formulários/tokens na home e não substitui a versão anterior por preços de fallback quando a API falhar.

O HTML, CSS e aviso ficam em uma pasta nova; a troca do symlink `current` é atômica. O CSS também tem URL por hash, preservada para abas abertas antes de uma atualização. Versões anteriores são preservadas. Falhas de geração ou cópia não alteram a versão servida. A home não recebe dados de sessão, cookies nem tokens CSRF. A aplicação continua responsável por seus próprios formulários.

Instalar `deploy/nginx/publico-independente.conf` como `/etc/nginx/snippets/gabarito360-publico.conf`. Gerar uma candidata a partir da configuração REAL com `preparar-nginx-publico.py ORIGEM DESTINO`, conferir o diff, preservar cópia, validar com `nginx -t` e recarregar. O gerador preserva logs protegidos e o socket específico da API; não substituir o arquivo real pelo template genérico do repositório.

O B5 atualiza o conteúdo público após reabrir API/web, quando a instalação está presente. No corte especial com `--manter-manutencao`, executar o publicador na retomada após abrir os serviços. Mudanças comerciais fora de deploy exigem regenerar a home pelo mesmo comando. Se o PHP ou API estiverem indisponíveis, manter a última versão válida.

## Verificação e limites

Integração com Nginx real e FastCGI fictício em porta exclusivamente local: aplicação normal, arquivos de manutenção presentes e backend removido. Conferir home/CSS 200, POST na home recusado, login 503 legível, API 503 JSON e cabeçalhos de não armazenamento. Isso não exige derrubar o PHP do servidor usado pelas pessoas.

Teste separado interrompe a exportação após uma publicação válida, altera o CSS da aplicação e confirma que o HTML/CSS anteriores continuam disponíveis. Regressões do B5 continuam obrigatórias.

Nenhuma migration ou alteração em contas/gabaritos é necessária. A independência cobre a apresentação pública durante manutenção da aplicação; uma indisponibilidade do próprio Nginx, host ou rede ainda afeta o site.
