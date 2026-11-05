"""Nginx real + FastCGI fictício: nenhuma parada de PHP ou alteração no sistema publicado."""
from pathlib import Path
import hashlib, json, os, re, shutil, socket, socketserver, struct, subprocess, tempfile, threading, time, unittest
from urllib.parse import urljoin
import urllib.request, urllib.error

ROOT = Path(__file__).resolve().parents[2]

class FastCGI(socketserver.BaseRequestHandler):
    status = 200
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
        resposta = (b'Status: 200 OK\r\nContent-Type: text/plain\r\n\r\nsistema-ativo' if self.status == 200
            else b'Status: 503 Service Unavailable\r\nContent-Type: application/json\r\n\r\n{"code":"falha-operacional"}')
        enviar(6, resposta)
        enviar(6, b'')
        enviar(3, b'\0'*8)

HELPER = ROOT/'scripts/operacao/assets-publicos.php'
PUBLIC = ROOT/'apps/web/public'
ORIGEM = 'https://homologa.exemplo.test'

def exportar_assets(publico, destino):
    destino.mkdir(parents=True, exist_ok=True)
    subprocess.run(['php',str(HELPER),'exportar',str(publico),str(destino),str(ROOT/'scripts/operacao/manutencao.html'),ORIGEM],check=True,capture_output=True)
    return json.loads((destino/'assets-manifest.json').read_text())

