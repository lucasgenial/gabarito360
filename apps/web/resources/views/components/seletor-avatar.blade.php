@props(['catalogo'=>'estudantes', 'direto'=>false, 'titulo'=>'Avatar do aluno', 'nome' => '', 'alunoId' => null, 'avatarId' => null, 'campoNome' => 'nome', 'previewId' => 'preview-avatar'])
@php
  $selecionado = filter_var(old('avatar_id', $avatarId), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 36]]) ?: null;
@endphp
<fieldset class="seletor-avatar" data-seletor-avatar data-campo-nome="{{ $campoNome }}">
  <legend>{{ $titulo }}</legend>
  <div class="seletor-avatar-atual">
    <x-avatar :catalogo="$catalogo" :nome="$nome" :aluno-id="$alunoId" :avatar-id="$selecionado" tamanho="lg" :id="$previewId" data-avatar-preview />
    <span data-avatar-status aria-live="polite">{{ $selecionado ? 'Avatar '.$selecionado.' selecionado' : 'Iniciais do nome' }}</span>
  </div>
  @if($direto)<div>@else<details @if($errors->has('avatar_id')) open @endif><summary>Escolher avatar</summary>@endif
    <div class="avatar-opcoes">
      <label class="avatar-opcao avatar-opcao-iniciais">
        <input type="radio" name="avatar_id" value="" @checked(!$selecionado)>
        <span>Usar iniciais do nome</span>
      </label>
      @for($i = 1; $i <= 36; $i++)
        <label class="avatar-opcao">
          <input type="radio" name="avatar_id" value="{{ $i }}" aria-label="Avatar {{ $i }}" @checked($selecionado === $i)>
          <x-avatar :catalogo="$catalogo" :avatar-id="$i" />
          <span class="avatar-numero">{{ $i }}</span>
        </label>
      @endfor
    </div>
  @if($direto)</div>@else</details>@endif
  @error('avatar_id')<span class="field-error">Escolha um dos avatares disponíveis ou use as iniciais.</span>@enderror
</fieldset>
