<details class="resultado-conceitos"><summary>Saiba mais sobre os percentuais</summary><p>Turma considera as correções concluídas da turma deste aluno. Geral considera todas as turmas vinculadas à mesma prova neste espaço, sem apresentar dados individuais de outros alunos. Cada questão considera somente os gabaritos aplicáveis, inclusive a língua escolhida. Respostas em branco entram na base; questões anuladas não têm percentual. A amostra aparece abaixo de cada indicador. Uma nova foto aguardando conferência não substitui o resultado vigente.</p></details>
<x-tabela class="resultado-pedagogico" titulo="Questão por questão e percentuais de acerto">
  <table class="tabela-padrao">
    <caption class="sr-only">Respostas do aluno e percentuais de acerto da turma e da prova</caption>
    <thead><tr><th scope="col">Nº</th><th scope="col">Marcou</th><th scope="col">Certa</th><th scope="col">% acerto Turma</th><th scope="col">% acerto Geral</th></tr></thead>
    <tbody>
    @foreach($folha as $questao)
      <tr>
        <td class="tabela-principal" data-rotulo="Questão"><strong>{{ str_pad($questao['numero_questao'],2,'0',STR_PAD_LEFT) }}</strong></td>
        <td data-rotulo="Marcou"><div class="tabela-valor">{{ !empty($questao['ambigua']) ? 'Revisão pendente' : ($questao['situacao']==='anulada' ? 'Anulada' : ($questao['marcada'] ?? 'Em branco')) }}</div></td>
        <td data-rotulo="Certa"><div class="tabela-valor">{{ $questao['situacao']==='anulada' ? 'Anulada' : ($questao['gabarito'] ?? 'Não disponível') }}</div></td>
        @foreach(['turma'=>'% acerto Turma','geral'=>'% acerto Geral'] as $grupo=>$rotulo)
          @php($indicador=$questao[$grupo]??null)
          <td data-rotulo="{{ $rotulo }}"><div class="tabela-valor">
            @if($questao['situacao']==='anulada')<span>—</span><span class="resultado-amostra">Questão anulada</span>
            @elseif(is_array($indicador) && is_numeric($indicador['percentual']??null) && ($indicador['total']??0)>0)
              <strong>{{ number_format((float)$indicador['percentual'],1,',','.') }}%</strong><span class="resultado-amostra">{{ $indicador['acertos'] }} de {{ $indicador['total'] }} correções</span>
            @else<span>—</span><span class="resultado-amostra">Sem base disponível</span>@endif
          </div></td>
        @endforeach
      </tr>
    @endforeach
    </tbody>
  </table>
</x-tabela>
