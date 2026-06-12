# Contrato de Entrega R5 - Fatias Funcionais Web

## 1. Resultado

A R5 conecta a fundacao visual da R4 aos contratos persistentes e policies da
R3. As telas canonicas usam consultas reais, respeitam o escopo do usuario e
mantem as rotas administrativas anteriores para compatibilidade.

## 2. Fatias entregues

| Fatia | Rotas principais | Resultado |
|---|---|---|
| Conta | `/perfil`, `/configuracoes` | perfil proprio, preferencias, senha e dispositivos |
| Painel | `/painel` | indicadores e atividade derivados do banco |
| Organizacao | `/escolas`, `/escolas/{escola}`, `/escolas/{escola}/equipe` | escolas, detalhe e equipe autorizada |
| Academico | `/turmas`, `/turmas/{turma}`, `/alunos/{aluno}` | turmas, alunos, matricula e importacao CSV validada |
| Avaliacoes | `/provas`, `/provas/nova`, `/provas/{prova}`, `/provas/{prova}/gabarito` | rascunho inicial, consulta de prova, modelo e gabarito |
| Operacao | `/correcoes`, `/aplicacoes/{aplicacao}/correcao` | snapshot persistido de progresso e pendencias |
| Resultados | `/resultados/{resultado}`, rotas de relatorio | consulta autorizada de resultados e agregados |

## 3. Autorizacao

- `PortalScope` concentra consultas canonicas para escolas, provas, aplicacoes
  e resultados.
- Os scopes e policies existentes continuam sendo reutilizados.
- Resultados nao sao exibidos para perfis sem permissao de consulta.
- Acesso fora do escopo retorna `404` quando a existencia do recurso nao deve
  ser revelada.

## 4. Dados de demonstracao

`LocalDemoSeeder` cria dados ficticios persistidos para visualizacao local:

- uma escola, turma e alunos;
- uma prova publicada com gabarito;
- uma aplicacao finalizada;
- leituras e resultados demonstrativos.

Os indicadores das telas sao calculados a partir desses registros. Nenhuma
metrica fica hardcoded nos templates.

## 5. Limites mantidos para R6

A R5 nao implementa criacao e execucao operacional de aplicacoes, captura OMR,
revisao de leituras, motor de correcao, Reverb, geracao de CSV/PDF ou Flutter.
As telas operacionais consultam dados reais existentes, mas mutacoes e
processamento pertencem a R6.

## 6. Verificacao

```powershell
cd backend
php artisan test tests/Feature/Web
php artisan test
npm.cmd run build
php artisan db:seed --class=LocalDemoSeeder --no-interaction
```

Credenciais locais:

```text
URL: http://127.0.0.1:8000/login
E-mail: admin@gabarito360.local
Senha: Gabarito360@Local
```

## 7. Gate

A R5 esta concluida quando a suite completa permanece verde, o build termina,
as rotas canonicas usam dados persistidos e os testes demonstram isolamento
horizontal entre escolas.
