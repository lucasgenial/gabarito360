# OMR Gabarito360

Contrato executavel isolado do leitor OMR. O modulo recebe uma imagem e uma
configuracao versionada, retorna JSON estruturado e nunca confirma uma leitura.

```powershell
python -m pip install -r omr/requirements.txt
python omr/scripts/generate_synthetic.py
python -m omr.process --image omr/dataset/regression/synthetic-card.png --config omr/config/model-v1.pre-homologation.json
python -m pytest omr/tests
```

O modelo permanece `pre-homologacao`. As metas de qualidade somente podem ser
aprovadas depois do dataset real e do protocolo descrito em
`docs/omr/dataset-e-metricas.md`.
