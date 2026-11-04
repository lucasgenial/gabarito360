<section class="card card-pad pc-secao" aria-labelledby="pc-dados-titulo">
  <h2 id="pc-dados-titulo">Dados da prova</h2>
  <div class="pc-grade">
    <div class="field pc-linha"><label for="titulo">Título da prova *</label><input class="input" id="titulo" name="titulo" @include('provas.curriculo._atributos-erro',['campo'=>'titulo']) required maxlength="255" value="{{ $f['titulo'] }}">@include('provas.curriculo._erro',['campo'=>'titulo'])</div>
    <div class="field"><label for="serie">Série / ano *</label><input class="input" id="serie" name="serie" @include('provas.curriculo._atributos-erro',['campo'=>'serie']) required maxlength="50" value="{{ $f['serie'] }}">@include('provas.curriculo._erro',['campo'=>'serie'])</div>
    <div class="field"><label for="formato_avaliacao">Formato da avaliação *</label><select class="select" id="formato_avaliacao" name="formato_avaliacao" required><option value="gabarito">Gabarito e cartão-resposta</option></select><x-orientacao assunto="formato da avaliação"><p>Cadastre o gabarito oficial para corrigir os cartões. Os enunciados são elaborados fora deste fluxo.</p></x-orientacao></div>
    <div class="field"><label for="num_questoes">Número de questões *</label><input class="input num" type="number" id="num_questoes" name="num_questoes" @include('provas.curriculo._atributos-erro',['campo'=>'num_questoes']) min="1" max="100" required value="{{ $f['num_questoes'] }}">@include('provas.curriculo._erro',['campo'=>'num_questoes'])</div>
    <div class="field"><label for="num_alternativas">Alternativas por questão *</label><select class="select" id="num_alternativas" name="num_alternativas" @include('provas.curriculo._atributos-erro',['campo'=>'num_alternativas']) required>@foreach([3,4,5] as $n)<option value="{{ $n }}" @selected((int)$f['num_alternativas']===$n)>{{ $n }} alternativas</option>@endforeach</select></div>
    <div class="field"><label for="data_aplicacao">Data de aplicação (opcional)</label><input class="input" type="date" id="data_aplicacao" name="data_aplicacao" @include('provas.curriculo._atributos-erro',['campo'=>'data_aplicacao']) value="{{ $f['data_aplicacao'] }}">@include('provas.curriculo._erro',['campo'=>'data_aplicacao'])</div>
    <div class="field"><label for="nota_maxima">Nota máxima</label><input class="input num" type="number" id="nota_maxima" name="nota_maxima" @include('provas.curriculo._atributos-erro',['campo'=>'nota_maxima']) step="0.5" min="0.5" max="100" value="{{ $f['nota_maxima'] }}">@include('provas.curriculo._erro',['campo'=>'nota_maxima'])</div>
  </div>
  <details class="pc-configuracoes"><summary>Configurações da correção e do cartão</summary><div class="pc-secao">
    <label class="pc-check"><input type="checkbox" name="anular_se_todas" value="1" @checked($f['anular_se_todas']??false)>Anular questão com mais de uma alternativa marcada</label>
    <label class="pc-check"><input type="checkbox" name="opcao_lingua" value="1" @checked($f['opcao_lingua']??false)>Opção de língua estrangeira nas questões 1 a 5 (padrão ENEM)</label>
    <label class="pc-check"><input type="checkbox" name="gerar_cartao_pdf" value="1" @checked($f['gerar_cartao_pdf']??true)>Gerar cartão-resposta em PDF</label>
  </div></details>
  <x-orientacao assunto="próximos passos"><p>Ao criar, a prova fica em rascunho e você segue para preencher o gabarito. Depois de revisá-lo, publique para preparar os cartões e aplicar às turmas.</p></x-orientacao>
</section>
