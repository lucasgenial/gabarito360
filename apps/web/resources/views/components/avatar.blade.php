@props(['temFoto'=>false, 'imagemVersao'=>1, 'nome' => '', 'alunoId' => null, 'avatarId' => null, 'tamanho' => 'md'])
@php
  $partes = preg_split('/\s+/u', trim($nome), -1, PREG_SPLIT_NO_EMPTY) ?: [];
  $iniciais = implode('', array_map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($partes, 0, 2))) ?: '?';
  $cores = ['azul', 'verde', 'roxo', 'neutro'];
  $cor = $cores[abs((int) $alunoId) % count($cores)];
  $tamanho = in_array($tamanho, ['sm', 'md', 'lg', 'xl'], true) ? $tamanho : 'md';
  $avatar = filter_var($avatarId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 36]]) ?: null;
@endphp
<span {{ $attributes->class(['avatar-aluno', 'avatar-aluno--'.$cor, 'avatar-aluno--'.$tamanho, 'avatar-aluno--imagem' => $avatar !== null]) }}
      @if($avatar) style="--avatar-x:{{ (($avatar - 1) % 6) * 20 }}%;--avatar-y:{{ intdiv($avatar - 1, 6) * 20 }}%" @endif aria-hidden="true">@if($temFoto && $alunoId)<img src="{{ route('alunos.foto',['id'=>$alunoId,'v'=>$imagemVersao]) }}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">@else<span class="avatar-iniciais">{{ $iniciais }}</span>@endif</span>
