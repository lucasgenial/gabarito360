# L6 — Avatares de alunos

Implementação em revisão em 31/10/2026; sem merge, deploy ou exclusão de fotos.

## Comportamento

Cadastro, lista, detalhe, turma e painéis de aluno, professor e coordenador usam o mesmo componente de iniciais. A cor deriva do ID do aluno e permanece estável após alteração de nome; no cadastro, antes de existir ID, a prévia é azul. Iniciais aceitam acentos e espaços repetidos, com escape HTML e alternativa para nome vazio. As cores usam os tokens dos temas claro e escuro.

API e web rejeitam novos uploads e caminhos de foto no cadastro/edição. O modelo não permite atribuição em massa de `foto_path` e omite o campo nas respostas. A coluna e os arquivos antigos são preservados. Não há migração destrutiva. Fotos de cartões-resposta não mudam.

## Inventário somente de leitura

Consulta em produção em 31/10/2026, com saída agregada, sem abrir imagens ou divulgar registros:

- Alunos com `foto_path` não vazio: **0**.
- Caminhos distintos: **0**.
- Arquivos no diretório `alunos` do disco público: **0**.
- Referências encontradas nesse diretório: **0**.

O levantamento se limita à coluna e ao diretório utilizados pela aplicação; não representa varredura de backups ou de outros diretórios. Remover a coluna e qualquer arquivo continua dependendo de aprovação específica, backup e nova conferência no momento da execução.

## Validação

- API: 104 testes, 560 asserções; regressão L6 com upload multipart também aprovada (3 testes, 30 asserções).
- Web: 29 testes, 141 asserções. Rejeição antes de chamar a API, formulário sem upload, iniciais, cor estável e escape HTML.
- MariaDB `gabarito360_teste`, cópia padrão do esquema: cadastro, edição, quatro rejeições de foto/caminho, ocultação do caminho e preservação do legado. Dados fictícios em transação revertida; sem migração. Credencial temporária revogada e túnel encerrado.
- Verificação estática de telas: 24 scripts, zero erros.
- Chrome: cadastro, lista, detalhe, turma e três painéis em 320, 375, 768, 1440, 1920 e 3840 px; sem rolagem horizontal nem erros JavaScript. Temas claro/escuro, prévia digitada e modal de edição conferidos.
- A auditoria visual usou renderização Blade e respostas fictícias locais; o roteiro remoto com dados reais não foi executado. Os prints não representam uma publicação em produção.

## Prints com dados fictícios

![Cadastro desktop](../evidencias/l6-2026-10-31/create-1440.png)
![Lista no celular](../evidencias/l6-2026-10-31/index-375.png)
![Detalhe no tema escuro](../evidencias/l6-2026-10-31/show-dark.png)
![Lista em 4K](../evidencias/l6-2026-10-31/index-3840.png)
