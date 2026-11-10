## Funcionalidade

FN: FN-NNN
Subfase: <número e nome da subfase no planejamento.md>

<!--
Obrigatório quando o PR altera apps/ (regras R-FN-NO-PR e R-VERSAO-PRINCIPIOS-FN):
- cite a FN do catálogo (docs/funcionalidades/README.md);
- atualize o planejamento.md dela com a evidência;
- revalide o planejamento contra os princípios vigentes e suba "Conforme princípios" para a versão de docs/decisoes/VERSAO.
A CI não roda de novo quando você só edita esta descrição. Depois de corrigir a FN aqui, rode o job "governanca" de novo.
-->

## O que muda

<!-- O problema, o comportamento resultante e o escopo final. Use frases curtas (ASD-STE100 moderado). -->

## Contrato, acesso e dados

<!-- Preencha o que se aplica. Justifique em uma frase o que não se aplica. -->

- API, Web (BFF) e URLs afetadas; compatibilidade:
- Perfil, vínculo e permissão; idempotência quando houver efeito:
- Consulta, índice e paginação, ou "sem mudança de consulta":
- Cache (classe de dado, chave, invalidação), ou "sem cache":

## Validação

<!-- Relate só evidência obtida. Meta ainda não medida fica identificada como meta. Desempenho: 1.000 usuários simultâneos, sem simulação (ADR-0052). -->

- Testes e verificações executados:
- Medição de desempenho, quando aplicável:
- Interface: revisão de UI/UX independente (o implementador não aprova a própria tela):

## Publicação

- Migração, assets, jobs e configuração; risco e reversão:
- Homologação primeiro. A produção recebe a entrega só por decisão expressa do responsável.

## Conferência final

- [ ] A FN está citada, e o `planejamento.md` dela tem a evidência e a versão dos princípios atual.
- [ ] Nenhum print ou screenshot da aplicação no PR, no repositório ou em anexos (ADR-0049).
- [ ] Nenhum segredo, credencial, dado pessoal, SQL literal ou HAR bruto (ADR-0047).
- [ ] Nenhuma marca de IA na autoria, nos commits ou nesta descrição (ADR-0051).
- [ ] Nenhum arquivo de outra tarefa neste PR.
