# Contrato de Entrega R6 - Integracao Operacional

## 1. Resultado

A R6 conecta aplicacoes, leituras preliminares, revisao humana, confirmacao
idempotente, correcao, dashboard em tempo real e relatorio CSV. O fluxo usa
dados persistidos, policies por escopo, locks transacionais e auditoria.

O modulo OMR e o app Flutter possuem contratos executaveis, mas o piloto
permanece bloqueado ate homologacao com cartoes impressos e dispositivos reais.

## 2. Fluxo implementado

```text
prova publicada + turma vinculada
  -> aplicacao com snapshot dos alunos
  -> inicio por aplicador vinculado
  -> leitura preliminar preservando codigo impresso e codigo do sistema
  -> revisao obrigatoria quando ambigua
  -> confirmacao com Idempotency-Key
  -> cartao associado sem colisao
  -> resultado vigente calculado pelo gabarito congelado
  -> evento privado Reverb + snapshot HTTP
  -> relatorio CSV privado e auditado
  -> finalizacao sem revisoes pendentes
```

## 3. Contratos entregues

- API REST em `/api/v1/aplicacoes`, `/api/v1/leituras` e `/api/v1/relatorios`.
- Confirmacao idempotente registrada em `logs_sincronizacao`.
- Eventos `application.progress.updated` em `private-applications.{id}`.
- Painel web operacional em `/aplicacoes/{id}/correcao`.
- CSV protegido contra formula injection e armazenado em disco privado.
- Worker OMR que executa `python -m omr.process`.
- App Flutter Android com tema oficial, login mobile, aplicacoes, dashboard e
  alunos autorizados.

## 4. Gates

| Gate | Estado | Evidencia |
|---|---|---|
| Fluxo prova ate relatorio | aprovado em teste automatizado | `OperationalFlowTest` |
| Revisao manual auditada | aprovado | auditoria `leitura_cartao.reviewed` |
| Idempotencia e conflitos | aprovado em teste automatizado | `logs_sincronizacao` e locks |
| Metricas derivadas do banco | aprovado | `ApplicationMetrics` |
| Tempo real privado | implementado | Reverb, Echo e canal autorizado |
| Storage e CSV privados | aprovado em teste de fluxo | `Arquivo` + disco privado |
| LGPD tecnico | aprovado no escopo R6 | minimizacao da API, storage privado, escopo e auditoria |
| Design e acessibilidade web | aprovado na regressao automatizada | `DesignSystemTest` |
| Carga local de dashboard | smoke aprovado, nao substitui homologacao | 100 requisicoes, 0 erros, p95 local de 406,94 ms |
| Flutter analyze/widget test | aprovado | comandos da secao 6 |
| Contrato OMR sintetico | aprovado | `omr/tests/test_contract.py` |
| Acuracia OMR real | **pendente/bloqueante** | dataset real ainda nao coletado |
| p95 em dispositivos reais | **pendente/bloqueante** | matriz de dispositivos ausente |
| Avaliacao organizacional LGPD/piloto | **pendente/bloqueante** | depende de homologacao e operacao real |
| Piloto | **nao autorizado** | depende dos gates OMR e dispositivo |

## 5. Limites deliberados

- O OMR pre-homologacao sempre exige revisao e nunca confirma leitura.
- A captura por camera no Flutter nao foi habilitada sem modelo fisico
  homologado.
- PDF, operacao offline e reprocessamento em lote permanecem fora desta entrega.
- Reverb usa `log` por padrao local; definir `BROADCAST_CONNECTION=reverb` para
  executar o WebSocket.

## 6. Verificacao

```powershell
cd backend
php artisan test
php vendor/bin/pint --test
npm.cmd run build

cd ../mobile
C:\develop\flutter\bin\flutter.bat analyze
C:\develop\flutter\bin\flutter.bat test

cd ..
python -m pytest omr/tests -q
python -m omr.process --image omr/dataset/regression/synthetic-card.png --config omr/config/model-v1.pre-homologation.json
```

## 7. Execucao local do tempo real

```powershell
cd backend
# No .env local: BROADCAST_CONNECTION=reverb
php artisan reverb:start
php artisan queue:work
```

O frontend sempre pode recuperar o estado atual pelo endpoint
`GET /api/v1/aplicacoes/{id}/dashboard`.
