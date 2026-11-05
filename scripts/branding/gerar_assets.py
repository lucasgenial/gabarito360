"""Gera a marca a partir do vetor oficial e dos TTF800; não usa fontes do sistema.

Instalar requirements.txt em ambiente isolado. --source-svg importa novamente o
arquivo oficial; execuções seguintes usam o SVG UTF8 normalizado versionado.
"""
import argparse
import hashlib
import io
import json
import math
from pathlib import Path
import re
import xml.etree.ElementTree as ET
from urllib.request import urlopen

from PIL import Image, ImageDraw, ImageFont
import resvg_py

ROOT = Path(__file__).resolve().parents[2]
PUBLIC = ROOT / 'apps/web/public'
BRAND = PUBLIC / 'brand'
SOURCE = ROOT / 'apps/web/resources/brand/gabarito360-icon.svg'
FONTS = ROOT / 'apps/api/omr/fonts'
NAVY, BLUE, WHITE = '#001534', '#0588FC', '#FFFFFF'


def hash_asset(p):
    # Git pode trocar CRLF/LF no checkout Windows; os vetores/JSON/licenças
    # têm hash canônico UTF8+LF. PNG/ICO/fontes continuam byte a byte.
    dados=p.read_text(encoding='utf-8').encode('utf-8') if p.suffix in ('.svg','.json','.webmanifest','.txt') else p.read_bytes()
    return hashlib.sha256(dados).hexdigest()


def gerar_fontes():
    from fontTools.ttLib import TTFont
    from fontTools.varLib.instancer import instantiateVariableFont
    pasta=SOURCE.parent/'fonts';web=PUBLIC/'fonts/brand';web.mkdir(parents=True,exist_ok=True)
    for dados in json.loads((pasta/'sources.json').read_text(encoding='utf-8')):
        raw=urlopen(dados['url'],timeout=30).read()
        if hashlib.sha256(raw).hexdigest()!=dados['sha256_variable']:
            raise ValueError('Fonte de origem diverge do hash fixado: '+dados['family'])
        fonte=instantiateVariableFont(TTFont(io.BytesIO(raw)),{'wght':800},inplace=False)
        fonte.recalcTimestamp=False
        fonte.save(FONTS/(dados['family']+'-ExtraBold.ttf'))
        fonte.flavor='woff2';fonte.save(web/(dados['family']+'-ExtraBold.woff2'))
        licenca=(pasta/(dados['family']+'-OFL.txt')).read_bytes()
        for destino in (FONTS,web): (destino/(dados['family']+'-OFL.txt')).write_bytes(licenca)


def normalizar(arquivo):
    raw = arquivo.read_bytes()
    texto = raw.decode('utf-16') if raw[:2] in (b'\xff\xfe', b'\xfe\xff') else raw.decode('utf-8-sig')
    texto = re.sub(r'<!DOCTYPE[^>]*>', '', texto)
    svg = ET.fromstring(texto)
    elementos = []
    for item in svg.iter():
        tag = item.tag.rsplit('}', 1)[-1]
        if tag == 'polygon':
            pontos = item.attrib['points'].split()
            d = 'M' + ' L'.join(pontos) + ' Z'
            elementos.append('<path class="g360-simbolo-check" fill="'+BLUE+'" d="'+d+'"/>')
        elif tag == 'path':
            # A geometria, a ordem de pintura e o traço do original são preservados.
            a = {k: v for k, v in item.attrib.items() if k in ('d', 'fill', 'stroke', 'stroke-width', 'stroke-miterlimit')}
            elementos.append('<path class="g360-simbolo-circulo" '+' '.join(k+'="'+v+'"' for k, v in a.items())+'/>')
    if len(elementos) != 2:
        raise ValueError('Esperados exatamente os dois elementos do símbolo oficial.')
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="'+svg.attrib['viewBox']+'" fill-rule="evenodd" clip-rule="evenodd">\n'+'\n'.join(elementos)+'\n</svg>\n'


