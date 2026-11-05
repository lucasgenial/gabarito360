<details class="resultado-conceitos"><summary>Saiba mais sobre os percentuais</summary><p>Turma considera as correções concluídas da turma deste aluno. Geral considera todas as turmas vinculadas à mesma prova neste espaço, sem apresentar dados individuais de outros alunos. Cada questão considera somente os gabaritos aplicáveis, inclusive a língua escolhida. Respostas em branco entram na base; questões anuladas não têm percentual. A amostra aparece abaixo de cada indicador. Uma nova foto aguardando conferência não substitui o resultado vigente.</p></details>
<div class="res-folha resultado-docente">
  @foreach($folha as $questao)
    @php($situacao=in_array($questao['situacao']??null,['correta','incorreta','branco','anulada'],true)?$questao['situacao']:'nao_preservada')
    <div class="res-q {{ $situacao }}">
      <div class="resultado-questao-resposta"><b>{{ str_pad($questao['numero_questao'],2,'0',STR_PAD_LEFT) }}</b><span>
        @switch($situacao)
          @case('correta') acertou ({{ $questao['marcada'] }}) @break
          @case('incorreta') marcou <b>{{ $questao['marcada'] }}</b> · certa <b>{{ $questao['gabarito'] }}</b> @break
          @case('branco') {{ !empty($questao['ambigua'])?'marcação dupla/fraca':'em branco' }} · certa <b>{{ $questao['gabarito'] }}</b> @break
          @case('anulada') anulada @break
          @default referência não disponível
        @endswitch
      </span></div>
      <div class="resultado-percentuais">
        @foreach(['turma'=>'Turma','geral'=>'Geral'] as $grupo=>$rotulo)
          @php($indicador=$questao[$grupo]??null)
          <div><span>{{ $rotulo }}</span>
            @if($situacao==='anulada')<strong>—</strong><small>Questão anulada</small>
            @elseif(is_array($indicador)&&is_numeric($indicador['percentual']??null)&&($indicador['total']??0)>0)
              <strong>{{ number_format((float)$indicador['percentual'],1,',','.') }}%</strong><small>{{ $indicador['acertos'] }} de {{ $indicador['total'] }} correções</small>
            @else<strong>—</strong><small>Sem base disponível</small>@endif
          </div>
        @endforeach
      </div>
    </div>
  @endforeach
</div>
