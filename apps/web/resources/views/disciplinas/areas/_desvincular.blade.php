@php($c=session('confirmacao_area'))
@if($dados['gerir'] && $c && (int)$c['area_id']===(int)$a['id'] && $c['contexto']===\App\Support\ContextoWeb::id())
  @php($p=collect($vinculos)->firstWhere('id',(int)$c['dados']['vinculo_id']))
  @if($p && $p['ativo'])
  <dialog open class="dc1-confirmacao dc1-confirmacao-area" data-dc1-confirmacao-area data-dc1-origem="desvincular-{{ $p['id'] }}" aria-labelledby="area-confirmacao-titulo" aria-describedby="area-confirmacao-texto">
    <h2 id="area-confirmacao-titulo">Desvincular {{ $p['nome'] }} desta área?</h2>
    <p id="area-confirmacao-texto">Este vínculo foi usado em {{ $c['provas'] }} {{ (int)$c['provas']===1?'prova':'provas' }}. As provas, os gabaritos, as notas e os demais registros anteriores serão preservados. A partir da desvinculação, novos registros não usarão esta ligação entre a disciplina e esta área.</p>
    <form method="POST" action="{{ route('areas.desvincular',['id'=>$a['id']]) }}" data-dc1-envio>@csrf @include('disciplinas._retorno')@foreach(\Illuminate\Support\Arr::only($c['dados'],['versao','disciplina_id','disciplina_versao','vinculo_id']) as $nome=>$valor)<input type="hidden" name="{{ $nome }}" value="{{ $valor }}">@endforeach<input type="hidden" name="confirmacao" value="{{ $c['confirmacao'] }}"><div class="dc1-confirmacao-acoes"><a class="btn btn-secondary" data-dc1-cancelar-area autofocus href="{{ route('areas.show',['id'=>$a['id']]+$retorno) }}">Cancelar</a><button class="btn btn-primary" type="submit">Desvincular</button></div></form>
  </dialog>
  @endif
@endif
