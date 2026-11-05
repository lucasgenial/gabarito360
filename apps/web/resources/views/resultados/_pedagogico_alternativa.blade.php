<details class="resultado-conceitos"><summary>Saiba mais sobre os percentuais</summary><p>Turma considera as correções concluídas da turma deste aluno. Geral considera todas as turmas vinculadas à mesma prova neste espaço, sem apresentar dados individuais de outros alunos. A base considera os gabaritos aplicáveis, inclusive a língua escolhida. Respostas em branco entram na base; questões anuladas não têm percentual. Nos parênteses, o primeiro número é o de acertos e o segundo é o de correções consideradas: (2/3) significa 2 acertos em 3 correções.</p></details>
<div class="resultado-modelo2">
  <ol class="resultado-modelo2-lista" aria-label="Questão por questão com indicadores separados">
    @foreach($folha as $questao)
      @php($situacao=in_array($questao['situacao']??null,['correta','incorreta','branco','anulada'],true)?$questao['situacao']:'nao_preservada')
      <li class="resultado-modelo2-linha">
        <div class="res-q {{ $situacao }} resultado-modelo2-resposta">
          <b>{{ str_pad($questao['numero_questao'],2,'0',STR_PAD_LEFT) }}</b><span>
            @switch($situacao)
              @case('correta') acertou ({{ $questao['marcada'] }}) @break
              @case('incorreta') marcou <b>{{ $questao['marcada'] }}</b> · certa <b>{{ $questao['gabarito'] }}</b> @break
              @case('branco') {{ !empty($questao['ambigua'])?'marcação dupla/fraca':'em branco' }} · certa <b>{{ $questao['gabarito'] }}</b> @break
              @case('anulada') anulada @break
              @default referência não disponível
            @endswitch
          </span>
        </div>
        <dl class="resultado-modelo2-indicadores" aria-label="Percentuais de acerto da questão {{ $questao['numero_questao'] }}">
          @foreach(['turma'=>'Turma','geral'=>'Geral'] as $grupo=>$rotulo)
            @php($indicador=$questao[$grupo]??null)
            <div><dt>{{ $rotulo }}</dt><dd>
              @if($situacao==='anulada')<strong>—</strong><span class="resultado-modelo2-sr">Questão anulada</span>
              @elseif(is_array($indicador)&&is_numeric($indicador['percentual']??null)&&($indicador['total']??0)>0)
                <strong>{{ number_format((float)$indicador['percentual'],1,',','.') }}%</strong><span class="resultado-modelo2-amostra" aria-hidden="true">({{ $indicador['acertos'] }}/{{ $indicador['total'] }})</span><span class="resultado-modelo2-sr">{{ $indicador['acertos'] }} acertos em {{ $indicador['total'] }} correções</span>
              @else<strong>—</strong><span class="resultado-modelo2-amostra">sem base</span>@endif
            </dd></div>
          @endforeach
        </dl>
      </li>
    @endforeach
  </ol>
</div>
