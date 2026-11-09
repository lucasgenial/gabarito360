@if($dc1Dados['atribuir']??false)
<section class="tu1-atribuicao formulario-escolar" aria-labelledby="atribuir-turma-titulo">
<h3 id="atribuir-turma-titulo">Vincular disciplina e professor</h3>
<p class="field-help">Escolha a disciplina disponível e o professor da escola.</p>
@if($errors->any())<x-aviso tipo="erro"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></x-aviso>@endif
@if($opcoesDocentesErro??false)<x-aviso tipo="erro">Não foi possível consultar as opções. Atualize a página antes de criar o vínculo.</x-aviso>
@elseif(empty($opcoesDocentes['disciplinas']))<x-aviso tipo="info">Nenhuma disciplina disponível. Confira o catálogo ativo e as atribuições atuais ou programadas abaixo.</x-aviso>
@elseif(empty($opcoesDocentes['professores']))<x-aviso tipo="info">Nenhum professor disponível com vínculo aceito e vigente nesta escola. O próprio acesso do responsável pela ação não pode ser ampliado por este formulário.</x-aviso>
@else
<form method="POST" action="{{ route('turmas.atribuir',$turma['id']) }}" data-turma-atribuicao data-turma-confirmar="Turma {{ $turma['nome'] }}. Este vínculo atribui a disciplina ao professor a partir de hoje, indica sua atuação na disciplina e concede acesso a esta turma. Se necessário, adiciona a disciplina à turma. O histórico existente será preservado.">
@csrf <x-revisao-acesso /><input type="hidden" name="chave_idempotencia" value="{{ old('chave_idempotencia',(string)\Illuminate\Support\Str::uuid()) }}">
<div class="form-row-2">
<div class="field"><label for="atribuicao-disciplina">Disciplina *</label><select class="select" id="atribuicao-disciplina" name="disciplina_id" required @error('disciplina_id') aria-invalid="true" aria-describedby="erro-disciplina_id" @enderror><option value="">Selecione a disciplina</option>@foreach($opcoesDocentes['disciplinas'] as $d)<option value="{{ $d['id'] }}" @selected(old('disciplina_id')==$d['id'])>{{ $d['nome'] }}</option>@endforeach</select><x-erro-campo nome="disciplina_id" /></div>
<div class="field"><label for="atribuicao-professor">Professor *</label><select class="select" id="atribuicao-professor" name="professor_vinculo_id" required @error('professor_vinculo_id') aria-invalid="true" aria-describedby="erro-professor_vinculo_id" @enderror><option value="">Selecione o professor</option>@foreach($opcoesDocentes['professores'] as $p)<option value="{{ $p['vinculo_id'] }}" @selected(old('professor_vinculo_id')==$p['vinculo_id'])>{{ $p['nome'] }}</option>@endforeach</select><x-erro-campo nome="professor_vinculo_id" /></div>
</div><div class="form-actions"><button class="btn btn-primary" type="submit"><x-icone nome="adicionar" /> Vincular professor</button></div>
</form>
@endif
<details class="dc1-ajuda"><summary>Saiba mais sobre os vínculos</summary><p>O vínculo começa hoje e respeita o prazo de acesso do professor. A confirmação reúne a disciplina, a indicação do professor e o acesso à turma em uma única operação. Disciplinas com professor atual ou programado não aparecem como disponíveis. O mesmo professor pode lecionar disciplinas diferentes. Consulte abaixo os períodos e o histórico.</p></details>
</section>
@endif

