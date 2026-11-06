@extends('layouts.app')
@section('title','Configurações de usuários')
@section('breadcrumb')<span>Configurações de usuários</span>@endsection
@section('content')
<x-pagina titulo="Configurações de usuários"><x-slot:sub>Exceções entre funções administrativas e pedagógicas, limitadas a uma pessoa e instituição.</x-slot:sub></x-pagina>
@if($mensagem)<x-aviso tipo="sucesso">{{ $mensagem }}</x-aviso>@endif
@if($erro)<x-aviso tipo="erro">{{ $erro }}</x-aviso>@endif
@if($errors->any())<x-aviso tipo="erro">{{ $errors->first() }}</x-aviso>@endif
<section class="card card-pad">
 <h2>Localizar usuário na instituição</h2>
 <p>Encerrar a função anterior permite uma nova função de outro grupo. Manter os dois grupos exige chamado e liberação específica. Esta configuração não cria vínculos nem concede permissões.</p>
 <form method="POST" action="{{ route('funcoes.consultar') }}">@csrf <x-revisao-acesso />
  <div class="form-row-2"><div class="field"><label for="funcao-escola">Instituição</label><select class="select" id="funcao-escola" name="espaco_id" required><option value="">Selecione</option>@foreach($escolas as $e)<option value="{{ $e['id'] }}" @selected((string)old('espaco_id',$dados['espaco_id']??'')===(string)$e['id'])>{{ $e['nome'] }}</option>@endforeach</select></div>
  <div class="field"><label for="funcao-email">E-mail da conta</label><input class="input" id="funcao-email" name="email" type="email" required autocomplete="off"></div></div>
  <button class="btn btn-primary" type="submit">Consultar usuário</button>
 </form>
</section>
@if(!empty($dados))
<section class="card card-pad" style="margin-top:20px;">
 <h2>{{ $dados['usuario']['nome'] }}</h2>
 <p>{{ collect($escolas)->firstWhere('id',$dados['espaco_id'])['nome']??'Instituição selecionada' }}</p>
 <ul>@foreach($dados['vinculos']??[] as $v)<li>{{ \App\Support\Acesso::rotulo($v['perfil']) }} — {{ ucfirst($v['situacao']) }}</li>@endforeach</ul>
 <p>Exceção atual: <strong>{{ $dados['permitir']?'Permitida':'Bloqueada' }}</strong>. Revogar a exceção pode bloquear vínculos incompatíveis até que a instituição encerre um deles.</p>
 <form method="POST" action="{{ route('funcoes.salvar') }}">@csrf <x-revisao-acesso />
  <input type="hidden" name="usuario_id" value="{{ $dados['usuario']['id'] }}"><input type="hidden" name="espaco_id" value="{{ $dados['espaco_id'] }}"><input type="hidden" name="versao" value="{{ $dados['versao'] }}">
  <div class="field"><label for="excecao-permitir">Funções de grupos diferentes na mesma instituição</label><select class="select" id="excecao-permitir" name="permitir"><option value="0" @selected(!$dados['permitir'])>Bloquear a exceção</option><option value="1" @selected($dados['permitir'])>Permitir mediante chamado</option></select></div>
  <div class="form-row-2"><div class="field"><label for="excecao-chamado">Referência do chamado</label><input class="input" id="excecao-chamado" name="chamado" required minlength="3" maxlength="80" value="{{ old('chamado',$dados['chamado']) }}"></div><div class="field"><label for="excecao-motivo">Motivo da autorização ou revogação</label><textarea class="input" id="excecao-motivo" name="motivo" required minlength="10" maxlength="500">{{ old('motivo',$dados['motivo']) }}</textarea></div></div>
  <button class="btn btn-primary" type="submit">Registrar configuração</button>
 </form>
</section>
<section class="card card-pad" style="margin-top:20px;"><h2>Histórico de autorizações</h2><x-tabela :integrada="true" titulo="Histórico de exceções" modo="lista"><table class="table tabela-padrao"><caption class="sr-only">Histórico de exceções</caption><thead><tr><th scope="col">Quando</th><th scope="col">Ação</th><th scope="col">Responsável</th><th scope="col">Chamado</th><th scope="col">Motivo</th></tr></thead><tbody>@forelse($dados['historico'] as $h)<tr><td data-rotulo="Quando">{{ \Carbon\Carbon::parse($h['ocorrido_em'])->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</td><td data-rotulo="Ação">{{ $h['permitir']?'Permitir':'Revogar' }}</td><td data-rotulo="Responsável">{{ \App\Support\Acesso::rotulo($h['perfil_ator']) }} #{{ $h['ator_id'] }}</td><td data-rotulo="Chamado">{{ $h['chamado'] }}</td><td data-rotulo="Motivo">{{ $h['motivo'] }}</td></tr>@empty<tr><td colspan="5">Nenhuma exceção registrada para esta pessoa nesta instituição.</td></tr>@endforelse</tbody></table></x-tabela></section>
@endif
@endsection
