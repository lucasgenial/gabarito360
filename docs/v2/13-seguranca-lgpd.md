# Segurança, Privacidade e LGPD V2

## Controles obrigatórios

- Autenticação, autorização por permissão e escopo em toda operação.
- MFA para funções privilegiadas e gestão de sessões/dispositivos.
- Limitação de tentativas, recuperação segura de senha e trilha de acesso.
- Criptografia em trânsito e proteção de segredos/credenciais.
- Storage privado, URLs temporárias e retenção por categoria de arquivo.
- Auditoria de alterações administrativas, leituras, revisões, exportações e LGPD.

## Fluxos visíveis no mockup

| Ação da interface | Implementação segura |
|---|---|
| Criar conta | onboarding validado, convite ou aprovação institucional |
| Remover membro | suspensão/inativação e revogação de sessões |
| Excluir aluno/dados | solicitação LGPD e anonimização/descarte controlado |
| Exportar todos os dados | job autorizado, arquivo temporário e auditoria |
| Integrações | credenciais criptografadas e permissões mínimas |
| Histórico de acesso | sessões, dispositivos, IP reduzido e eventos relevantes |

## Dados de alunos

Aplicar minimização, base legal definida, acesso por necessidade, mascaramento,
retenção e descarte. Fotos, CPF, responsáveis, resultados e cartões exigem
proteção proporcional e não devem aparecer em logs técnicos.

## Gate

Nenhuma tela sensível é concluída sem testes de acesso permitido/negado,
auditoria, política de retenção e revisão de exposição de dados.
