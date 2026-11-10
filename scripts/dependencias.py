"""Arquivos de dependências (FN-078 subfase 4.2).

Um PR que só altera estes arquivos é manutenção de dependências (bibliotecas e ações do GitHub). Ele fica fora das regras
R-FN-NO-PR e R-VERSAO-PRINCIPIOS-FN, porque não muda o comportamento de uma funcionalidade.
"""
import re

ARQUIVO = re.compile(r'(^|/)(composer\.json|composer\.lock|package\.json|package-lock\.json|requirements[\w.-]*\.txt)$'
                     r'|^\.github/workflows/[\w.-]+\.ya?ml$')


def so_dependencias(arquivos):
    """True quando há arquivos e todos são manifestos ou travas de dependências."""
    arquivos = [a for a in arquivos if a]
    return bool(arquivos) and all(ARQUIVO.search(a) for a in arquivos)
