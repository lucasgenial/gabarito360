@php
$c=old('cadastro',$cadastro??[]);$tipos=\App\Http\Controllers\InstituicoesPessoaisController::TIPOS;
$pessoal=$modo==='minhas-solicitacoes';
$geoUrl=route($modo==='atendimento'?'catalogo-atendimento.municipios':($modo==='gestao'?'catalogo-gestao.municipios':'catalogo-pedidos.municipios'));
$temAdicionais=!empty($c['sigla'])||!empty($c['codigo'])||!empty($c['site'])||!empty($c['observacao'])||$errors->has('cadastro.sigla')||$errors->has('cadastro.codigo')||$errors->has('cadastro.namespace')||$errors->has('cadastro.site')||$errors->has('cadastro.observacao');
@endphp
<div data-catalogo-localizacao data-url="{{ $geoUrl }}">
<fieldset class="catalogo-secao">
  <legend>Identificação</legend>
  <p class="catalogo-ajuda">{{ $pessoal?'Informe como a instituição é conhecida e o tipo de instituição.':'Confira os dados que serão apresentados no catálogo.' }}</p>
  <div class="catalogo-campos">
    <div class="catalogo-campo catalogo-campo-inteiro"><label for="cad-nome">Nome da instituição <span aria-hidden="true">*</span></label><input id="cad-nome" name="cadastro[nome]" required maxlength="200" autocomplete="organization" value="{{ $c['nome']??'' }}" aria-invalid="{{ $errors->has('cadastro.nome')?'true':'false' }}" aria-describedby="erro-nome">@include('catalogo-atendimento._erro-campo',['campo'=>'cadastro.nome','erroId'=>'erro-nome'])</div>
    <div class="catalogo-campo"><label for="cad-tipo">Tipo de instituição <span aria-hidden="true">*</span></label><select id="cad-tipo" name="cadastro[tipo]" required @disabled(!empty($e)) aria-invalid="{{ $errors->has('cadastro.tipo')?'true':'false' }}" aria-describedby="erro-tipo"><option value="">Selecione o tipo</option>@foreach($tipos as $k=>$v)<option value="{{ $k }}" @selected(($c['tipo']??'')===$k)>{{ $v }}</option>@endforeach</select>@if(!empty($e))<input type="hidden" name="cadastro[tipo]" value="{{ $c['tipo'] }}">@endif @include('catalogo-atendimento._erro-campo',['campo'=>'cadastro.tipo','erroId'=>'erro-tipo'])</div>
    <div class="catalogo-campo"><label for="cad-dep">{{ $pessoal?'Administração da instituição, se souber':'Dependência administrativa *' }}</label><select id="cad-dep" name="cadastro[dependencia]" @required(!$pessoal) aria-invalid="{{ $errors->has('cadastro.dependencia')?'true':'false' }}" aria-describedby="ajuda-dep erro-dep">@if($pessoal)<option value="nao_informada" @selected(($c['dependencia']??'nao_informada')==='nao_informada')>Não sei informar</option>@else<option value="">Selecione após conferir</option>@endif @foreach(['federal'=>'Federal','estadual'=>'Estadual','municipal'=>'Municipal','privada'=>'Privada','especial'=>'Especial'] as $k=>$v)<option value="{{ $k }}" @selected(($c['dependencia']??'')===$k)>{{ $v }}</option>@endforeach</select><p class="catalogo-ajuda" id="ajuda-dep">{{ $pessoal?'Se não souber, a equipe confere essa informação.':'Classificação conferida pela equipe responsável.' }}</p>@include('catalogo-atendimento._erro-campo',['campo'=>'cadastro.dependencia','erroId'=>'erro-dep'])</div>
  </div>
</fieldset>
<fieldset class="catalogo-secao">
  <legend>Localização</legend>
  <p class="catalogo-ajuda">Selecione a UF e o município onde a instituição está localizada.</p>
  <div class="catalogo-campos catalogo-campos-localizacao">
    <div class="catalogo-campo"><label for="cad-uf">UF <span aria-hidden="true">*</span></label><select id="cad-uf" name="cadastro[uf]" required data-uf aria-invalid="{{ $errors->has('cadastro.uf')?'true':'false' }}" aria-describedby="erro-uf"><option value="">Selecione a UF</option>@foreach($opcoes['ufs'] as $uf)<option value="{{ $uf['sigla'] }}" @selected(($c['uf']??'')===$uf['sigla'])>{{ $uf['nome'] }} ({{ $uf['sigla'] }})</option>@endforeach</select>@include('catalogo-atendimento._erro-campo',['campo'=>'cadastro.uf','erroId'=>'erro-uf'])</div>
    <div class="catalogo-campo"><label for="cad-municipio">Município <span aria-hidden="true">*</span></label><select id="cad-municipio" name="cadastro[municipio_ibge]" required data-municipio data-selecionado="{{ $c['municipio_ibge']??'' }}" aria-invalid="{{ $errors->has('cadastro.municipio_ibge')?'true':'false' }}" aria-describedby="cad-status erro-municipio"><option value="">Selecione a UF primeiro</option></select>@include('catalogo-atendimento._erro-campo',['campo'=>'cadastro.municipio_ibge','erroId'=>'erro-municipio'])</div>
  </div>
  <div class="catalogo-pesquisa-municipio" data-pesquisa-municipio @if(empty($c['uf'])) hidden @endif>
    <label for="cad-localizar">Pesquisar município pelo nome</label><div class="catalogo-busca-linha"><input id="cad-localizar" type="search" data-busca maxlength="100" placeholder="Digite parte do nome"><button type="button" class="btn btn-secondary" data-localizar>Pesquisar</button></div>
    <p class="catalogo-ajuda" role="status" aria-live="polite" id="cad-status" data-status></p><button type="button" class="btn btn-ghost btn-sm" data-proximos hidden>Carregar mais municípios</button>
  </div>
