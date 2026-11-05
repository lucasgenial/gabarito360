"""Publicação atômica: falha de atualização não remove a apresentação já disponível."""
from pathlib import Path
import json, os, shutil, subprocess, tempfile, unittest

ROOT = Path(__file__).resolve().parents[2]

class PublicarHomeTest(unittest.TestCase):
    def test_falha_preserva_html_e_css_anteriores(self):
        self.assertEqual(os.geteuid(), 0, 'Executar como root no ambiente de teste.')
        with tempfile.TemporaryDirectory(prefix='g360-export-test-') as temp:
            p = Path(temp); p.chmod(0o755)
            app = p/'app'; base = p/'publico'; aux = p/'aux'; aux.mkdir()
            publico = app/'apps/web/public'; publico.mkdir(parents=True)
            helper = ROOT/'scripts/operacao/assets-publicos.php'
            lista = json.loads(subprocess.check_output(['php', str(helper), 'lista'], text=True))
            for nome in lista:
                alvo = publico/nome; alvo.parent.mkdir(parents=True, exist_ok=True)
                shutil.copyfile(ROOT/'apps/web/public'/nome, alvo)
            css = app/'apps/web/public/css/app.css'; css.write_text('body{color:blue}')
            for nome in ['publicar-home.sh', 'manutencao.html', 'assets-publicos.php']:
                shutil.copy(ROOT/'scripts/operacao'/nome, aux/nome)
            marca = subprocess.check_output(['php', str(helper), 'id', str(publico)], text=True).strip()
            html = '<html>Versão aprovada /_institucional/marca-'+marca+'/css/app.css /_institucional/marca-'+marca+'/css/marca.css</html>'
            exporter = aux/'exportar-home.php'; exporter.write_text('<?php echo '+repr(html)+';')
            args = ['bash', str(aux/'publicar-home.sh'), str(app), str(base)]
            subprocess.run(args, check=True, capture_output=True)
            anterior = (base/'current').resolve()
            self.assertEqual((base/'current/app.css').read_text(), 'body{color:blue}')
            css.write_text('body{color:red}')
            exporter.write_text('<?php exit(1);')
            self.assertNotEqual(subprocess.run(args, capture_output=True).returncode, 0)
            self.assertEqual((base/'current').resolve(), anterior)
            self.assertEqual((base/'current/index.html').read_text(), html)
            self.assertEqual((base/'current/app.css').read_text(), 'body{color:blue}')

if __name__ == '__main__': unittest.main()
