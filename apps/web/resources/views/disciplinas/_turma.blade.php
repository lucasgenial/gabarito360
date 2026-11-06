@if($dc1Visivel ?? false)
@push('styles')<link rel="stylesheet" href="{{ asset('css/disciplinas.css') }}?v={{ @filemtime(public_path('css/disciplinas.css')) }}">@endpush
<section class="dc1-stack" id="turma-disciplinas" aria-labelledby="turma-disciplinas-titulo">
<div class="card"><div class="card-pad"><h2 id="turma-disciplinas-titulo">Disciplinas e professores</h2>
@if($dc1Erro)<x-aviso tipo="erro">Não foi possível carregar a organização das disciplinas. Atualize a página antes de fazer alterações.</x-aviso>@else
@unless($dc1Dados['gerir']??false)<p class="field-help">Consulte as disciplinas e as atribuições autorizadas para seu perfil.</p>@endunless
@endif
@unless($dc1Erro)@include('disciplinas._atribuir-turma')@endunless
</div>
@unless($dc1Erro)
<x-tabela :interna="true" :integrada="true" titulo="Disciplinas desta turma"><table class="tabela-padrao"><caption class="sr-only">Disciplinas e professores da turma</caption><thead><tr><th scope="col">Disciplina</th><th scope="col">Professores</th><th scope="col">Situação</th><th scope="col">Ações</th></tr></thead><tbody>
@forelse($dc1Dados['componentes'] as $comp)
@php($emCurso=collect($comp['atribuicoes'])->where('estado_periodo','Em curso'))
@php($programadas=collect($comp['atribuicoes'])->where('estado_periodo','Programada'))
<tr><td class="tabela-principal" data-rotulo="Disciplina"><div class="tabela-valor">{{ $comp['nome'] }}</div>@if($comp['sigla'])<span class="dc1-periodo">{{ $comp['sigla'] }}</span>@endif</td><td data-rotulo="Professores">@forelse($emCurso as $a)<div>{{ $a['nome'] }}@unless($a['vinculo_vigente'])<span class="dc1-erro-vinculo"> · Vínculo indisponível</span>@endunless</div>@empty<span>Sem professor em curso</span>@endforelse @if($programadas->count())<span class="dc1-periodo">{{ $programadas->count() }} atribuição(ões) programada(s)</span>@endif</td><td data-rotulo="Situação">{{ !($comp['ativo']??true)?'Inativa nesta turma':(!$comp['disciplina_ativa']?'Inativa no catálogo':'Ativa nesta turma') }}</td><td class="tabela-acoes" data-rotulo="Ações"><div class="tabela-valor"><a class="btn btn-secondary btn-sm" href="{{ route('disciplinas.componente',['turma'=>$turma['id'],'componente'=>$comp['id']]) }}">{{ $dc1Dados['atribuir']?'Gerir atribuições':'Consultar atribuições' }}<span class="sr-only">: {{ $comp['nome'] }}</span></a></div></td></tr>
@empty<tr><td colspan="4"><div class="dc1-vazio"><h3>{{ \App\Support\Acesso::perfil()==='professor'?'Nenhuma atribuição registrada para você':'Nenhuma disciplina adicionada à turma' }}</h3><p>{{ $dc1Dados['gerir']?'Adicione uma disciplina do catálogo da escola. Depois, atribua os professores e os períodos de atuação.':'A coordenação organiza as disciplinas e as atribuições dos professores.' }}</p></div></td></tr>@endforelse
</tbody></table></x-tabela>
@if($dc1Dados['gerir']??false)<div class="card-pad"><details class="dc1-ajuda" @if(empty($dc1Dados['componentes'])||$errors->has('disciplina_id')) open @endif><summary>Adicionar disciplina à turma</summary>@include('disciplinas._catalogo-turma')</details></div>@endif
@endunless
</div></section>
@endif

@push('scripts')<script src="{{ asset('js/turma-atribuicao.js') }}" defer></script>@endpush
