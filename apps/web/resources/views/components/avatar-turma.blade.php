@props(['turma','tamanho'=>'md'])
@php($iniciais=mb_strtoupper(mb_substr(preg_replace('/\s+/u','',$turma['nome']),0,2)))
<span data-imagem-turma="{{ $turma['id'] }}" data-tamanho="{{ $tamanho }}" data-iniciais="{{ $iniciais }}" aria-hidden="true">
@if($turma['tem_foto']??false)<span class="avatar-aluno avatar-aluno--azul avatar-aluno--{{ $tamanho }}"><img data-imagem-avatar data-imagem-contextual="{{ route('turmas.foto',['id'=>$turma['id'],'v'=>$turma['imagem_versao']??1]) }}" alt="" style="display:none;width:100%;height:100%;object-fit:cover;border-radius:inherit;"><span class="avatar-iniciais">{{ $iniciais }}</span></span>
@elseif(!empty($turma['avatar_id']))<x-avatar catalogo="turmas" :avatar-id="$turma['avatar_id']" :tamanho="$tamanho" />
@else<span class="avatar-aluno avatar-aluno--azul avatar-aluno--{{ $tamanho }}"><span class="avatar-iniciais">{{ $iniciais }}</span></span>@endif
</span>