class AssetsPublicosTest(unittest.TestCase):
    def test_bundle_reproduzivel_sem_laravel_com_fontes_relativas_e_og_publico(self):
        self.assertIsNotNone(shutil.which('php'))
        with tempfile.TemporaryDirectory(prefix='g360-assets-') as temp:
            p=Path(temp);m=exportar_assets(PUBLIC,p/'a');n=exportar_assets(PUBLIC,p/'b')
            self.assertEqual(m,n)
            prefixo='/_institucional/marca-'+m['id']+'/'
            bundle=p/'a'/('marca-'+m['id'])
            subprocess.run(['php',str(HELPER),'verificar',str(bundle),str(p/'a/assets-manifest.json')],check=True)
            for rel,sha in m['hashes'].items():
                self.assertEqual(hashlib.sha256((bundle/rel).read_bytes()).hexdigest(),sha)
            css=(bundle/'css/marca.css').read_text()
            for alvo in re.findall(r"url\(['\"]?([^)'\"]+)",css):
                caminho=urljoin(prefixo+'css/marca.css',alvo)
                self.assertTrue((bundle/caminho.removeprefix(prefixo)).is_file(),caminho)
            manifesto=json.loads((bundle/'site.webmanifest').read_text())
            self.assertTrue(all(i['src'].startswith(prefixo) for i in manifesto['icons']))
            html=(p/'a/manutencao.html').read_text()
            self.assertIn(ORIGEM+prefixo+'brand/gabarito360-og.png',html)
            self.assertIn('g360-wordmark',html);self.assertIn('g360-six',html)
            self.assertNotIn('__G360_',html);self.assertNotIn('<form',html);self.assertNotIn('csrf',html)
            self.assertNotIn('class="selo"',html)
            codigo='require $argv[1]; echo g360_publicos_html($argv[3],g360_publicos_contrato($argv[2]),$argv[4]);'
            entrada='<link rel="canonical" href="'+ORIGEM+'/"><meta property="og:url" content="'+ORIGEM+'/"><link href="'+ORIGEM+'/css/marca.css?v=1"><meta content="'+ORIGEM+'/brand/gabarito360-og.png"><a href="'+ORIGEM+'/login">Entrar</a>'
            saida=subprocess.check_output(['php','-r',codigo,str(HELPER),str(PUBLIC),entrada,ORIGEM],text=True)
            self.assertIn('href="'+prefixo+'css/marca.css"',saida)
            self.assertIn('href="/login"',saida)
            self.assertIn('content="'+ORIGEM+prefixo+'brand/gabarito360-og.png"',saida)
            self.assertIn('<link rel="canonical" href="'+ORIGEM+'/">',saida)
            self.assertIn('property="og:url" content="'+ORIGEM+'/"',saida)

    def test_whitelist_recusa_symlink_e_integridade_alterada(self):
        with tempfile.TemporaryDirectory(prefix='g360-assets-seguro-') as temp:
            p=Path(temp);publico=p/'public';publico.mkdir()
            lista=json.loads(subprocess.check_output(['php',str(HELPER),'lista'],text=True))
            for rel in lista:
                alvo=publico/rel;alvo.parent.mkdir(parents=True,exist_ok=True);shutil.copyfile(PUBLIC/rel,alvo)
            (publico/'segredo.txt').write_text('nao-publicar')
            m=exportar_assets(publico,p/'a');bundle=p/'a'/('marca-'+m['id'])
            self.assertFalse((bundle/'segredo.txt').exists())
            (bundle/'extra.txt').write_text('fora-do-contrato')
            self.assertNotEqual(subprocess.run(['php',str(HELPER),'verificar',str(bundle),str(p/'a/assets-manifest.json')],capture_output=True).returncode,0)
            (bundle/'extra.txt').unlink()
            # Uma nova marca gera outro endereço; o bundle anterior continua intacto.
            (publico/'css/marca.css').write_text((publico/'css/marca.css').read_text()+'\n/* nova versao */\n')
            n=exportar_assets(publico,p/'b')
            self.assertNotEqual(m['id'],n['id'])
            subprocess.run(['php',str(HELPER),'verificar',str(bundle),str(p/'a/assets-manifest.json')],check=True)
            (bundle/'css/marca.css').write_text('alterada')
            self.assertNotEqual(subprocess.run(['php',str(HELPER),'verificar',str(bundle),str(p/'a/assets-manifest.json')],capture_output=True).returncode,0)
            (publico/'css/marca.css').unlink();(publico/'css/marca.css').symlink_to(PUBLIC/'css/marca.css')
            self.assertNotEqual(subprocess.run(['php',str(HELPER),'id',str(publico)],capture_output=True).returncode,0)

    @unittest.skipUnless(hasattr(os,'geteuid') and os.geteuid()==0,'Publisher usa root; exportador puro não exige root.')
    def test_falha_exportacao_nao_troca_pointer_ativo(self):
        with tempfile.TemporaryDirectory(prefix='g360-publisher-') as temp:
            p=Path(temp);p.chmod(0o755);raiz=p/'release';publico=raiz/'apps/web/public';publico.mkdir(parents=True)
            lista=json.loads(subprocess.check_output(['php',str(HELPER),'lista'],text=True))
            for rel in lista:
                alvo=publico/rel;alvo.parent.mkdir(parents=True,exist_ok=True);shutil.copyfile(PUBLIC/rel,alvo)
            base=p/'publicado';base.mkdir();antiga=base/'antiga';antiga.mkdir();(antiga/'index.html').write_text('anterior')
            (base/'current').symlink_to(antiga,target_is_directory=True)
            # Sem vendor/API, a exportação CLI falha e jamais pode trocar a versão servida.
            r=subprocess.run(['bash',str(ROOT/'scripts/operacao/publicar-home.sh'),str(raiz),str(base),ORIGEM],capture_output=True)
            self.assertNotEqual(r.returncode,0)
            self.assertEqual((base/'current').resolve(),antiga)
            self.assertEqual((base/'current/index.html').read_text(),'anterior')

