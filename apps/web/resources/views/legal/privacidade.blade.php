{{-- Prévia sem rota pública. A publicação depende da aprovação do texto jurídico. --}}
@extends('layouts.public')
@section('title', 'Aviso de privacidade — Prévia para revisão')
@section('robots', 'noindex, nofollow')
@section('content')
<article class="documento-legal" aria-labelledby="titulo-documento">
<header><h1 id="titulo-documento">Aviso de privacidade</h1><p>Última atualização: <span class="campo-pendente">[DATA]</span></p></header>
<x-aviso tipo="atencao">Prévia para conferência. Os campos entre colchetes serão preenchidos antes da publicação.</x-aviso>
<section><h2>Quem somos</h2><p>O Gabarito360 é operado por <span class="campo-pendente">[RAZÃO SOCIAL]</span>, CNPJ <span class="campo-pendente">[CNPJ]</span>, com sede em <span class="campo-pendente">[ENDEREÇO]</span>. Nosso encarregado pelo tratamento de dados pessoais é <span class="campo-pendente">[NOME DO ENCARREGADO]</span>, pelo e-mail <span class="campo-pendente">[E-MAIL DO ENCARREGADO]</span>.</p></section>
<section><h2>Quem decide sobre os dados</h2><p>Quando o sistema é usado por uma escola, núcleo, rede, secretaria ou professor, essa instituição ou professor define como os dados de alunos são usados. Nós tratamos esses dados conforme as instruções recebidas. Somos responsáveis diretos pelos dados de contas, cobrança e segurança da plataforma.</p></section>
<section><h2>Dados tratados</h2><ul><li>Usuários: nome, e-mail, telefone, CPF, cargo, vínculos, dados de acesso e registros de uso.</li><li>Alunos: nome, matrícula, data de nascimento, turma, escola, foto do cartão-resposta, respostas, notas e relatórios.</li><li>Responsáveis: nome e, quando informados, contatos.</li><li>Clientes: dados de contrato, cobrança e nota fiscal.</li><li>Contato: dados enviados pelo Fale Conosco.</li></ul></section>
<section><h2>Finalidades</h2><p>Corrigir provas, calcular notas, gerar relatórios, permitir a consulta por responsáveis, prestar suporte, cobrar pelos serviços, proteger a plataforma e cumprir obrigações legais.</p></section>
<section><h2>Natureza do serviço</h2><p>O Gabarito360 é uma ferramenta pedagógica para provas, gabaritos e respostas. Não é rede social e não guarda fotos ou vídeos de alunos. A imagem do cartão-resposta é mantida para conferir a correção.</p></section>
<section><h2>Crianças e adolescentes</h2><p>Tratamos esses dados no seu melhor interesse, usando apenas o necessário à atividade educacional. Não vendemos esses dados, não os usamos para publicidade direcionada e não criamos perfis de comportamento para fins comerciais.</p></section>
<section><h2>Compartilhamento</h2><p>Compartilhamos dados apenas com a instituição responsável e com fornecedores necessários, como hospedagem, envio de e-mail, proteção do site e pagamento. A lista atualizada está em <span class="campo-pendente">[LINK DOS SUBOPERADORES]</span>. Podemos compartilhar dados por exigência legal ou ordem de autoridade.</p></section>
<section><h2>Transferência internacional</h2><p>Alguns fornecedores podem tratar dados fora do Brasil. Nesses casos, adotamos os mecanismos previstos na legislação.</p></section>
<section><h2>Retenção</h2><p>Mantemos os dados pelo tempo necessário às finalidades, ao contrato e às obrigações legais. A tabela de prazos está em <span class="campo-pendente">[LINK DA TABELA DE RETENÇÃO]</span>. Ao final, os dados são eliminados ou anonimizados.</p></section>
<section><h2>Segurança</h2><p>Usamos criptografia, controle de acesso, registro de operações, cópias de segurança e monitoramento. Nenhum sistema é totalmente imune a incidentes. Se um incidente puder causar risco ou dano relevante, comunicaremos a ANPD e os titulares conforme a norma aplicável.</p></section>
<section><h2>Seus direitos</h2><p>Você pode pedir confirmação do tratamento, acesso, correção, anonimização, bloqueio, eliminação, portabilidade, informação sobre compartilhamento e revisão de decisões automatizadas. Também pode revogar um consentimento dado. Pedidos sobre dados escolares devem ser feitos à instituição; podemos encaminhá-los pelo e-mail <span class="campo-pendente">[E-MAIL DO ENCARREGADO]</span>. Você também pode procurar a ANPD.</p></section>
<section><h2>Medição de desempenho</h2><p>Usamos Cloudflare Web Analytics para medir visitas e desempenho das páginas. Esse recurso não usa cookies ou armazenamento local para coletar as métricas.</p></section>
<section><h2>Fontes do site</h2><p>Utilizamos Google Fonts para carregar as fontes do site. Isso cria uma conexão do seu navegador com os servidores desse fornecedor.</p></section>
<section><h2>Cookies</h2><p>Usamos cookies necessários para login e segurança. Cookies opcionais só serão usados com sua escolha.</p></section>
</article>
@endsection
