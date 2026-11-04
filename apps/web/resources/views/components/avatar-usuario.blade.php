@props(['nome'=>'','usuarioId'=>null,'avatarId'=>null,'temFoto'=>false,'versao'=>1,'proprio'=>false,'tamanho'=>'md'])
@php
 $partes=preg_split('/\s+/u',trim($nome),-1,PREG_SPLIT_NO_EMPTY)?:[];
 $iniciais=implode('',array_map(fn($p)=>mb_strtoupper(mb_substr($p,0,1)),array_slice($partes,0,2)))?:'?';
 $foto=$proprio?route('perfil.foto',['v'=>$versao,'_ctx'=>\App\Support\ContextoWeb::id()]):route('usuarios.foto',['id'=>$usuarioId??0,'v'=>$versao,'_ctx'=>\App\Support\ContextoWeb::id()]);
@endphp
<span @if($proprio) data-imagem-propria data-tamanho="{{ $tamanho }}" data-iniciais="{{ $iniciais }}" @endif aria-hidden="true">
@if($temFoto)
<span class="avatar-aluno avatar-aluno--{{ in_array($tamanho,['sm','md','lg','xl'])?$tamanho:'md' }}"><img src="{{ $foto }}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;"></span>
@else
<x-avatar :nome="$nome" :aluno-id="$usuarioId" :avatar-id="$avatarId" :tamanho="$tamanho" />
@endif
</span>
