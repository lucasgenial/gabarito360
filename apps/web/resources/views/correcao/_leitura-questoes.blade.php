<h3>Questões desta leitura</h3>
@php($naoAplicada=($dados['estado']??null)==='aguardando_confirmacao')
@if($naoAplicada)<p class="page-sub">Não aplicada: as cores abaixo se referem à leitura automática desta foto. A correção vigente foi mantida.</p>@endif
<div class="res-folha leitura-questoes">
  @forelse($dados['linhas']??[] as $linha)
    @php
      $referencia=!empty($linha['referencia_preservada']);
      $estadoQuestao=$naoAplicada?($linha['leitura_situacao']??null):($linha['situacao']??null);
      $classe=$referencia&&in_array($estadoQuestao,['correta','incorreta','branco','anulada'],true)?$estadoQuestao:'nao_preservada';
    @endphp
    <div class="res-q {{ $classe }}">
      <div class="leitura-questao-cabecalho"><b>{{ str_pad($linha['numero_questao'],2,'0',STR_PAD_LEFT) }}</b>
        <span>{{ ['correta'=>'Correta','incorreta'=>'Incorreta','branco'=>'Em branco ou pendente de revisão','anulada'=>'Anulada','nao_preservada'=>'Referência histórica não preservada'][$classe] }}</span>
      </div>
      <dl class="leitura-comparacao">
        <div><dt>Leitura automática</dt><dd>{{ filled($linha['leitura']??null)?$linha['leitura']:'Não preservada' }}</dd></div>
        <div><dt>Resposta registrada</dt><dd>{{ $naoAplicada?'Não aplicada':(!empty($linha['resposta_registrada'])&&filled($linha['resposta']??null)?$linha['resposta']:'Não preservada') }}</dd></div>
        @if($referencia&&filled($linha['gabarito']??null))<div><dt>Gabarito preservado</dt><dd>{{ $linha['gabarito'] }}</dd></div>@endif
      </dl>
      @if(!empty($linha['modificada']))<p class="leitura-intervencao"><strong>Resposta modificada</strong></p>
      @elseif(!empty($linha['intervencao_humana']))<p class="leitura-intervencao"><strong>Intervenção humana registrada</strong></p>@endif
      <p class="leitura-observacao">{{ filled($linha['observacao']??null)?$linha['observacao']:'Sem registro preservado de intervenção.' }}</p>
    </div>
  @empty
    <p class="page-sub">Os dados por questão não estão disponíveis nesta leitura. Isso não indica ausência de marcação ou de intervenção humana.</p>
  @endforelse
</div>
