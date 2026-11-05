@php($ctxGuia=\App\Support\ContextoWeb::id())
@push('styles')
<style>
.copias-guia{margin:20px 0}.copias-guia h2{margin-bottom:12px}.copias-guia p{margin:8px 0;max-width:85ch}.copias-opcoes{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr));gap:16px;margin:16px 0}.copias-opcao{display:flex;flex-direction:column;gap:12px}.copias-opcao .btn{align-self:flex-start}.copias-passos{padding-left:24px;display:grid;gap:14px}.copias-passos li{padding-left:6px}.copias-nota{color:var(--text-secondary,#526078)}.copias-links{display:flex;flex-wrap:wrap;gap:12px;margin-top:12px}.copias-guia summary{cursor:pointer;font-weight:600;padding:10px 0}.copias-guia table{min-width:520px}
</style>
@endpush
<section class="card card-pad copias-guia" aria-labelledby="copias-finalidade">
  <h2 id="copias-finalidade">Reutilize conteúdo em outra escola ou no seu espaço pessoal</h2>
  <p>Envie uma <strong>cópia independente</strong> do conteúdo selecionado. O original permanece no espaço de origem. O destino recebe sua própria versão, acessível pelas permissões daquele espaço.</p>
  <p><strong>Não há sincronização:</strong> alterações futuras em um espaço não atualizam o outro. Esta operação não transfere a matrícula escolar do aluno nem remove seus registros da origem.</p>
  <details><summary>O que muda em cada espaço?</summary>
    <p><strong>Origem:</strong> conserva os registros originais. Quem deixa de ter vínculo com ela pode perder o acesso, mesmo tendo preparado a cópia.</p>
    <p><strong>Destino:</strong> passa a gerir a versão recebida no próprio espaço. Provas e cadastros podem ser utilizados conforme suas permissões; resultados ficam em acervo histórico. A cópia não declara uma transferência de titularidade jurídica dos dados.</p>
    <p>As duas partes autorizam a operação. Revogar a continuidade interrompe o que ainda não foi publicado e não recolhe cópias já publicadas.</p>
  </details>
</section>
@foreach(['origem'=>'Enviar uma cópia','destino'=>'Receber uma cópia'] as $ladoGuia=>$tituloGuia)
@if(\App\Support\Acesso::pode('copia.autorizar_'.$ladoGuia))
<section class="copias-guia" aria-labelledby="copias-{{ $ladoGuia }}">
  <h2 id="copias-{{ $ladoGuia }}">{{ $tituloGuia }}</h2>
  <p>{{ $ladoGuia==='origem'?'Você está no espaço que possui os registros originais. Escolha o conteúdo e peça ao responsável pelo destino o código da modalidade correspondente.':'Você está no espaço que receberá os registros. Comece aqui: escolha o conteúdo, selecione o espaço e gere o código para a pessoa que enviará a cópia.' }}</p>
  <div class="copias-opcoes">
  @foreach([
    ['Provas e gabaritos','Reutilizar uma avaliação como rascunho editável. Não inclui alunos, notas ou imagens de cartões.','copias.materiais','copias.material.receber','Selecionar materiais','Receber materiais'],
    ['Turmas e alunos','Copiar os cadastros selecionados, com autorização específica e apenas os campos permitidos. Não inclui notas.','copias.cadastros','copias.cadastro.receber','Selecionar cadastro','Receber cadastro'],
    ['Histórico de resultados','Preservar resultados autorizados em acervo histórico separado. Não substitui notas atuais nem recalcula avaliações no destino.','copias.resultados','copias.resultado.receber','Selecionar resultados','Receber resultados'],
  ] as [$nomeGuia,$descricaoGuia,$rotaEnvio,$rotaReceber,$rotuloEnvio,$rotuloReceber])
    <article class="card card-pad copias-opcao"><h3>{{ $nomeGuia }}</h3><p>{{ $descricaoGuia }}</p>
      <a class="btn {{ $ladoGuia==='origem'?'btn-primary':'btn-secondary' }}" href="{{ route($ladoGuia==='origem'?$rotaEnvio:$rotaReceber) }}">{{ $ladoGuia==='origem'?$rotuloEnvio:$rotuloReceber }}</a>
    </article>
  @endforeach
  </div>
</section>
@endif
@endforeach
<section class="card card-pad copias-guia" aria-labelledby="copias-como">
  <h2 id="copias-como">Como a cópia acontece</h2>
  <ol class="copias-passos">
    <li><strong>Quem recebe gera o código.</strong> Entra na própria conta, escolhe o vínculo da escola ou espaço pessoal de destino e abre Cópias → Receber uma cópia. Escolhe a modalidade e clica em “Gerar código de destino”. Compartilha o código com quem envia.</li>
    <li><strong>Quem envia seleciona e autoriza a saída.</strong> No vínculo de origem, abre Cópias → Enviar uma cópia, seleciona os itens e confere a prévia. Informa o código recebido, confere o destino e prepara a solicitação. Registra as revisões e autorizações exigidas para esse conteúdo.</li>
    <li><strong>Quem recebe confere e autoriza a entrada.</strong> Quem envia compartilha a referência da solicitação. O destinatário volta à mesma tela “Receber”, informa essa referência em “Conferir uma solicitação” e decide sobre a entrada do conteúdo.</li>
    <li><strong>O destino solicita o processamento.</strong> Depois das autorizações e confirmações exigidas, abre a operação e clica em “Solicitar processamento”. Ambos acompanham a situação abaixo. Só os itens publicados ficam disponíveis no destino.</li>
  </ol>
  <p class="copias-nota">O código identifica o destino; a referência identifica a solicitação criada depois. São informações diferentes. Nenhuma mensagem é enviada automaticamente.</p>
  <p>Se você administra os dois espaços, pode realizar os dois papéis: use <strong>Trocar vínculo</strong> para atuar em cada um. As autorizações continuam separadas.</p>
  <details><summary>Quem tem autonomia para autorizar?</summary>
    <div style="overflow-x:auto"><x-tabela :integrada="true" titulo="Perfis e alcance" modo="lista"><table class="tabela-padrao table"><caption class="sr-only">Perfis e alcance</caption><thead><tr><th scope="col">Perfil</th><th scope="col">Alcance nesta versão</th></tr></thead><tbody>
      <tr><td class="tabela-principal" data-rotulo="Perfil"><div class="tabela-valor">Administrador da Rede</div></td><td data-rotulo="Alcance nesta versão"><div class="tabela-valor">Escolas da própria rede, com permissão vigente.</div></td></tr>
      <tr><td class="tabela-principal" data-rotulo="Perfil"><div class="tabela-valor">Diretor Escolar</div></td><td data-rotulo="Alcance nesta versão"><div class="tabela-valor">Somente a própria escola, com permissão vigente.</div></td></tr>
      <tr><td class="tabela-principal" data-rotulo="Perfil"><div class="tabela-valor">Professor autônomo</div></td><td data-rotulo="Alcance nesta versão"><div class="tabela-valor">Somente o espaço pessoal do qual é proprietário.</div></td></tr>
      <tr><td class="tabela-principal" data-rotulo="Perfil"><div class="tabela-valor">Professor da escola, Coordenador e Diretor de Núcleo</div></td><td data-rotulo="Alcance nesta versão"><div class="tabela-valor">Não autorizam esta operação nesta versão. Procure o diretor da escola ou administrador da rede.</div></td></tr>
      <tr><td class="tabela-principal" data-rotulo="Perfil"><div class="tabela-valor">Equipe interna: Desenvolvedor, Suporte e Financeiro</div></td><td data-rotulo="Alcance nesta versão"><div class="tabela-valor">O acesso interno não concede autorização para copiar dados escolares.</div></td></tr>
    </tbody></table></x-tabela></div>
    <p>Ter acesso a uma prova ou turma não concede, por si só, autorização para enviá-la a outro espaço. Origem e destino precisam de decisões válidas; permissões retiradas ou vínculos vencidos bloqueiam a operação.</p>
  </details>
</section>
