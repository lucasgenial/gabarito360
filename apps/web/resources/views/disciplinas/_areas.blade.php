@php($selecionadas=array_map('intval',old('area_ids',array_column($disciplina['areas']??[],'id'))))
@php($errosAreas=array_merge($errors->get('area_ids'),array_merge([], ...array_values($errors->get('area_ids.*')))))
<fieldset class="dc1-areas" tabindex="-1" aria-describedby="areas-ajuda{{ count($errosAreas)?' dc1-erro-area_ids':'' }}" @if(count($errosAreas)) aria-invalid="true" @endif>
  <legend>Áreas do conhecimento <span class="field-help">(opcional)</span></legend>
  <p class="dc1-texto" id="areas-ajuda">Selecione as áreas que organizam esta disciplina. Você pode escolher mais de uma.</p>
  <input type="hidden" name="area_selecao" value="1">
  @if(count($areas))
  <div class="dc1-areas-opcoes">
    @foreach($areas as $area)<label class="dc1-area-opcao" for="disciplina-area-{{ $area['id'] }}"><input type="checkbox" id="disciplina-area-{{ $area['id'] }}" name="area_ids[]" value="{{ $area['id'] }}" @checked(in_array((int)$area['id'],$selecionadas,true))><span>{{ $area['nome'] }}@if($area['sigla']) <span class="field-help">({{ $area['sigla'] }})</span>@endif @unless($area['ativo'])<span class="badge badge-info">Inativa</span>@endunless</span></label>@endforeach
  </div>
  @else<p class="dc1-texto">Nenhuma área cadastrada. Você pode salvar a disciplina sem uma área e associá-la depois.</p>@endif
  @if(count($errosAreas))<p class="field-error" id="dc1-erro-area_ids">{{ implode(' ',array_unique($errosAreas)) }}</p>@endif
  <p class="field-help">A coordenação gerencia as áreas em Disciplinas → Áreas do conhecimento. Salve este formulário antes de sair.</p>
</fieldset>
