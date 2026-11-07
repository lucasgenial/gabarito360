<dialog class="tu1-dialog" data-documento-modal aria-labelledby="documento-titulo">
<div class="tu1-dialog-cabecalho"><h2 id="documento-titulo" data-documento-titulo>Gerar PDF</h2><button class="btn btn-ghost tu1-icone-acao" type="button" data-documento-fechar aria-label="Fechar" title="Fechar"><x-icone nome="fechar" /></button></div>
<form method="POST" action="{{ route('turmas.painelPdf',$turma['id']) }}" target="_blank" data-painel-documento>
@csrf <x-revisao-acesso />
@foreach($painelAplicado as $campo=>$valor)@if($valor!==null&&$valor!=='')<input type="hidden" name="{{ $campo }}" value="{{ $valor }}">@endif @endforeach
<input type="hidden" name="baixar" value="1">
<fieldset class="tu1r3-orientacao"><legend>Disposição das páginas</legend><label><input type="radio" name="orientacao" value="retrato" checked><span class="tu1r3-folha" aria-hidden="true"></span><strong>Vertical</strong><span>Retrato · A4</span></label><label><input type="radio" name="orientacao" value="paisagem"><span class="tu1r3-folha tu1r3-folha-horizontal" aria-hidden="true"></span><strong>Horizontal</strong><span>Paisagem · A4</span></label></fieldset>
<p class="field-help">O documento inclui os indicadores e os filtros aplicados neste painel.</p><div class="tu1-acoes"><button class="btn btn-secondary" type="button" data-documento-fechar>Cancelar</button><button class="btn btn-primary" type="submit"><x-icone nome="pdf" /><span data-documento-confirmar>Gerar PDF</span></button></div>
</form></dialog>
