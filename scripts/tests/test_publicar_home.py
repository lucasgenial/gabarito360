"""Publicação atômica: falha de atualização não remove a apresentação já disponível."""
from pathlib import Path
import hashlib, os, shutil, subprocess, tempfile, unittest

ROOT = Path(__file__).resolve().parents[2]

class PublicarHomeTest(unittest.TestCase):
    def test_falha_preserva_html_e_css_anteriores(self):
        self.assertEqual(os.geteuid(), 0, 'Executar como root no ambiente de teste.')
        with tempfile.TemporaryDirectory(prefix='g360-export-test-') as temp:
            p = Path(temp)
            app = p/'app'; base = p/'publico'; aux = p/'aux'; aux.mkdir()
            (app/'apps/web/public/css').mkdir(parents=True)
            css = app/'apps/web/public/css/app.css'; css.write_text('body{color:blue}')
            for nome in ['publicar-home.sh', 'manutencao.html']:
                shutil.copy(ROOT/'scripts/operacao'/nome, aux/nome)
            html = '<html>Versão aprovada /_institucional/'+hashlib.sha256(css.read_bytes()).hexdigest()+'.css</html>'
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
