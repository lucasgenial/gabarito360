@extends('layouts.app')
@section('title',$disciplina?'Editar disciplina':'Adicionar disciplina')
@section('breadcrumb')<a href="{{ route('disciplinas.index') }}">Disciplinas</a><span class="sep">/</span><span>{{ $disciplina?'Editar':'Adicionar' }}</span>@endsection
@section('content')
<x-pagina :titulo="$disciplina?'Editar disciplina':'Adicionar disciplina'">
  <x-slot:sub>Catálogo da escola.</x-slot:sub>
  @include('disciplinas._voltar')
</x-pagina>
@include('disciplinas._base')
<div class="dc1-stack">
@if($disciplina)<x-orientacao assunto="alterações na disciplina"><p>Alterações valem para o catálogo atual. O histórico é preservado.</p></x-orientacao>@endif
@unless($disciplina)
<section class="card card-pad" aria-labelledby="origem-titulo">
  <h2 id="origem-titulo">Como deseja adicionar?</h2>
  <div class="dc1-escolha">
    <a class="btn btn-secondary" href="{{ route('disciplinas.create',['origem'=>'referencia']+$retorno) }}" @if(($f['origem']??'referencia')==='referencia') aria-current="page" @endif>Escolher referência nacional</a>
    <a class="btn btn-secondary" href="{{ route('disciplinas.create',['origem'=>'propria']+$retorno) }}" @if(($f['origem']??'')==='propria') aria-current="page" @endif>Criar disciplina própria</a>
  </div>
  @if(($f['origem']??'referencia')==='referencia')
    <x-orientacao assunto="referências nacionais"><p>Sugestões de nomes a partir da BNCC/MEC. A escola decide quais disciplinas oferece. A área da referência não associa automaticamente a disciplina às áreas da escola.</p></x-orientacao>
    @if($selecionada)<p><strong>Referência escolhida:</strong> {{ $selecionada['nome'] }}. Área da referência: {{ $selecionada['area'] }}</p><a href="{{ route('disciplinas.create',['origem'=>'referencia']+$retorno) }}">Escolher outra referência</a>
    @else
    <form method="GET" action="{{ route('disciplinas.create') }}" class="dc1-acoes"><input type="hidden" name="origem" value="referencia">@include('disciplinas._retorno')<div class="field"><label for="referencia-busca">Buscar referência pelo nome</label><input class="input" id="referencia-busca" type="search" name="busca" maxlength="100" value="{{ $f['busca']??'' }}"></div><button class="btn btn-secondary" type="submit">Buscar</button></form>
    <x-tabela titulo="Referências nacionais"><table class="tabela-padrao"><caption class="sr-only">Referências de disciplinas</caption><thead><tr><th scope="col">Referência</th><th scope="col">Área da referência</th><th scope="col">Ações</th></tr></thead><tbody>
    @forelse($referencias as $ref)<tr><td class="tabela-principal" data-rotulo="Referência"><div class="tabela-valor">{{ $ref['nome'] }}</div></td><td data-rotulo="Área da referência">{{ $ref['area'] }}</td><td class="tabela-acoes" data-rotulo="Ações"><div class="tabela-valor"><a class="btn btn-secondary btn-sm" href="{{ route('disciplinas.create',['origem'=>'referencia','referencia'=>$ref['chave']]+$retorno) }}">Escolher<span class="sr-only"> {{ $ref['nome'] }}</span></a></div></td></tr>@empty<tr><td colspan="3"><p class="dc1-vazio">Nenhuma referência encontrada. Tente outro nome ou crie uma disciplina própria.</p></td></tr>@endforelse
    </tbody></table></x-tabela>
    @endif
  @else<x-orientacao assunto="disciplinas próprias"><p>Use para componentes locais, eletivas, projetos, idiomas ou disciplinas técnicas. O cadastro fica restrito à escola.</p></x-orientacao>@endif
</section>
@endunless
@if($disciplina || $selecionada || ($f['origem']??'')==='propria')
<form method="POST" action="{{ $disciplina?route('disciplinas.update',$disciplina['id']):route('disciplinas.store') }}" class="card card-pad dc1-stack">
  @csrf @if($disciplina)@method('PUT')<input type="hidden" name="versao" value="{{ old('versao',$disciplina['versao']) }}">@endif
  @include('disciplinas._retorno')
  @if(!$disciplina && $selecionada)<input type="hidden" name="referencia" value="{{ $selecionada['chave'] }}">@endif
  <h2>Dados na escola</h2>
  <div class="dc1-grade">
    <div class="field"><label for="disciplina-nome">Nome da disciplina <span aria-hidden="true">*</span></label><input id="disciplina-nome" class="input" name="nome" required maxlength="100" value="{{ old('nome',$disciplina['nome']??$selecionada['nome']??'') }}" @error('nome') aria-invalid="true" aria-describedby="dc1-erro-nome" @enderror>@include('disciplinas._erro',['campo'=>'nome'])</div>
    <div class="field"><label for="disciplina-sigla">Sigla <span class="field-help">(opcional)</span></label><input id="disciplina-sigla" class="input" name="sigla" maxlength="20" value="{{ old('sigla',$disciplina['sigla']??'') }}" placeholder="Ex.: MAT" @error('sigla') aria-invalid="true" aria-describedby="dc1-erro-sigla" @enderror>@include('disciplinas._erro',['campo'=>'sigla'])</div>
    <div class="field"><label for="disciplina-ativo">Situação</label><select id="disciplina-ativo" class="select" name="ativo" @error('ativo') aria-invalid="true" aria-describedby="dc1-erro-ativo" @enderror><option value="1" @selected((string)old('ativo',$disciplina['ativo']??1)==='1')>Ativa</option><option value="0" @selected((string)old('ativo',$disciplina['ativo']??1)==='0')>Inativa</option></select>@include('disciplinas._erro',['campo'=>'ativo'])<p class="field-help">Histórico preservado.</p><x-orientacao assunto="situação da disciplina"><p>Para inativar, encerre ou cancele as atribuições abertas.</p></x-orientacao></div>
  </div>
  <div class="dc1-acoes"><button class="btn btn-primary" type="submit" @disabled(session('conflito_dc1'))>{{ $disciplina?'Salvar alterações':'Adicionar disciplina' }}</button><a class="btn btn-ghost" href="{{ $disciplina?route('disciplinas.show',['id'=>$disciplina['id']]+$retorno):route('disciplinas.index',$retorno) }}">Cancelar</a></div>
</form>
@endif
</div>
@endsection
