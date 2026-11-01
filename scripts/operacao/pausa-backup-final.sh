#!/usr/bin/env bash
# Hook síncrono do B5. Confirmação só após backup e verificação pelo operador no host.
set -Eeuo pipefail
exec python3 - "$@" <<'PY'
import json, os, re, stat, sys, tempfile, time, uuid
from pathlib import Path
assert os.geteuid() == 0 and len(sys.argv) == 4
raiz, anterior, alvo = sys.argv[1:]
assert all(re.fullmatch(r"[0-9a-f]{40}", x) for x in (anterior, alvo))
timeout = int(os.environ.get("G360_BACKUP_TIMEOUT", "1800"))
assert 1 <= timeout <= 3600
os.umask(0o077)
pasta = Path(tempfile.mkdtemp(prefix="g360-backup-final-", dir="/root"))
checkpoint = str(uuid.uuid4())
pronto = {"checkpoint": checkpoint, "raiz": raiz, "anterior": anterior, "alvo": alvo,
          "criado_utc": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime()),
          "limite_segundos": timeout}
(pasta / "pronto.json").write_text(json.dumps(pronto, indent=2) + "\n")
print("CHECKPOINT AGUARDANDO: " + str(pasta), flush=True)
fim = time.monotonic() + timeout
recibo = pasta / "confirmado.json"
try:
    while not recibo.exists():
        if time.monotonic() >= fim:
            raise TimeoutError("Backup não confirmado dentro do prazo")
        time.sleep(1)
    fd = os.open(recibo, os.O_RDONLY | os.O_NOFOLLOW)
    with os.fdopen(fd) as arquivo:
        st = os.fstat(arquivo.fileno())
        assert stat.S_ISREG(st.st_mode) and st.st_uid == 0 and st.st_mode & 0o077 == 0
        assert 0 < st.st_size <= 16384
        dados = json.load(arquivo)
    assert all(dados.get(k) == pronto[k] for k in ("checkpoint", "anterior", "alvo"))
    assert dados.get("verificado") is True
    arquivos = dados["arquivos"]
    assert len(arquivos) == 2 and {a["papel"] for a in arquivos} == {"banco", "aplicacao"}
    assert len({a["arquivo"] for a in arquivos}) == 2
    for a in arquivos:
        assert isinstance(a["arquivo"], str) and a["arquivo"].startswith("/")
        assert re.fullmatch(r"[0-9a-f]{64}", a["sha256"])
        assert type(a["bytes"]) is int and a["bytes"] > 0
    # Os arquivos ficam no host: o recibo atesta as verificações feitas lá.
    # Não afirmar que este hook leu/hashou arquivos remotos.
    (pasta / "aceito.json").write_text(json.dumps(dados, indent=2) + "\n")
    print("CHECKPOINT CONFIRMADO: " + str(pasta), flush=True)
except Exception as erro:
    (pasta / "falhou.txt").write_text(type(erro).__name__ + "\n")
    print("Checkpoint recusado ou expirado; manutenção deve permanecer.", file=sys.stderr)
    sys.exit(1)
PY
