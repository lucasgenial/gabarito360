"""Nginx real + FastCGI fictício: nenhuma parada de PHP ou alteração no sistema publicado."""
from pathlib import Path
import hashlib, json, shutil, socket, socketserver, struct, subprocess, tempfile, threading, time, unittest
import urllib.request, urllib.error

ROOT = Path(__file__).resolve().parents[2]

class FastCGI(socketserver.BaseRequestHandler):
    def handle(self):
        def ler(n):
            b = b''
            while len(b) < n:
                d = self.request.recv(n-len(b))
                if not d: raise EOFError()
                b += d
            return b
        while True:
            _, tipo, rid, size, pad, _ = struct.unpack('!BBHHBB', ler(8))
            ler(size+pad)
            if tipo == 5 and size == 0: break
        def enviar(tipo, data):
            self.request.sendall(struct.pack('!BBHHBB', 1, tipo, rid, len(data), 0, 0)+data)
        enviar(6, b'Status: 200 OK\r\nContent-Type: text/plain\r\n\r\nsistema-ativo')
        enviar(6, b'')
        enviar(3, b'\0'*8)

class PublicoNginxTest(unittest.TestCase):
    def test_home_independente_manutencao_e_fpm_indisponivel(self):
        nginx = shutil.which('nginx')
        self.assertIsNotNone(nginx, 'Instale nginx para executar a integração real.')
        with tempfile.TemporaryDirectory(prefix='g360-publico-') as temp:
            p = Path(temp); p.chmod(0o755)
            for d in ['site', 'assets', 'web/storage/framework', 'api/storage/framework', 'web/public']:
                (p/d).mkdir(parents=True, exist_ok=True)
            (p/'site/index.html').write_text('<h1>Apresentação pública</h1>')
            (p/'site/app.css').write_text('body{color:#172b4d}')
            css = hashlib.sha256((p/'site/app.css').read_bytes()).hexdigest()
            shutil.copy(p/'site/app.css', p/'assets'/f'{css}.css')
            css_url = '/_institucional/'+css+'.css'
            shutil.copy(ROOT/'scripts/operacao/manutencao.html', p/'site/manutencao.html')
            sock = p/'fpm.sock'
            backend = socketserver.UnixStreamServer(str(sock), FastCGI)
            sock.chmod(0o777)
            thread = threading.Thread(target=backend.serve_forever, daemon=True); thread.start()
            with socket.socket() as probe:
                probe.bind(('127.0.0.1', 0)); port = probe.getsockname()[1]
            snippet = (ROOT/'deploy/nginx/publico-independente.conf').read_text().replace('/var/www/gabarito360-publico/current', str(p/'site'))
            snippet = snippet.replace('/var/www/gabarito360-publico/assets', str(p/'assets'))
            (p/'publico.conf').write_text(snippet)
            source = p/'original.conf'
            source.write_text((ROOT/'deploy/nginx/gabarito360.conf').read_text())
            subprocess.run(['python3', str(ROOT/'scripts/operacao/preparar-nginx-publico.py'), str(source), str(p/'server.conf')], check=True)
            server = (p/'server.conf').read_text()
            server = server.replace('listen 80 default_server;', f'listen 127.0.0.1:{port};').replace('listen [::]:80 default_server;', '')
            server = server.replace('/home/lucasdev/projetos/gabarito360/apps', str(p))
            server = server.replace('/etc/nginx/snippets/gabarito360-publico.conf', str(p/'publico.conf'))
            server = server.replace('include fastcgi_params;', 'include /etc/nginx/fastcgi_params;')
            server = server.replace('unix:/run/php/php8.3-fpm.sock', 'unix:'+str(sock))
            server = server.replace('access_log /var/log/nginx/gabarito360.access.log;', 'access_log off;')
            server = server.replace('error_log /var/log/nginx/gabarito360.error.log;', f'error_log {p}/error.log;')
            (p/'nginx.conf').write_text(f'worker_processes 1; pid {p}/nginx.pid; events {{ worker_connections 32; }} http {{ include /etc/nginx/mime.types; '+server+' }')
            proc = subprocess.Popen([nginx, '-p', str(p), '-c', str(p/'nginx.conf'), '-g', 'daemon off;'], stdout=subprocess.DEVNULL, stderr=subprocess.PIPE)
            def get(path, method='GET'):
                try: response = urllib.request.urlopen(urllib.request.Request(f'http://127.0.0.1:{port}'+path, method=method), timeout=3)
                except urllib.error.HTTPError as e: response = e
                with response: return response.status, dict(response.headers), response.read().decode()
            try:
                for _ in range(50):
                    try: get('/'); break
                    except urllib.error.URLError: time.sleep(.05)
                self.assertEqual(get('/')[0], 200)
                self.assertEqual(get('/login')[2], 'sistema-ativo')
                self.assertEqual(get('/api/v1/health')[0], 200)
                for app in ['web', 'api']: (p/app/'storage/framework/down').write_text('{}')
                self.assertEqual(get('/?origem=teste')[0], 200)
                self.assertEqual(get('/', 'POST')[0], 405)
                self.assertEqual(get(css_url)[0], 200)
                status, headers, body = get('/login')
                self.assertEqual(status, 503); self.assertIn('Estamos atualizando', body)
                self.assertEqual(headers['Retry-After'], '60'); self.assertEqual(headers['Cache-Control'], 'no-store')
                self.assertEqual(get('/_g360_manutencao.html')[0], 404)
                self.assertIn('manutenção', json.loads(get('/api/v1/health')[2])['message'])
                for app in ['web', 'api']: (p/app/'storage/framework/down').unlink()
                backend.shutdown(); backend.server_close(); thread.join(); sock.unlink()
                self.assertEqual(get('/')[0], 200)
                self.assertEqual(get(css_url)[0], 200)
                self.assertEqual(get('/login')[0], 503)
                self.assertEqual(get('/api/v1/health')[0], 503)
            finally:
                proc.terminate(); proc.communicate(timeout=5)
                if thread.is_alive(): backend.shutdown(); backend.server_close(); thread.join()

if __name__ == '__main__': unittest.main()
