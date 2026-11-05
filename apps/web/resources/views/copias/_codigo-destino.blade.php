@php
  $nomeReceber=['material'=>'Receber materiais','cadastro'=>'Receber cadastro','resultado'=>'Receber resultados'][$modalidade];
  $rotaReceber=['material'=>'copias.material.receber','cadastro'=>'copias.cadastro.receber','resultado'=>'copias.resultado.receber'][$modalidade];
@endphp
<section aria-label="Como obter o código do destino">
  <h2>Ainda não tem o código?</h2>
  <p><strong>Quem recebe precisa fazer o primeiro passo na própria conta:</strong></p>
  <ol style="padding-left:24px;line-height:1.7">
    <li>Selecionar o vínculo da escola ou espaço pessoal que receberá a cópia.</li>
    <li>Abrir <strong>Cópias → Receber uma cópia → {{ $nomeReceber }}</strong>.</li>
    <li>Escolher o espaço e clicar em <strong>Gerar código de destino</strong>.</li>
    <li>Compartilhar o código com você. Cole-o no campo acima e confira o nome do destino na próxima tela.</li>
  </ol>
  <p>Use um código desta modalidade: um código de materiais não serve para cadastros ou resultados. O código tem validade curta e serve para uma solicitação; se vencer, o destinatário deve gerar outro.</p>
  <p>Se você administra origem e destino, abra o recebimento em outra aba e use <strong>Trocar vínculo</strong> para escolher o destino. Mantenha esta seleção aberta na aba de origem.</p>
  @if(\App\Support\Acesso::pode('copia.autorizar_destino'))
    <a class="btn btn-secondary" target="_blank" rel="noopener" href="{{ route($rotaReceber,[]) }}">Abrir {{ $nomeReceber }} em outra aba</a>
  @endif
  <p>O código apenas indica onde receber. A autorização de entrada será registrada depois que a solicitação estiver preparada e a saída autorizada.</p>
</section>