def icone(svg, tamanho, escuro=False, maskable=False, fundo=None):
    if escuro:
        svg = svg.replace('fill="'+NAVY+'"', 'fill="'+WHITE+'"').replace('stroke="#373435"', 'stroke="'+WHITE+'"')
    # Bounding box inteiro dentro do círculo de segurança de diâmetro80%.
    fator = .78 / math.sqrt(1 + (11262.83 / 12421.36)**2) if maskable else .9
    largura = max(1, round(tamanho*fator))
    png = resvg_py.svg_to_bytes(svg_string=svg, width=largura, skip_system_fonts=True)
    arte = Image.open(io.BytesIO(png)).convert('RGBA')
    tela = Image.new('RGBA', (tamanho, tamanho), fundo or (255,255,255,0))
    tela.alpha_composite(arte, ((tamanho-arte.width)//2,(tamanho-arte.height)//2))
    return tela


def marca(svg, largura=1000, altura=200, escuro=False):
    tela = Image.new('RGBA',(largura,altura))
    simbolo = icone(svg,altura,escuro=escuro)
    tela.alpha_composite(simbolo,(0,0))
    desenho = ImageDraw.Draw(tela)
    fig = ImageFont.truetype(str(FONTS/'Figtree-ExtraBold.ttf'),round(altura*.48))
    albert = ImageFont.truetype(str(FONTS/'AlbertSans-ExtraBold.ttf'),round(altura*.48))
    x,y=altura+altura*.10,altura*.68
    for texto,fonte,cor in [('Gabarito',fig,WHITE if escuro else NAVY),('3',fig,BLUE),('6',albert,BLUE),('0',fig,BLUE)]:
        desenho.text((x,y),texto,font=fonte,fill=cor,anchor='ls')
        x+=desenho.textlength(texto,font=fonte)
    return tela.crop((0,0,min(largura,math.ceil(x+altura*.06)),altura))


def main():
    parser=argparse.ArgumentParser();parser.add_argument('--source-svg',type=Path);parser.add_argument('--fontes',action='store_true');args=parser.parse_args()
    SOURCE.parent.mkdir(parents=True,exist_ok=True);BRAND.mkdir(parents=True,exist_ok=True)
    if args.source_svg:
        SOURCE.write_text(normalizar(args.source_svg),encoding='utf-8')
    if args.fontes: gerar_fontes()
    svg=SOURCE.read_text(encoding='utf-8')
    (BRAND/'gabarito360-icon.svg').write_text(svg,encoding='utf-8')
    (BRAND/'gabarito360-icon-dark.svg').write_text(svg.replace('fill="'+NAVY+'"','fill="'+WHITE+'"').replace('stroke="#373435"','stroke="'+WHITE+'"'),encoding='utf-8')
    icone(svg,1024).save(BRAND/'gabarito360-icon.png')
    icone(svg,256,escuro=True).save(BRAND/'gabarito360-icon-dark.png')
    adaptativo=svg.replace('>','><style>@media(prefers-color-scheme:dark){.g360-simbolo-circulo{fill:#fff;stroke:#fff}}</style>',1)
    (PUBLIC/'favicon.svg').write_text(adaptativo,encoding='utf-8')
    for n in (16,32,48,96): icone(svg,n).save(PUBLIC/f'favicon-{n}x{n}.png')
    icone(svg,256).save(PUBLIC/'favicon.ico',sizes=[(16,16),(32,32),(48,48),(64,64),(128,128),(256,256)])
    icone(svg,180,fundo=WHITE).save(PUBLIC/'apple-touch-icon.png')
    for n in (192,512):
        imagem=icone(svg,n,fundo=WHITE)
        imagem.save(PUBLIC/f'android-chrome-{n}x{n}.png');imagem.save(PUBLIC/f'icon-{n}.png')
        icone(svg,n,maskable=True,fundo=WHITE).save(PUBLIC/f'maskable-icon-{n}.png')
    for escuro,nome in [(False,'gabarito360-email.png'),(True,'gabarito360-email-dark.png')]: marca(svg,escuro=escuro).save(BRAND/nome)
    og=Image.new('RGB',(1200,630),NAVY);logo=marca(svg,largura=1100,altura=180,escuro=True)
    og.paste(logo,(72,130),logo)
    desenho=ImageDraw.Draw(og);fonte=ImageFont.truetype(str(FONTS/'Figtree-ExtraBold.ttf'),33)
    desenho.text((88,374),'Correção de provas e relatórios educacionais',font=fonte,fill=WHITE)
    desenho.rectangle((88,455,1112,461),fill=BLUE)
    og.save(BRAND/'gabarito360-og.png')
    manifesto={'id':'/','name':'Gabarito360','short_name':'Gabarito360','lang':'pt-BR','start_url':'/','scope':'/','display':'standalone','theme_color':NAVY,'background_color':'#f4f6fa','icons':[{'src':f'/icon-{n}.png','sizes':f'{n}x{n}','type':'image/png','purpose':'any'} for n in (192,512)]+[{'src':f'/maskable-icon-{n}.png','sizes':f'{n}x{n}','type':'image/png','purpose':'maskable'} for n in (192,512)]}
    for nome in ('manifest.json','site.webmanifest'): (PUBLIC/nome).write_text(json.dumps(manifesto,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
    arquivos=[SOURCE,*BRAND.iterdir(),*PUBLIC.glob('favicon*'),*PUBLIC.glob('*icon*.png'),*PUBLIC.glob('android-chrome*'),PUBLIC/'manifest.json',PUBLIC/'site.webmanifest']
    arquivos += [FONTS/(n+'-ExtraBold.ttf') for n in ('Figtree','AlbertSans')]+list((PUBLIC/'fonts/brand').iterdir())
    registro={str(p.relative_to(ROOT)).replace('\\','/'):hash_asset(p) for p in arquivos if p.is_file()}
    (SOURCE.parent/'assets-sha256.json').write_text(json.dumps(dict(sorted(registro.items())),indent=2)+'\n',encoding='utf-8')
    print('Assets gerados; geometria oficial e TTF800 locais.')


if __name__=='__main__': main()
