@props(['nome'=>'','usuarioId'=>null,'avatarId'=>null,'temFoto'=>false,'versao'=>1,'proprio'=>false,'tamanho'=>'md'])
@if($temFoto)
<span class="avatar-aluno avatar-aluno--{{ in_array($tamanho,['sm','md','lg','xl'])?$tamanho:'md' }}" aria-hidden="true"><img src="{{ $proprio ? route('perfil.foto',['v'=>$versao]) : route('usuarios.foto',['id'=>$usuarioId,'v'=>$versao]) }}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;"></span>
@else
<x-avatar :nome="$nome" :aluno-id="$usuarioId" :avatar-id="$avatarId" :tamanho="$tamanho" />
@endif
