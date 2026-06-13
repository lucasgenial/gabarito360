# Relatorio de Homologacao R7

## 1. Escopo

Data de referencia: 13 de junho de 2026.

Este relatorio separa homologacao tecnica automatizada de homologacao fisica e
organizacional. A entrega R7 prepara deploy e merge, mas nao autoriza piloto.

## 2. Gates

| Gate | Estado inicial R7 | Evidencia |
|---|---|---|
| Contrato Compose valido | aprovado localmente | `docker compose config --quiet` |
| MariaDB e Redis sem porta publica | aprovado por contrato | somente `nginx` possui `ports` |
| Imagens de aplicacao e Nginx | aprovado na CI | job `Containers and restore verification` |
| Ambiente sobe e health responde | aprovado na CI | API health e login via Nginx |
| Backup e restauracao isolada | aprovado na CI | `scripts/infra/verify-restore.ps1` |
| Backend completo | aprovado localmente | 184 testes e 1457 assercoes |
| Composer, Pint, Vite e OpenAPI | aprovado localmente | manifestos, estilo, build e contrato validos |
| Auditoria Composer | aprovado localmente | nenhuma vulnerabilidade reportada |
| Auditoria npm critica | aprovado com risco residual | nenhum achado critico; 3 achados altos de build em Vite/esbuild |
| Flutter analyze/test/build | aprovado localmente | analyze sem erros, widget test e APK debug |
| OMR sintetico | aprovado localmente | contrato retorna `partial` e exige revisao |
| CI da branch | aprovado | [run 27465368521](https://github.com/lucasgenial/gabarito360/actions/runs/27465368521) |
| Acuracia OMR com cartoes reais | **bloqueante** | dataset real ainda nao aprovado |
| Desempenho em dispositivos reais | **bloqueante** | matriz de dispositivos ausente |
| Avaliacao organizacional LGPD | **bloqueante** | aceite operacional pendente |
| Piloto | **nao autorizado** | depende dos tres gates bloqueantes |

O Docker Desktop instalado na estacao local nao conseguiu iniciar em 13 de
junho de 2026. Build, subida, health containerizado e restauracao foram
executados e aprovados no job isolado da CI.

## 3. Criterios para liberar a PR

- regressao tecnica e CI aprovadas;
- backup/restauracao executados com evidencia;
- revisao de Infraestrutura, Backend, Mobile/OMR e Seguranca;
- documentacao operacional conferida;
- nenhuma credencial ou dado real na branch;
- plano de rollback aprovado.
- risco alto de dependencia de build avaliado ou corrigido antes de producao.

## 4. Criterios adicionais para piloto

- dataset real rotulado e metas OMR aprovadas;
- cartao impresso e variacoes de captura homologados;
- dispositivos Android e p95 reais aprovados;
- treinamento, suporte e responsaveis definidos;
- avaliacao LGPD organizacional concluida.
