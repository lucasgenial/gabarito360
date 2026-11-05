@if($curricular??false)
  @if($prova['organizacao_curricular']['editavel']??false)
    @unless($prova['organizacao_curricular']['configurado']??false)<x-aviso tipo="atencao">Configure turmas e disciplinas antes de publicar.</x-aviso>@endunless
    @if($prova['pode_editar']??false)@pode('prova.editar')<a class="btn btn-secondary" href="{{ route('provas.curriculo.edit',['id'=>$prova['id']]) }}">{{ ($prova['organizacao_curricular']['configurado']??false)?'Configurar turmas e disciplinas':'Configurar' }}</a>@endpode @endif
  @else
    <p class="field-help" style="margin-top:16px;">Classificação preservada após a publicação ou aplicação.</p>
  @endif
@endif
