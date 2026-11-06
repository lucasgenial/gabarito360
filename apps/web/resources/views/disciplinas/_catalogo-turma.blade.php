<details class="tu1-ajuda"><summary>Saiba mais sobre disciplinas e professores</summary><p>Adicione uma disciplina do catálogo à turma. Em seguida, abra “Gerir atribuições” para escolher professor, atuação e período. O professor precisa ter vínculo vigente, indicação na disciplina e acesso à turma; essas autorizações são conferidas separadamente.</p></details>
@if($catalogoErro??false)
  <x-aviso tipo="erro">Não foi possível consultar o catálogo. Atualize a página antes de adicionar uma disciplina. As disciplinas já vinculadas continuam abaixo.</x-aviso>
@else
<div class="tu1-catalogo">
  <form method="GET" action="{{ route('turmas.show',$turma['id']) }}" class="tu1-acoes">
    <input type="hidden" name="secao" value="disciplinas">
    <div class="field"><label for="catalogo-busca">Buscar disciplina no catálogo da escola</label><input class="input" id="catalogo-busca" type="search" name="catalogo_busca" maxlength="100" value="{{ $catalogoBusca??'' }}" placeholder="Nome ou sigla"></div>
    <button class="btn btn-secondary" type="submit">Buscar</button>
    @if(!empty($catalogoBusca))<a class="btn btn-ghost" href="{{ route('turmas.show',['id'=>$turma['id'],'secao'=>'disciplinas']) }}">Limpar busca</a>@endif
  </form>
  @php($disponiveis=collect($catalogo??[])->reject(fn($d)=>collect($dc1Dados['componentes']??[])->contains('disciplina_id',$d['id'])))
  @if($disponiveis->isNotEmpty())
  <form method="POST" action="{{ route('disciplinas.adicionar',$turma['id']) }}" class="tu1-acoes">
    @csrf <x-revisao-acesso />
    <div class="field"><label for="disciplina-da-turma">Disciplina do catálogo da escola</label><select class="select" name="disciplina_id" id="disciplina-da-turma" required aria-describedby="{{ $errors->has('disciplina_id')?'catalogo-pagina catalogo-erro-disciplina':'catalogo-pagina' }}" @error('disciplina_id') aria-invalid="true" @enderror><option value="">Selecione uma disciplina</option>
    @foreach($catalogo as $d)
      @php($adicionada=collect($dc1Dados['componentes']??[])->contains('disciplina_id',$d['id']))
      <option value="{{ $d['id'] }}" @disabled($adicionada) @selected(!$adicionada && (string)old('disciplina_id')===(string)$d['id'])>{{ $d['nome'] }}{{ $adicionada?' — Já nesta turma':'' }}</option>
    @endforeach
    </select>@error('disciplina_id')<p class="field-help" role="alert" id="catalogo-erro-disciplina">{{ $message }}</p>@enderror</div>
    <button class="btn btn-primary" type="submit" @disabled(session('conflito_dc1'))>Adicionar à turma</button>
  </form>
  @elseif(count($catalogo??[]))<p class="field-help">As disciplinas desta página já estão na turma. Consulte as atribuições abaixo ou busque outra disciplina.</p>
  @else<p class="field-help">{{ !empty($catalogoBusca)?'Nenhuma disciplina corresponde à busca.':'Nenhuma disciplina ativa disponível no catálogo da escola.' }}</p>@endif
  <nav class="dc1-paginas" aria-label="Páginas do catálogo da escola" id="catalogo-pagina">
    <span>{{ $catalogoMeta['total']??count($catalogo??[]) }} resultado(s) · Página {{ $catalogoMeta['pagina']??1 }} de {{ $catalogoMeta['paginas']??1 }}</span>
    @if(($catalogoMeta['pagina']??1)>1)<a class="btn btn-secondary" href="{{ route('turmas.show',['id'=>$turma['id'],'secao'=>'disciplinas','catalogo_busca'=>$catalogoBusca??'','catalogo_pagina'=>$catalogoMeta['pagina']-1]) }}">Anterior</a>@endif
    @if(($catalogoMeta['pagina']??1)<($catalogoMeta['paginas']??1))<a class="btn btn-secondary" href="{{ route('turmas.show',['id'=>$turma['id'],'secao'=>'disciplinas','catalogo_busca'=>$catalogoBusca??'','catalogo_pagina'=>$catalogoMeta['pagina']+1]) }}">Próxima</a>@endif
  </nav>
</div>
@endif
<div class="tu1-acoes"><a class="btn btn-secondary" href="{{ route('disciplinas.create',['retorno_turma'=>$turma['id']]) }}">Cadastrar disciplina no catálogo</a></div>