class PublicoNginxTest(unittest.TestCase):
    def test_home_independente_manutencao_e_fpm_indisponivel(self):
        nginx = shutil.which('nginx')
        self.assertIsNotNone(nginx, 'Instale nginx para executar a integração real.')
        with tempfile.TemporaryDirectory(prefix='g360-publico-') as temp:
            p = Path(temp); p.chmod(0o755)
            for d in ['site', 'assets', 'web/storage/framework', 'api/storage/framework', 'web/public']:
                (p/d).mkdir(parents=True, exist_ok=True)
            m=exportar_assets(PUBLIC,p/'site');prefixo='/_institucional/marca-'+m['id']+'/'
            (p/'site/index.html').write_text('<h1>Apresentação pública</h1><link rel="stylesheet" href="'+prefixo+'css/marca.css">')
            shutil.copytree(p/'site'/('marca-'+m['id']),p/'assets'/('marca-'+m['id']))
            css = m['css_sha']
            shutil.copy(p/'site/app.css', p/'assets'/f'{css}.css')
            css_url = '/_institucional/'+css+'.css'
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
            nginx_conf=Path(os.environ.get('G360_NGINX_CONF_DIR','/etc/nginx'))
            server = server.replace('include fastcgi_params;', 'include '+str(nginx_conf/'fastcgi_params')+';')
            server = server.replace('unix:/run/php/php8.3-fpm.sock', 'unix:'+str(sock))
            server = server.replace('access_log /var/log/nginx/gabarito360.access.log;', 'access_log off;')
            server = server.replace('error_log /var/log/nginx/gabarito360.error.log;', f'error_log {p}/error.log;')
            (p/'nginx.conf').write_text(f'worker_processes 1; pid {p}/nginx.pid; events {{ worker_connections 32; }} http {{ include {nginx_conf}/mime.types; '+server+' }')
            proc = subprocess.Popen([nginx, '-p', str(p), '-c', str(p/'nginx.conf'), '-g', 'daemon off;'], stdout=subprocess.DEVNULL, stderr=subprocess.PIPE)
            def get(path, method='GET', binario=False):
                try: response = urllib.request.urlopen(urllib.request.Request(f'http://127.0.0.1:{port}'+path, method=method), timeout=3)
                except urllib.error.HTTPError as e: response = e
                with response:
                    corpo=response.read()
                    return response.status,dict(response.headers),corpo if binario else corpo.decode()
            try:
                for _ in range(50):
                    try: get('/'); break
                    except urllib.error.URLError: time.sleep(.05)
                self.assertEqual(get('/')[0], 200)
                self.assertEqual(get('/login')[2], 'sistema-ativo')
                self.assertEqual(get('/api/v1/health')[0], 200)
                FastCGI.status = 503
                for path in ['/login', '/api/v1/health']:
                    status, _, body = get(path)
                    self.assertEqual(status, 503)
                    self.assertEqual(json.loads(body)['code'], 'falha-operacional')
                FastCGI.status = 200
                for app in ['web', 'api']: (p/app/'storage/framework/down').write_text('{}')
                self.assertEqual(get('/?origem=teste')[0], 200)
                self.assertEqual(get('/', 'POST')[0], 405)
                self.assertEqual(get(css_url)[0], 200)
                status, headers, body = get('/login')
                self.assertEqual(status, 503); self.assertIn('Estamos atualizando', body)
                self.assertEqual(headers['Retry-After'], '60'); self.assertEqual(headers['Cache-Control'], 'no-store')
                self.assertEqual(get('/_g360_manutencao.html')[0], 404)
                self.assertIn(prefixo+'css/marca.css',body)
                self.assertNotIn('__G360_',body)
                for rel,tipo in [('css/marca.css','text/css'),('css/app.css','text/css'),('css/apresentacao-publica.css','text/css'),('js/app.js','application/javascript'),('js/apresentacao-publica.js','application/javascript'),('brand/gabarito360-icon.svg','image/svg+xml'),('brand/gabarito360-og.png','image/png'),('fonts/brand/Figtree-ExtraBold.woff2','font/woff2'),('favicon.ico','image/x-icon'),('site.webmanifest','application/manifest+json')]:
                    st,h,b=get(prefixo+rel,binario=True);self.assertEqual(st,200,rel);self.assertTrue(b,rel);self.assertIn(tipo,h['Content-Type']);self.assertIn('immutable',h['Cache-Control'])
                self.assertEqual(get(prefixo+'css/marca.css','HEAD')[0],200)
                for rel in ['segredo.txt','.env','foo.php','css/ausente.css']:
                    self.assertEqual(get(prefixo+rel)[0],404,rel)
                self.assertIn('manutenção', json.loads(get('/api/v1/health')[2])['message'])
                for app in ['web', 'api']: (p/app/'storage/framework/down').unlink()
                backend.shutdown(); backend.server_close(); thread.join(); sock.unlink()
                self.assertEqual(get('/')[0], 200)
                self.assertEqual(get(css_url)[0], 200)
                self.assertEqual(get('/login')[0], 503)
                self.assertEqual(get('/api/v1/health')[0], 503)
                self.assertEqual(get(prefixo+'brand/gabarito360-og.png',binario=True)[0],200)
                self.assertEqual(get(prefixo+'fonts/brand/AlbertSans-ExtraBold.woff2',binario=True)[0],200)
            finally:
                FastCGI.status = 200
                proc.terminate(); proc.communicate(timeout=5)
                if thread.is_alive(): backend.shutdown(); backend.server_close(); thread.join()

if __name__ == '__main__': unittest.main()
