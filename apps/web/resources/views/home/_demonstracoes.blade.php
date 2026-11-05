<div class="publico-demos">
  <article class="publico-demo">
    <figure>
      <div class="publico-demo-painel">
        <div class="publico-demo-barra"><strong>Cartão-resposta</strong><span>Matemática</span></div>
        <p class="publico-demo-estado">Aluno de exemplo · Turma 1º A</p>
        <div class="lp-cartao" aria-label="Exemplo de cinco questões com alternativas A a E">
          @foreach([['01','B'],['02','A'],['03',null],['04','C'],['05','E']] as [$numero,$marcada])
            <div class="lp-linha"><b>{{ $numero }}</b>@foreach(['A','B','C','D','E'] as $letra)<span class="{{ $letra === $marcada ? 'marcada' : '' }}" aria-label="{{ $letra === $marcada ? $letra.' marcada' : $letra }}">{{ $letra }}</span>@endforeach</div>
          @endforeach
        </div>
      </div>
      <figcaption>Dados fictícios. Recorte ilustrativo de um cartão; não utilizar para aplicação.</figcaption>
    </figure>
    <div class="publico-demo-texto"><h3>Um cartão para cada aluno</h3><p>Gere os cartões identificados e imprima para a aplicação. A identificação conecta a leitura à avaliação e ao aluno.</p></div>
  </article>
  <article class="publico-demo">
    <figure>
      <div class="publico-demo-painel">
        <div class="publico-demo-barra"><strong>Revisar leitura</strong><span>Questão 04</span></div>
        <p>Marcação incerta: confira a imagem antes de registrar.</p>
        <div class="publico-demo-possibilidades" aria-label="Exemplo de alternativas; C é a resposta conferida">
          @foreach(['A','B','C','D','E'] as $letra)<span class="{{ $letra==='C'?'selecionada':'' }}">{{ $letra }}</span>@endforeach
        </div>
        <p class="publico-demo-estado">Resposta conferida: C · Exemplo de revisão humana</p>
      </div>
      <figcaption>Dados fictícios. Apresentação estática da revisão; não envia ou altera respostas.</figcaption>
    </figure>
    <div class="publico-demo-texto"><h3>Você confere o que precisa de atenção</h3><p>Leituras incertas são separadas para conferência. A decisão do professor continua fazendo parte do processo.</p></div>
  </article>
  <article class="publico-demo">
    <figure>
      <div class="publico-demo-painel">
        <div class="publico-demo-barra"><strong>Questão por questão</strong><span>Visão do educador</span></div>
        <div class="publico-demo-questao correta"><b>01</b> · acertou (B)<div class="publico-demo-indicadores"><span>Turma <b>66,7%</b> · 2 de 3 correções</span><span>Geral <b>75,0%</b> · 6 de 8 correções</span></div></div>
        <div class="publico-demo-questao incorreta"><b>02</b> · marcou A · certa D<div class="publico-demo-indicadores"><span>Turma <b>33,3%</b> · 1 de 3 correções</span><span>Geral <b>50,0%</b> · 4 de 8 correções</span></div></div>
        <div class="publico-demo-questao branco"><b>03</b> · em branco · certa A</div>
      </div>
      <figcaption>Dados fictícios. Indicadores coletivos são exclusivos de educadores autorizados.</figcaption>
    </figure>
    <div class="publico-demo-texto"><h3>Enxergue além da nota</h3><p>Veja acertos, erros e respostas em branco. Percentuais por questão ajudam o educador a perceber onde retomar o conteúdo.</p><details class="publico-ajuda"><summary>Saiba mais</summary><p>Turma considera correções concluídas da turma. Geral considera as turmas da mesma prova no mesmo espaço. Questões anuladas não têm percentual; o gabarito aplicável é respeitado.</p></details></div>
  </article>
  <article class="publico-demo">
    <figure>
      <div class="publico-demo-painel publico-demo-relatorio">
        <x-marca size="sm" theme="light" />
        <strong>Resultado da avaliação</strong>
        <span>Aluno de exemplo · Turma 1º A</span>
        <div class="publico-demo-nota"><span>Nota</span><strong>8,0</strong></div>
        <div class="publico-demo-linha"><span>Acertos</span><b>8 de 10</b></div>
        <div class="publico-demo-linha"><span>Documento</span><b>Relatório individual</b></div>
        <small>Emissão identificada · Consulta por código ou QR</small>
      </div>
      <figcaption>Resumo ilustrativo de relatório com dados fictícios. Não é um documento emitido ou validável.</figcaption>
    </figure>
    <div class="publico-demo-texto"><h3>Resultados prontos para compartilhar</h3><p>Relatórios para aluno e professor, em PDF individual ou em lote, organizam a devolutiva. A emissão pode ser consultada por código ou QR e o arquivo pode ter sua integridade conferida.</p></div>
  </article>
</div>
