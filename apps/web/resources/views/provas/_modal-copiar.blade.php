<dialog id="cp1-modal" class="cp1-modal" aria-labelledby="cp1-modal-titulo" aria-describedby="cp1-modal-resumo">
  <form method="POST" class="cp1-formulario" id="cp1-formulario">
    @csrf
    <h2 id="cp1-modal-titulo">Copiar prova</h2>
    <p>Origem: <strong id="cp1-modal-origem"></strong></p>
    <p id="cp1-modal-resumo">Será criado um rascunho independente com as configurações e o gabarito. A original permanece igual.</p>
    <p>Turmas, alunos, cartões, respostas dos alunos, notas e datas de aplicação não serão copiados. Depois, revise o gabarito e escolha as turmas.</p>
    <input type="hidden" name="chave" id="cp1-chave">
    <input type="hidden" name="origem_id" id="cp1-origem-id">
    <div class="field">
      <label for="cp1-novo-titulo">Nome da nova prova *</label>
      <input class="input" id="cp1-novo-titulo" name="titulo" required maxlength="255" autofocus>
      @error('titulo')<span class="field-error">{{ $message }}</span>@enderror
      @error('chave')<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div class="cp1-acoes">
      <button type="submit" class="btn btn-primary">Criar cópia</button>
      <button type="button" class="btn btn-secondary" id="cp1-cancelar">Cancelar</button>
    </div>
  </form>
</dialog>
@push('scripts')
<script>
(function () {
  var modal = document.getElementById('cp1-modal'), formulario = document.getElementById('cp1-formulario');
  if (!modal || !formulario) return;
  var tentativa = @json(old('origem_id')), tituloAnterior = @json(old('titulo')), chaveAnterior = @json(old('chave'));
  function abrir(botao, recuperar) {
    formulario.action = botao.dataset.acao;
    document.getElementById('cp1-modal-origem').textContent = botao.dataset.titulo;
    document.getElementById('cp1-origem-id').value = botao.dataset.origem;
    document.getElementById('cp1-chave').value = recuperar && chaveAnterior ? chaveAnterior : botao.dataset.chave;
    document.getElementById('cp1-novo-titulo').value = recuperar && tituloAnterior !== null ? tituloAnterior : Array.from(botao.dataset.titulo).slice(0,240).join('') + ' — cópia';
    modal.showModal();
  }
  document.querySelectorAll('[data-copiar-prova]').forEach(function (botao) {
    botao.addEventListener('click', function () { abrir(botao, String(tentativa) === botao.dataset.origem); });
    if (tentativa !== null && String(tentativa) === botao.dataset.origem) abrir(botao,true);
  });
  document.getElementById('cp1-cancelar').addEventListener('click',function () { modal.close(); });
})();
</script>
@endpush
