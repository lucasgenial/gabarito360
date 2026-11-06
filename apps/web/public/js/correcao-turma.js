var correcao_turmaConfig = JSON.parse(document.getElementById('correcao-turma-config').textContent);

(function () {
  var chk = document.getElementById('so-falta');
  if (!chk) return;
  chk.addEventListener('change', function () {
    document.querySelectorAll('.quadro tbody tr').forEach(function (tr) {
      tr.style.display = chk.checked && tr.dataset.falta !== '1' ? 'none' : '';
    });
  });
})();
