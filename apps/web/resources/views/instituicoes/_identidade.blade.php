<h2 style="font-size:19px;overflow-wrap:anywhere;">{{ $e['nome'] }}{{ !empty($e['sigla']) ? ' · '.$e['sigla'] : '' }}</h2>
<p>{{ $e['municipio_nome'] }} — {{ $e['uf'] }} · {{ $tipos[$e['tipo']] ?? $e['tipo'] }}</p>
<p class="page-sub">Dependência administrativa: {{ ['federal'=>'Federal','estadual'=>'Estadual','municipal'=>'Municipal','privada'=>'Privada','especial'=>'Especial'][$e['dependencia']??'']??'Não informada' }}</p>
<p class="page-sub">{{ $e['natureza']==='sede_administrativa' ? 'Sede administrativa da IES' : 'Escola de referência' }} · {{ $e['namespace']==='inep_escola' ? 'INEP da escola' : 'Código INEP da IES' }}: <b>{{ $e['codigo'] }}</b>
  · {{ $e['situacao_rotulo'] }} · Fonte: {{ $e['fonte']==='basica' ? 'Censo Escolar' : 'Censo Superior' }} {{ $e['ano'] }}</p>
