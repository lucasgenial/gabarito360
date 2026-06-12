# Registro de Decisoes do MVP

Este diretorio registra as decisoes arquiteturais e de produto que bloqueavam o inicio seguro das migrations e funcionalidades do Gabarito360.

As decisoes abaixo foram registradas para o MVP em **10 de junho de 2026**. O status vigente de cada uma deve ser respeitado. Mudancas posteriores exigem novo ADR, avaliacao de impacto e atualizacao da documentacao afetada.

| ID | Decisao | Responsaveis | Status | Prazo de revalidacao |
|---|---|---|---|---|
| D001 | [Modelo fisico inicial do cartao](ADR-D001-modelo-fisico-cartao.md) | Produto + OMR | Aceita | Antes da homologacao OMR da FASE 7 |
| D002 | [Unicidade da matricula](ADR-D002-unicidade-matricula.md) | Produto + Backend | Aceita | Antes da primeira migration de alunos |
| D003 | [Formato unico para o codigo do cartao](ADR-D003-codigo-cartao.md) | Produto + OMR + Backend | Substituida por D010 | Nao aplicavel |
| D004 | [Politica de questao anulada](ADR-D004-questao-anulada.md) | Produto + Pedagogico | Aceita | Antes da implementacao de gabaritos |
| D005 | [Motivo de correcao manual](ADR-D005-motivo-correcao-manual.md) | Produto + Auditoria | Aceita | Antes da implementacao da confirmacao |
| D006 | [Retencao de imagens e logs](ADR-D006-retencao-imagens-logs.md) | Seguranca + Produto | Aceita | Antes da homologacao e a cada 12 meses |
| D007 | [Homologacao de dispositivos Android](ADR-D007-dispositivos-android.md) | Produto + Mobile + QA | Aceita | Antes do gate da FASE 8 |
| D008 | [Metas de qualidade OMR](ADR-D008-metas-qualidade-omr.md) | Produto + OMR + QA | Aceita | Antes do piloto e apos mudanca de modelo |
| D009 | [Abordagem do painel web](ADR-D009-painel-web.md) | Arquitetura + Produto | Aceita | Apos o piloto ou antes de ampliar o painel |
| D010 | [Identificacao de cartoes impressos e codigos gerados](ADR-D010-identificacao-cartao.md) | Produto + OMR + Mobile + Backend | Aceita | Antes das migrations de cartoes e da homologacao OMR |
| D011 | [Corte de escopo e matriz de permissoes do MVP](ADR-D011-escopo-e-permissoes-mvp.md) | Produto + Arquitetura + Seguranca | Aceita, parcialmente substituida por D013 | Antes de alterar o gate do MVP ou iniciar nova release |
| D012 | [Reorientacao pelo mockup funcional e adocao do MariaDB](ADR-D012-reorientacao-mockup-mariadb.md) | Produto + Arquitetura + Design + Backend | Aceita | Antes de iniciar R2 |
| D013 | [Contrato de produto web da R1](ADR-D013-contrato-produto-web-r1.md) | Produto + Arquitetura + Design + Seguranca | Aceita | Antes de ampliar o MVP ou iniciar integracoes |

## Regras de governanca

- Um ADR aceito nao deve ser editado para esconder uma mudanca de decisao; uma mudanca relevante gera novo ADR que substitui o anterior.
- Limiares numericos de classificacao OMR nao fazem parte destas decisoes. Eles devem ser calibrados com dataset real, versionados no modelo de cartao e homologados por metricas.
- A implementacao deve parar quando uma decisao nao puder ser aplicada sem contradizer requisitos, regras ou integridade de dados.
