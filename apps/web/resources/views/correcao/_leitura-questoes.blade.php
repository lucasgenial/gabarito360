<h3>Questões desta leitura</h3>
<x-tabela class="tentativa-lista leitura-questoes" titulo="Leitura automática e resposta registrada">
  <table class="tabela-padrao">
    <caption class="sr-only">Leitura automática, resposta registrada e intervenções por questão</caption>
    <thead><tr><th scope="col">Questão</th><th scope="col">Leitura automática</th><th scope="col">Resposta registrada</th><th scope="col">Observação</th></tr></thead>
    <tbody>
    @forelse($dados['linhas'] ?? [] as $linha)
      <tr>
        <td class="tabela-principal" data-rotulo="Questão"><strong>{{ $linha['numero_questao'] }}</strong></td>
        <td data-rotulo="Leitura automática"><div class="tabela-valor">{{ filled($linha['leitura'] ?? null) ? $linha['leitura'] : 'Não preservada' }}</div></td>
        <td data-rotulo="Resposta registrada"><div class="tabela-valor">{{ filled($linha['resposta'] ?? null) ? $linha['resposta'] : 'Não preservada' }}</div></td>
        <td data-rotulo="Observação"><div class="tabela-valor leitura-observacao">{{ filled($linha['observacao'] ?? null) ? $linha['observacao'] : 'Sem registro preservado de intervenção.' }}</div></td>
      </tr>
    @empty
      <tr><td colspan="4">Os dados por questão não estão disponíveis nesta leitura. Isso não indica ausência de marcação ou de intervenção humana.</td></tr>
    @endforelse
    </tbody>
  </table>
</x-tabela>
