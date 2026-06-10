# ADR-D007 - Homologacao de dispositivos Android

- **Status:** aceita para o MVP
- **Data:** 2026-06-10
- **Responsaveis:** Produto + Mobile + QA
- **Prazo:** resolvida; executar a homologacao antes do gate da FASE 8

## Contexto

A qualidade do OMR depende da camera e do desempenho do dispositivo. Fixar marcas ou modelos sem acesso ao inventario real tornaria a decisao fragil.

## Decisao

A homologacao sera baseada em capacidade e teste, nao em marca. Um dispositivo pode operar no piloto somente se:

- executar Android 11 ou superior com atualizacoes de seguranca suportadas;
- possuir camera traseira de pelo menos 8 MP, foco automatico e flash;
- possuir arquitetura 64 bits, pelo menos 4 GB de RAM e 2 GB livres;
- concluir o fluxo de captura e processamento dentro das metas da D008;
- passar a suite de qualidade com cartoes reais em iluminacao normal e desfavoravel;
- nao apresentar falhas criticas de camera, permissao, memoria ou rede.

O piloto deve incluir pelo menos tres modelos fisicos representando faixas de desempenho diferentes. O resultado de cada modelo sera registrado em uma matriz de homologacao antes do gate da FASE 8.

## Justificativa

Uma matriz baseada em evidencia permite trocar aparelhos sem alterar a arquitetura e impede liberacao de hardware inadequado.

## Impactos

- O app deve registrar versao e modelo para diagnostico, sem identificadores invasivos.
- Dispositivo nao homologado deve receber aviso e pode ser bloqueado por configuracao.
- Cada nova versao relevante do app ou OMR exige regressao nos aparelhos homologados.
