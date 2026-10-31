{{-- Prévia sem rota pública. A publicação depende da aprovação do texto jurídico. --}}
@extends('layouts.public')
@section('title', 'Termos de uso — Prévia para revisão')
@section('robots', 'noindex, nofollow')
@section('content')
<article class="documento-legal" aria-labelledby="titulo-documento">
<header><h1 id="titulo-documento">Termos de uso</h1><p>Última atualização: <span class="campo-pendente">[DATA]</span></p></header>
<x-aviso tipo="atencao">Prévia para conferência. Os campos entre colchetes serão preenchidos antes da publicação.</x-aviso>
<section><h2>Identificação</h2><p>O Gabarito360 é operado por <span class="campo-pendente">[RAZÃO SOCIAL]</span>, CNPJ <span class="campo-pendente">[CNPJ]</span>, com sede em <span class="campo-pendente">[ENDEREÇO]</span>.</p></section>
<section><h2>O serviço</h2><p>O Gabarito360 é uma ferramenta pedagógica para provas, gabaritos, respostas e relatórios educacionais. A imagem do cartão-resposta é mantida para conferir a correção, com possibilidade de revisão humana das leituras automáticas.</p></section>
<section><h2>Dados escolares e responsabilidades</h2><p>A instituição ou o professor responsável define as finalidades e as instruções de tratamento dos dados escolares. O Gabarito360 atua como operador desses dados e como controlador dos dados de conta, cobrança, segurança e Fale Conosco.</p><p>O contrato com o cliente deve identificar os papéis de controlador e operador, as instruções de tratamento, as finalidades e a base legal definida pelo controlador.</p></section>
<section><h2>Proteção de dados</h2><p>As condições contratuais devem contemplar medidas de segurança, lista de suboperadores e aviso de mudanças, apoio em pedidos de titulares e incidentes, auditoria e prestação de contas.</p><p>O encarregado pelo tratamento de dados pessoais será identificado como <span class="campo-pendente">[NOME DO ENCARREGADO]</span>, pelo e-mail <span class="campo-pendente">[E-MAIL DO ENCARREGADO]</span>.</p></section>
<section><h2>Dados de alunos e responsáveis</h2><p>O cadastro de alunos do professor autônomo fica limitado a nome, turma e identificação simples, sem portal dos responsáveis. O cadastro completo e o portal são exclusivos de escolas e perfis superiores.</p><p>O sistema não guarda fotos ou vídeos de alunos. O aluno é representado por avatar; a imagem do cartão-resposta é o documento da correção.</p></section>
<section><h2>Saída do professor e cópia de dados</h2><p>Notas identificadas só seguem com o professor se o contrato da escola permitir, com base legal e finalidade legítima. Sem essa previsão, a cópia se limita a provas, materiais e notas anonimizadas.</p></section>
<section><h2>Encerramento do contrato</h2><p>O contrato deve prever exportação e eliminação dos dados ao final da relação, com o prazo <span class="campo-pendente">[PRAZO DE EXPORTAÇÃO E ELIMINAÇÃO]</span>. A instituição deve poder exportar os dados e solicitar a eliminação, observados os prazos contratuais e legais.</p></section>
<section><h2>Documentos e resultados</h2><p>Documentos impressos ou em PDF podem conter dados pessoais do estudante. Quem os gera deve guardá-los com segurança e não publicá-los nem repassá-los a terceiros sem base legal.</p></section>
</article>
@endsection
