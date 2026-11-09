<dialog id="retirar-modal" class="troca-contexto-modal no-print" aria-labelledby="retirar-titulo" aria-describedby="retirar-descricao">
  <form id="retirar-form">
    <h2 id="retirar-titulo">Retirar correção vigente?</h2><p id="retirar-aluno"></p><p id="retirar-nota"></p>
    <p id="retirar-descricao">A foto, as respostas, a nota e as revisões serão preservadas no histórico. O resultado deixará de ficar disponível como vigente.</p>
    <details><summary>Quero apenas melhorar a foto</summary><p>Envie outra imagem e confira a nova tentativa. Não é necessário retirar a correção atual para reenviar uma foto.</p></details>
    <label for="retirar-motivo">Motivo da retirada</label><textarea id="retirar-motivo" class="input" minlength="10" maxlength="500" required></textarea>
    <label class="retirar-confirmacao"><input type="checkbox" id="retirar-confirmar" required> Entendo que o resultado atual deixará de ser vigente.</label>
    <div class="retirar-acoes"><button type="button" class="btn btn-secondary" id="retirar-cancelar" autofocus>Cancelar</button><button type="submit" class="btn btn-danger">Retirar correção</button></div>
  </form>
</dialog>
@push('scripts')<script src="@ativo('js/retirar-cartao.js')"></script>@endpush
