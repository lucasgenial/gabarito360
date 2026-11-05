{{-- Fallback progressivo: não duplicar o aviso que a própria página já renderiza. --}}
@foreach(['sucesso','erro','info'] as $tipo)
  @php($mensagem=session($tipo))
  @if(is_string($mensagem)&&$mensagem!==''&&!($tipo==='erro'&&$errors->any())&&!str_contains($__env->yieldContent('content'),'data-g360-flash="'.$tipo.'"'))
    <x-aviso :tipo="$tipo" data-g360-flash="{{ $tipo }}">{{ $mensagem }}</x-aviso>
  @endif
@endforeach
