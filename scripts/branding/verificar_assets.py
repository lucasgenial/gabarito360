"""Valida dimensões, referências, fontes e a área segura dos ícones gerados."""
import hashlib
import json
from pathlib import Path
import xml.etree.ElementTree as ET

from PIL import Image, ImageChops
from fontTools.ttLib import TTFont

ROOT=Path(__file__).resolve().parents[2]
PUBLIC=ROOT/'apps/web/public'
checks=0


def conferir(condicao,mensagem):
    global checks
    if not condicao: raise AssertionError(mensagem)
    checks+=1


manifesto=json.loads((PUBLIC/'site.webmanifest').read_text(encoding='utf-8'))
conferir(manifesto==json.loads((PUBLIC/'manifest.json').read_text(encoding='utf-8')),'Manifestos divergem')
for item in manifesto['icons']:
    p=PUBLIC/item['src'].lstrip('/');im=Image.open(p).convert('RGB');n=int(item['sizes'].split('x')[0])
    conferir(im.size==(n,n),'Dimensão inválida: '+p.name)
    if item['purpose']=='maskable':
        bbox=ImageChops.difference(im,Image.new('RGB',im.size,'white')).getbbox()
        conferir(bbox is not None,'Maskable vazio')
        x0,y0,x1,y1=bbox
        conferir(all((x-n/2)**2+(y-n/2)**2<=(n*.4)**2 for x in (x0,x1) for y in (y0,y1)),'Símbolo fora do círculo seguro: '+p.name)
for n in (16,32,48,96): conferir(Image.open(PUBLIC/f'favicon-{n}x{n}.png').size==(n,n),'Favicon inválido')
conferir(Image.open(PUBLIC/'apple-touch-icon.png').size==(180,180),'Apple icon inválido')
conferir(Image.open(PUBLIC/'brand/gabarito360-og.png').size==(1200,630),'OG inválido')
svg=(ROOT/'apps/web/resources/brand/gabarito360-icon.svg').read_text(encoding='utf-8')
conferir('DOCTYPE' not in svg and 'metadata' not in svg and '<script' not in svg,'SVG não normalizado')
conferir(len(list(ET.fromstring(svg)))==2,'Geometria deve conter os dois elementos oficiais')
for familia in ('Figtree','AlbertSans'):
    ttf=ROOT/f'apps/api/omr/fonts/{familia}-ExtraBold.ttf';woff=PUBLIC/f'fonts/brand/{familia}-ExtraBold.woff2'
    conferir(TTFont(ttf)['OS/2'].usWeightClass==800,'Peso TTF errado')
    conferir(TTFont(woff)['OS/2'].usWeightClass==800,'Peso WOFF2 errado')
    conferir((ttf.parent/(familia+'-OFL.txt')).is_file() and (woff.parent/(familia+'-OFL.txt')).is_file(),'Licença ausente')
for caminho,esperado in json.loads((ROOT/'apps/web/resources/brand/assets-sha256.json').read_text(encoding='utf-8')).items():
    p=ROOT/caminho
    dados=p.read_text(encoding='utf-8').encode('utf-8') if p.suffix in ('.svg','.json','.webmanifest','.txt') else p.read_bytes()
    conferir(hashlib.sha256(dados).hexdigest()==esperado,'Hash diverge: '+caminho)
print(f'Marca: {checks} verificações aprovadas (incluindo maskable e fontes800).')
