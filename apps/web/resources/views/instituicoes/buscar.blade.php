@extends('layouts.app')
@section('tela','tela-instituicoes-busca')
@section('title','Buscar instituição')
@push('styles')<link rel="stylesheet" href="@ativo('css/instituicoes-busca.css')">@endpush
@section('breadcrumb')<a href="{{ route('instituicoes.index') }}">Minhas instituições</a><span class="sep">/</span><span>Buscar instituição</span>@endsection
@section('content')
<x-pagina titulo="Buscar instituição"><x-slot:sub>Encontre uma instituição para adicionar à sua lista pessoal.</x-slot:sub></x-pagina>
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif
@if($errors->any())<section class="busca-erros" role="alert"><h2>Confira os filtros para continuar</h2><ul>@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></section>@endif
<form method="GET" action="{{ route('instituicoes.buscar') }}" class="card busca-formulario" id="catalogo-busca" data-municipios="{{ route('instituicoes.municipios',[],false) }}">
  <nav class="busca-percurso" aria-label="Como buscar a instituição">
    <a href="{{ route('instituicoes.buscar',['modo'=>'nome']) }}" @if($modo==='nome') aria-current="page" @endif>Por nome ou localização</a>
    <a href="{{ route('instituicoes.buscar',['modo'=>'codigo']) }}" @if($modo==='codigo') aria-current="page" @endif>Por código</a>
  </nav>
  <input type="hidden" name="modo" value="{{ $modo }}">
  @if($modo==='codigo')
    <div class="busca-principal">@include('instituicoes._busca-codigo',['obrigatorio'=>true])<p class="busca-ajuda">Informe o código e sua origem. Você pode buscar em todo o Brasil sem escolher uma UF.</p></div>
    <details class="busca-refinamento" @if(!empty($f['busca']) || !empty($f['uf']) || !empty($f['municipio_ibge']) || !empty($f['tipo'])) open @endif><summary>Restringir também por nome, localização ou tipo</summary>@include('instituicoes._busca-localizacao')</details>
  @else
    @include('instituicoes._busca-localizacao')
    @if(!empty($f['namespace']) || !empty($f['codigo']))<details class="busca-refinamento" open><summary>Código aplicado nesta busca</summary>@include('instituicoes._busca-codigo',['obrigatorio'=>false])</details>@endif
  @endif
  <details class="busca-refinamento" @if(!empty($f['dependencia']) || !empty($f['situacao']) || $errors->has('dependencia') || $errors->has('situacao')) open @endif>
    <summary>Mais filtros</summary><div class="busca-campos">
      <div class="busca-campo"><label for="cat-dependencia">Dependência administrativa</label><select class="select" id="cat-dependencia" name="dependencia" aria-invalid="{{ $errors->has('dependencia')?'true':'false' }}" aria-describedby="cat-erro-dependencia"><option value="">Todas</option>@foreach(['federal'=>'Federal','estadual'=>'Estadual','municipal'=>'Municipal','privada'=>'Privada','especial'=>'Especial'] as $k=>$n)<option value="{{ $k }}" @selected(($f['dependencia']??'')===$k)>{{ $n }}</option>@endforeach</select>@include('instituicoes._busca-erro',['campo'=>'dependencia'])</div>
      <div class="busca-campo"><label for="cat-situacao">Situação da instituição</label><select class="select" id="cat-situacao" name="situacao" aria-invalid="{{ $errors->has('situacao')?'true':'false' }}" aria-describedby="cat-erro-situacao"><option value="">Atuais no catálogo</option><option value="todas" @selected(($f['situacao']??'')==='todas')>Incluir paralisadas e encerradas</option></select>@include('instituicoes._busca-erro',['campo'=>'situacao'])<p class="busca-ajuda">Instituições de ensino superior podem não informar a situação na fonte.</p></div>
    </div>
  </details>
  <div class="busca-acoes"><button class="btn btn-primary" type="submit" name="pesquisar" value="1">Buscar instituições</button><a class="btn btn-ghost" href="{{ route('instituicoes.buscar',['modo'=>$modo]) }}">Limpar filtros</a></div>
</form>
<details class="busca-sobre"><summary>Sobre o catálogo e esta seleção</summary><p>O catálogo reúne escolas, sedes de instituições de ensino superior e cadastros conferidos pela equipe. Não representa todos os campi e polos. Adicionar uma referência à sua lista pessoal não confirma vínculo profissional nem libera dados da instituição.</p></details>
<p id="cat-resultados-status" role="status" aria-live="polite"></p>
@if($dados!==null)
<section id="cat-resultados" aria-labelledby="cat-resultados-titulo" class="busca-resultados">
  @php $total=(int)$dados['paginacao']['total']; @endphp
  <h2 id="cat-resultados-titulo">{{ number_format($total,0,',','.') }} {{ $total===1?'instituição encontrada':'instituições encontradas' }}</h2>
  <div class="busca-lista">
  @forelse($dados['itens'] as $e)
    <article class="card busca-resultado"><div class="busca-resultado-conteudo">
      <h3>{{ $e['nome'] }}{{ !empty($e['sigla'])?' · '.$e['sigla']:'' }}</h3>
      <p>{{ $e['municipio_nome'] }} — {{ $e['uf'] }} <span aria-hidden="true">·</span> {{ $tipos[$e['tipo']]??$e['tipo'] }}</p>
      <p class="busca-origem">{{ ['inep_escola'=>'INEP da escola','inep_ies'=>'INEP da IES','plataforma'=>'Código da plataforma'][$e['namespace']]??'Código' }}: <strong>{{ $e['codigo'] }}</strong> <span aria-hidden="true">·</span> {{ ['basica'=>'Censo Escolar','superior'=>'Censo Superior','manual'=>'Cadastro da plataforma'][$e['fonte']]??$e['fonte'] }} {{ $e['ano'] }}</p>
      <p class="busca-origem">{{ $e['situacao_rotulo'] }}@if($e['natureza']==='sede_administrativa') <span aria-hidden="true">·</span> Sede administrativa da IES @endif @if(!empty($e['dependencia'])) <span aria-hidden="true">·</span> {{ ['federal'=>'Federal','estadual'=>'Estadual','municipal'=>'Municipal','privada'=>'Privada','especial'=>'Especial'][$e['dependencia']]??'Dependência não informada' }}@endif</p>
    </div><a class="btn btn-secondary" href="{{ route('instituicoes.conferir',['unidade'=>$e['unidade_id'],'edicao_id'=>$e['edicao_id']]) }}">Conferir instituição</a></article>
  @empty
    <section class="card busca-vazio"><h3>Nenhuma instituição corresponde a estes filtros</h3><p>Confira a localização ou tente outro nome. Se tiver o código INEP, use a busca por código.</p>@if(\App\Support\Acesso::pode('catalogo.solicitar'))<p>Se a instituição ainda não estiver no catálogo, a equipe pode conferir e cadastrá-la.</p><a class="btn btn-secondary" href="{{ route('catalogo-pedidos.novo') }}">Solicitar cadastro ao suporte</a>@else<p>Para instituições ausentes, consulte o suporte.</p>@endif</section>
  @endforelse
  </div>@include('instituicoes._paginas',['rota'=>'instituicoes.buscar','f'=>$f+['modo'=>$modo]])
</section>
@elseif(!$errors->any())<section class="busca-inicio"><h2>Qual instituição você procura?</h2><p>Busque pelo nome ou escolha a localização para ver as instituições da região.</p></section>@endif
@endsection
@push('scripts')<script src="@ativo('js/catalogo-instituicoes.js')"></script>@endpush