</fieldset>
<details class="catalogo-secao catalogo-adicionais" @if($temAdicionais || !$pessoal) open @endif>
  <summary>Informações adicionais{{ $pessoal?' (opcional)':'' }}</summary>
  <p class="catalogo-ajuda">{{ $pessoal?'Estes detalhes ajudam a equipe a localizar a instituição. Preencha apenas o que souber.':'Registre a sigla e a evidência pública utilizada na conferência.' }}</p>
  <div class="catalogo-campos">
    <div class="catalogo-campo"><label for="cad-sigla">Sigla (opcional)</label><input id="cad-sigla" name="cadastro[sigla]" maxlength="40" value="{{ $c['sigla']??'' }}" aria-invalid="{{ $errors->has('cadastro.sigla')?'true':'false' }}" aria-describedby="erro-sigla">@include('catalogo-atendimento._erro-campo',['campo'=>'cadastro.sigla','erroId'=>'erro-sigla'])</div>
    @if(empty($e))
    <div class="catalogo-campo"><label for="cad-fonte">Código oficial, se conhecido</label><select id="cad-fonte" data-fonte-codigo name="cadastro[namespace]" aria-invalid="{{ $errors->has('cadastro.namespace')?'true':'false' }}" aria-describedby="erro-fonte"><option value="">Não tenho o código</option><option value="inep_escola" @selected(($c['namespace']??'')==='inep_escola')>INEP da escola</option><option value="inep_ies" @selected(($c['namespace']??'')==='inep_ies')>INEP da IES</option></select>@include('catalogo-atendimento._erro-campo',['campo'=>'cadastro.namespace','erroId'=>'erro-fonte'])</div>
    <div class="catalogo-campo"><label for="cad-codigo">Número do código</label><p class="catalogo-ajuda">Preencha somente se tiver selecionado uma fonte do código.</p><input id="cad-codigo" data-numero-codigo name="cadastro[codigo]" inputmode="numeric" maxlength="8" value="{{ $c['codigo']??'' }}" aria-invalid="{{ $errors->has('cadastro.codigo')?'true':'false' }}" aria-describedby="erro-codigo">@include('catalogo-atendimento._erro-campo',['campo'=>'cadastro.codigo','erroId'=>'erro-codigo'])</div>
    @elseif(($c['namespace']??null)!==null)<input type="hidden" name="cadastro[namespace]" value="{{ $c['namespace'] }}"><input type="hidden" name="cadastro[codigo]" value="{{ $c['codigo'] }}">@endif
    <div class="catalogo-campo catalogo-campo-inteiro"><label for="cad-site">Site público da instituição ou evidência (HTTPS)</label><input id="cad-site" name="cadastro[site]" type="url" maxlength="500" value="{{ $c['site']??'' }}" placeholder="https://…" aria-invalid="{{ $errors->has('cadastro.site')?'true':'false' }}" aria-describedby="ajuda-site erro-site"><p class="catalogo-ajuda" id="ajuda-site">Use um endereço público. Não inclua senhas ou links para documentos privados.</p>@include('catalogo-atendimento._erro-campo',['campo'=>'cadastro.site','erroId'=>'erro-site'])</div>
    @if($pessoal)<div class="catalogo-campo catalogo-campo-inteiro"><label for="cad-obs">Observação para a equipe (opcional)</label><textarea id="cad-obs" name="cadastro[observacao]" maxlength="2000" rows="3" aria-invalid="{{ $errors->has('cadastro.observacao')?'true':'false' }}" aria-describedby="ajuda-obs erro-obs">{{ $c['observacao']??'' }}</textarea><p class="catalogo-ajuda" id="ajuda-obs">Não inclua CPF, notas ou dados de alunos. Somente você e a equipe interna acompanham este pedido.</p>@include('catalogo-atendimento._erro-campo',['campo'=>'cadastro.observacao','erroId'=>'erro-obs'])</div>@endif
  </div>
</details>
</div>
