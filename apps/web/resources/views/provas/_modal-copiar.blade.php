<dialog id="cp1-modal" class="cp1-modal" aria-labelledby="cp1-modal-titulo" aria-describedby="cp1-modal-resumo">
  <form method="POST" class="cp1-formulario" id="cp1-formulario">
    @csrf <x-revisao-acesso />
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
@php($g360Config = ['tentativa'=>old('origem_id'),'titulo'=>old('titulo'),'chave'=>old('chave')])
<script type="application/json" id="prova-copiar-config">@json($g360Config)</script>
<script src="{{ asset('js/prova-copiar.js') }}?v={{ filemtime(public_path('js/prova-copiar.js')) }}"></script>
@endpush
