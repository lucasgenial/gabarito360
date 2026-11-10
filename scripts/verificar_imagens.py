"""Regra do responsável (09/11/2026): nenhum print da aplicação é mantido no repositório.

Imagens versionadas só são aceitas como assets da aplicação ou desenhos de design.
Prints/capturas de tela da aplicação (evidências, revisões, PRs) ficam fora do Git e de serviços de imagem.
"""
import subprocess
import sys

EXTENSOES = ('.png', '.jpg', '.jpeg', '.webp', '.gif', '.bmp', '.tif', '.tiff', '.heic', '.avif')
PERMITIDOS = (
    'apps/web/public/',          # assets servidos pela aplicação (marca, avatares, ícones)
    'apps/web/resources/images/',
    'mockups/',                  # desenhos de design de referência, não prints
    'style-system/',
)

arquivos = subprocess.run(['git', 'ls-files', '-z'], capture_output=True, check=True).stdout.decode().split('\0')
fora = [a for a in arquivos if a.lower().endswith(EXTENSOES) and not a.startswith(PERMITIDOS)]
if fora:
    print('Imagens fora das pastas de assets permitidas (prints da aplicação não podem ser versionados):')
    for a in fora:
        print('  ' + a)
    sys.exit(1)
print('Nenhuma imagem fora das pastas de assets permitidas.')
