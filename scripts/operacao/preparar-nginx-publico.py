"""Gera uma configuração candidata, preservando sockets, logs e demais ajustes locais."""
from pathlib import Path
import sys

origem, destino = map(Path, sys.argv[1:])
assert origem.is_file() and not destino.exists()
texto = origem.read_text()
assert 'gabarito360-publico.conf' not in texto, 'Configuração já preparada; não duplicar.'
for nome, app, pagina in [('web', 'web', '/_g360_manutencao.html'), ('api', 'api', '@g360_api_manutencao')]:
    marcador = '    location @gabarito360_'+nome+' {'
    assert texto.count(marcador) == 1
    texto = texto.replace(marcador, marcador+'\n'
        '        if (-f /home/lucasdev/projetos/gabarito360/apps/'+app+'/storage/framework/down) { return 503; }\n'
        '        fastcgi_intercept_errors off;\n'
        '        error_page 502 503 504 =503 '+pagina+';')
marcador = '    client_max_body_size 20m;'
assert texto.count(marcador) == 1
texto = texto.replace(marcador, marcador+'\n\n    include /etc/nginx/snippets/gabarito360-publico.conf;')
with destino.open('x') as f:
    f.write(texto)
