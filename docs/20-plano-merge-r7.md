# Plano de Merge R7

## 1. Estrategia

- Origem: `refatoracao-mockup-mariadb`.
- Destino: `main`.
- Forma: draft PR, revisao tematica e merge somente apos todos os gates da PR.
- Piloto: permanece bloqueado mesmo apos eventual merge tecnico.

## 2. Revisoes obrigatorias

| Area | Foco |
|---|---|
| Produto/Web | aderencia ao mockup, rotas e fluxos R5 |
| Backend/Dados | migrations MariaDB, autorizacao, auditoria e regressao |
| Mobile/OMR | contrato Flutter, falha segura e bloqueios fisicos |
| Infraestrutura | imagens, redes, segredos, backup/restauracao e rollback |
| Seguranca/LGPD | dados pessoais, storage, logs, acessos e retencao |

## 3. Checklist pre-merge

- CI completa verde na revisao atual.
- PR sem segredos, `.env`, backups, imagens reais ou artefatos locais.
- Compose sobe e health responde.
- Restauracao isolada validada.
- Revisoes obrigatorias registradas.
- Documentacao corresponde ao comportamento.
- Plano de rollback conferido.
- Relatorio de homologacao atualizado.

## 4. Procedimento

1. Congelar novas alteracoes funcionais na branch.
2. Atualizar a branch com `main` e executar regressao novamente.
3. Resolver comentarios sem misturar escopo novo.
4. Obter aprovacoes obrigatorias.
5. Fazer merge pela PR conforme politica do repositorio.
6. Validar ambiente de homologacao depois do merge.

## 5. Rollback

- Reapontar imagens para a tag anterior.
- Nao reverter migration destrutiva sem procedimento aprovado.
- Restaurar banco/storage apenas com evidencia, janela e responsavel.
- Preservar logs e `request_id` do incidente.

## 6. Bloqueios atuais

CI, subida containerizada e restauracao foram aprovadas na draft PR #1. O merge
continua bloqueado ate as revisoes humanas obrigatorias e a decisao sobre o
risco residual da dependencia de build. O piloto possui bloqueios adicionais
descritos em `docs/piloto/relatorio-homologacao-r7.md`.
