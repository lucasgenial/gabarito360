# 55 — V1-A: estrutura e inventário de vínculos

Data: 01/11/2026. Base: `b750b94`, após merge autorizado do PR #86.
Escopo aprovado: iniciar V1 conforme [plano mestre](28-plano-mestre-proximos-modulos.md) e [T1](40-clientes-espacos-e-vinculos.md).

## Entrega

Primeira parte do desenvolvimento V1, ainda sem ativação. A migration aditiva cria:

- `usuario_vinculos`: pessoa, espaço, perfil, situação, início/fim, versão de autorização e autoria opcional; unicidade pessoa/espaço/perfil.
- `vinculo_permissoes`: concessão ou retirada por vínculo, validade e autor; FK ao catálogo, sem copiar ajustes existentes.
- `vinculo_eventos`: histórico mínimo com ator opcional, instante e motivo técnico. Não gravar texto livre sensível nesse motivo.

FKs usam RESTRICT para preservar vínculos e histórico. Cada criação pode ser retomada se a tabela anterior já existir; esse mecanismo não corrige tabelas divergentes criadas externamente. `down()` recusa remoção automática. Nenhum vínculo é criado pela migration. Os campos de vigência, compatibilidade perfil/espaço e transições de estado serão validados pelo serviço de escrita de V1-B antes de expor qualquer operação.

`Usuario::vinculos()` e `UsuarioVinculo` permitem leitura da estrutura; mass assignment do vínculo é bloqueado. `usuarios.perfil`, `usuario_escopos`, `usuario_permissoes` e seu histórico permanecem intactos. `Permissao`, `Escopo`, autenticação, sessão e jobs ainda usam o modelo atual.

## Diagnóstico somente leitura

Após aplicar as migrations **em ambiente de teste**, executar na API:

```sh
php artisan g360:inventariar-vinculos
```

O comando não oferece `--aplicar`. Retorna JSON e código 1 quando encontra pendências; código 0 significa apenas inventário sem pendências detectadas. `conversao_liberada` permanece sempre `false`. Os IDs do relatório são dados operacionais: guardar com acesso restrito, sem publicar relatórios reais em PRs. Não inclui nome, e-mail, CPF, senha, token ou nome de aluno.

Regras de diagnóstico:

- Administrador com duas redes explícitas gera dois candidatos independentes, nunca um vínculo global.
- Diretor de núcleo usa núcleo; diretor escolar/coordenador/aplicador usam escola.
- Professor agrupa escolas e turmas explícitas por espaço escolar, sem unir suas restrições entre instituições.
- Aluno depende de referência explícita ao registro; não há associação por nome, CPF ou e-mail. Referência ausente ou incoerente exige conciliação.
- Referências órfãs, tipo de escopo incompatível, espaço incoerente, hierarquia/cliente inativo, conta inativa e vínculos já existentes são sinalizados.
- Todo ajuste individual, inclusive retirada e ajuste vencido, exige decisão de destino. O relatório conserva chave, efeito, validade e ID da concessão para a conciliação; não multiplica permissões.
- Perfis fora do conjunto escolar atual exigem conciliação. Administração de plataforma e novos perfis continuam nas fases aprovadas próprias.

O inventário é diagnóstico, não manifesto executável nem prova de equivalência de acesso. Sua transação oferece leitura consistente conforme o isolamento configurado no banco; a futura conversão deverá repetir as verificações com escritores pausados e comparar o estado aprovado antes de aplicar.

## Validação e continuidade

`VinculosLegadosTest` cobre duas redes, restrições por turma/aluno, identidade omitida, pendências e ausência de escrita; também verifica unicidade e FKs. `VinculosMariaDbTest` executa o DDL em banco descartável, repete a expansão com dados e testa referências, catálogo, perfil inválido e preservação do histórico. A CI executa essa suíte junto à integridade T2. `PerfisTest` permanece sem edição.

V1 ainda não está concluído. Próximas partes:

1. **V1-B:** relações `vinculo_turmas`/`vinculo_alunos` com integridade composta, manifesto de conciliação, serviço de escrita/conversão retomável e comparação de permissões antes/depois, preservando concessões e retiradas aprovadas.
2. **V1-C:** adaptar autorização e todos os consumidores ao vínculo ativo; verificar vigência/revogação, permissões isoladas, filas e links assinados. Provar que diretor na escola A e professor na B não une poderes nem dados.
3. **V2:** escolha de vínculo na interface e contexto independente por aba/token conforme T1.

Não houve deploy, migration ou diagnóstico no servidor nesta entrega. O servidor permanece no T2 publicado, conforme [registro de ativação](54-t2-publicacao-homologacao.md). Merge e publicação deste pacote dependem de suas respectivas aprovações; publicar V1 exigirá preparação operacional e aviso ao responsável.
