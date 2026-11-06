@if($dc1Dados['atribuir']??false)
<section class="tu1-atribuicao formulario-escolar" aria-labelledby="atribuir-turma-titulo">
<h3 id="atribuir-turma-titulo">Vincular disciplina e professor</h3>
<p class="field-help">Escolha a disciplina desta turma e o professor que irá lecionar.</p>
@if($errors->any())<x-aviso tipo="erro"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></x-aviso>@endif
@if(count($dc1Dados['opcoes_atribuicao']??[]))
<form method="POST" action="{{ route('turmas.atribuir',$turma['id']) }}" data-turma-atribuicao>
@csrf <x-revisao-acesso />
<input type="hidden" name="chave" value="{{ old('chave',(string)\Illuminate\Support\Str::uuid()) }}">
<input type="hidden" name="versao" value="{{ old('versao') }}" data-atribuicao-versao>
<div class="form-row-2">
<div class="field"><label for="atribuicao-disciplina">Disciplina *</label><select class="select" id="atribuicao-disciplina" name="componente_id" required @error('componente_id') aria-invalid="true" aria-describedby="erro-componente_id" @enderror ><option value="">Selecione a disciplina</option>@foreach($dc1Dados['componentes'] as $c)@if($c['ativo']&&$c['disciplina_ativa'])<option value="{{ $c['id'] }}" data-versao="{{ $c['versao'] }}" @selected(old('componente_id')==$c['id'])>{{ $c['nome'] }}</option>@endif @endforeach</select><x-erro-campo nome="componente_id" /></div>
<div class="field"><label for="atribuicao-professor">Professor *</label><select class="select" id="atribuicao-professor" name="vinculo_id" required @error('vinculo_id') aria-invalid="true" aria-describedby="erro-vinculo_id" @enderror ><option value="">Selecione o professor</option>@foreach($dc1Dados['opcoes_atribuicao'] as $p)<option value="{{ $p['vinculo_id'] }}" data-componente="{{ $p['componente_id'] }}" @selected(old('vinculo_id')==$p['vinculo_id']&&old('componente_id')==$p['componente_id'])>{{ $p['nome'] }}</option>@endforeach</select><p class="field-help" data-atribuicao-vazio hidden>Nenhum professor elegível para esta disciplina. Confira a indicação e o acesso à turma.</p><x-erro-campo nome="vinculo_id" /></div>
<div class="field"><label for="atribuicao-inicio">Início *</label><input class="input" id="atribuicao-inicio" name="inicio_em" type="date" min="{{ now('America/Sao_Paulo')->toDateString() }}" value="{{ old('inicio_em',now('America/Sao_Paulo')->toDateString()) }}" required @error('inicio_em') aria-invalid="true" aria-describedby="erro-inicio_em" @enderror ><x-erro-campo nome="inicio_em" /></div>
<div class="field"><label for="atribuicao-fim">Encerramento (opcional)</label><input class="input" id="atribuicao-fim" name="fim_em" type="date" value="{{ old('fim_em') }}" @error('fim_em') aria-invalid="true" aria-describedby="erro-fim_em" @enderror ><x-erro-campo nome="fim_em" /></div>
</div>
<p class="field-help">Não é permitido sobrepor professores na mesma disciplina. Consulte as atribuições abaixo antes de confirmar.</p>
<div class="form-actions"><button class="btn btn-primary" type="submit">Vincular professor à disciplina</button></div>
</form>
@else<p>Nenhum professor elegível no momento. Adicione a disciplina à turma, confira os professores indicados e o acesso à turma.</p>@endif
<details class="dc1-ajuda"><summary>Saiba mais sobre os vínculos</summary><p>O professor precisa ter vínculo ativo na escola, indicação para a disciplina e acesso à turma. Use “Gerir atribuições” na disciplina para conferir esses requisitos e o histórico. O encerramento não inclui a data final: outro professor pode começar nessa data. Os resultados anteriores são preservados.</p></details>
</section>
@endif

@push('styles')<link rel="stylesheet" href="{{ asset('css/formulario-escolar.css') }}?v={{ filemtime(public_path('css/formulario-escolar.css')) }}">@endpush
