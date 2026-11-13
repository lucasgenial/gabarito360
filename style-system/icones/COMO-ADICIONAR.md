# G360 Icones — como adicionar um ícone novo

A família foi aprovada em 13/11/2026 com 141 ícones (U+E001 a U+E08D). Ícone novo entra assim:

1. **Confira se o conceito já existe.** Procure no `catalogo.json` e no mapa de metáforas da `DIRETRIZ.md`, seção 4. Um conceito tem um desenho e um id.
2. **Escolha a metáfora.** Ela não pode colidir com a tabela da diretriz. Quando o conceito é "base + modificador", use selo.
3. **Desenhe no lote do assunto** (`lotes/loteN.py`) ou num lote novo (`lotes/lote8.py`). Use os motivos de `motivos.py`. Siga a especificação: grade 24, traço 1,8, pontas redondas e as classes de construção.
4. **Confira na prancha** em 16, 20, 24 e 32 px, nos dois temas e nas duas variantes, com o checklist da diretriz, seção 6.
5. **Compile:**
   - `python3 gerar_fontes.py`
   - `python3 compilar.py`. Ele acrescenta o código novo no **fim** do `registro.json`, e nenhum código existente muda.
   - fantasticon com os dois `fonte/fantasticon-*.json`
   - `python3 gerar_css.py`
6. **Atualize** o catálogo, o mapa de metáforas (se a metáfora for nova) e a prancha (`prancha_final.py`).

Ferramentas: Python com picosvg, skia-pathops, fonttools e brotli; Node com fantasticon@3; Chrome sem janela para as pranchas.

O pacote completo, com as fontes compiladas e os SVGs, está no `g360-icones-familia.zip` entregue na conversa "icone".
