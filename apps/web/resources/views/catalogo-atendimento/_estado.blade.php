@php
$rotulos=['enviada'=>'Enviada','em_analise'=>'Em análise','aguardando_complementacao'=>$modo==='minhas-solicitacoes'?'Precisa de complementação':'Aguardando complementação','aprovada'=>'Aprovada','indeferida'=>'Indeferida','cancelada'=>'Cancelada'];
$cores=['enviada'=>'info','em_analise'=>'info','aguardando_complementacao'=>'warn','aprovada'=>'success','indeferida'=>'muted','cancelada'=>'muted'];
@endphp
<span class="badge badge-{{ $cores[$situacao]??'muted' }}">{{ $rotulos[$situacao]??$situacao }}</span>
