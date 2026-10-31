@props(['nome' => '', 'alunoId' => null, 'tamanho' => 'md'])
@php
  $partes = preg_split('/\s+/u', trim($nome), -1, PREG_SPLIT_NO_EMPTY) ?: [];
  $iniciais = implode('', array_map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($partes, 0, 2))) ?: '?';
  $cores = ['azul', 'verde', 'roxo', 'neutro'];
  $cor = $cores[abs((int) $alunoId) % count($cores)];
  $tamanho = in_array($tamanho, ['sm', 'md', 'lg', 'xl'], true) ? $tamanho : 'md';
@endphp
<span {{ $attributes->class(['avatar-aluno', 'avatar-aluno--'.$cor, 'avatar-aluno--'.$tamanho]) }} aria-hidden="true">{{ $iniciais }}</span>
