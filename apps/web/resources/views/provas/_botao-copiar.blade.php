<button type="button" class="btn btn-sm btn-secondary" data-copiar-prova
        data-origem="{{ $origem['id'] }}" data-titulo="{{ $origem['titulo'] }}"
        data-acao="{{ route('provas.copiar.store',$origem['id']) }}"
        data-chave="{{ \Illuminate\Support\Str::uuid() }}" aria-haspopup="dialog" aria-controls="cp1-modal">Copiar</button>
