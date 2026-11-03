<h2 style="font-size:19px;overflow-wrap:anywhere;">{{ $e['nome'] }}{{ !empty($e['sigla']) ? ' · '.$e['sigla'] : '' }}</h2>
<p>{{ $e['municipio_nome'] }} — {{ $e['uf'] }} · {{ $tipos[$e['tipo']] ?? $e['tipo'] }}</p>
<p class="page-sub">Dependência administrativa: {{ ['federal'=>'Federal','estadual'=>'Estadual','municipal'=>'Municipal','privada'=>'Privada','especial'=>'Especial'][$e['dependencia']??'']??'Não informada' }}</p>
<p class="page-sub">{{ $e['natureza']==='sede_administrativa' ? 'Sede administrativa da IES' : 'Escola de referência' }} · {{ ['inep_escola'=>'INEP da escola','inep_ies'=>'Código INEP da IES','plataforma'=>'Código da plataforma'][$e['namespace']]??'Código' }}: <b>{{ $e['codigo'] }}</b>
  · {{ $e['situacao_rotulo'] }} · Fonte: {{ ['basica'=>'Censo Escolar','superior'=>'Censo Superior','manual'=>'Cadastro da plataforma'][$e['fonte']]??$e['fonte'] }} {{ $e['ano'] }}</p>
@if($e['fonte']==='manual')<p class="page-sub">Cadastro ou correção conferida pela equipe responsável. Esta edição preserva a fonte censitária e não confirma vínculo profissional ou acesso escolar.</p>@endif
