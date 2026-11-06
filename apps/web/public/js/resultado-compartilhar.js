var resultado_compartilharConfig = JSON.parse(document.getElementById('resultado-compartilhar-config').textContent);

// O relatório pode ser exportado; o cartão digitalizado fica fora da impressão.
document.querySelectorAll('#res-menu-relatorio a').forEach(function(a){a.addEventListener('click',function(){document.getElementById('res-menu-relatorio').open=false;});});
document.addEventListener('click',function(e){var m=document.getElementById('res-menu-relatorio');if(!m.contains(e.target))m.open=false;});
document.getElementById('res-menu-relatorio').addEventListener('keydown',function(e){if(e.key==='Escape'){this.open=false;this.querySelector('summary').focus();}});

// Envia o PDF pelo compartilhamento do celular (WhatsApp aparece na lista); sem suporte a
// arquivos, baixa o PDF e abre o WhatsApp com o resumo para anexar.
(function () {
  var btn = document.getElementById('btn-whats');
  var PDF_URL = resultado_compartilharConfig.pdf;
  var TEXTO = resultado_compartilharConfig.texto;
  btn.addEventListener('click', function () {
    var rotulo = btn.innerHTML;
    var aviso=window.G360Toast?.carregando('Preparando o relatório para compartilhar.');
    btn.disabled = true; btn.textContent = 'Gerando PDF…';
    G360Requisicao(PDF_URL, { credentials: 'same-origin' })
      .then(function (r) { if (!r.ok) throw new Error('falha ao gerar o PDF'); return r.blob(); })
      .then(function (blob) {
        var nome = resultado_compartilharConfig.nomeArquivo;
        var arq = new File([blob], nome, { type: 'application/pdf' });
        if (navigator.canShare && navigator.canShare({ files: [arq] })) {
          return navigator.share({ files: [arq], title: 'Resultado', text: TEXTO }).catch(function () {});
        }
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob); a.download = nome; document.body.appendChild(a); a.click(); a.remove();setTimeout(function(){URL.revokeObjectURL(a.href);},1000);
        window.open('https://wa.me/?text=' + encodeURIComponent(TEXTO + '\n(PDF em anexo)'), '_blank');
      })
      .then(function(){if(aviso)window.G360Toast.sucesso('Relatório preparado. Conclua o envio no aplicativo escolhido.',{id:aviso});})
      .catch(function (e) { if(window.G360Toast)window.G360Toast.erro('Não foi possível preparar o relatório: '+e.message,{id:aviso});else alert('Não foi possível enviar: ' + e.message); })
      .finally(function () { btn.disabled = false; btn.innerHTML = rotulo; });
  });
})();
