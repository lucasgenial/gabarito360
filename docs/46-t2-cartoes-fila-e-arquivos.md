# 46 — T2-D3: cartões, fila e arquivos

Base: PR #77 incorporado em `24e335ae3927fb6291f7346d18fc219c65d1654e`. Continuidade T2-D3 autorizada pelo responsável, **sem deploy**. Implementação derivada de [T1 aprovado](40-clientes-espacos-e-vinculos.md) e do [roteiro D/E](44-t2-adaptacao-e-corte.md).

## Entrega em revisão — PR #78

- Gabaritos, questões, cartões, recibos de envio, respostas, notas e decisões manuais recebem espaço e autoria do contexto autorizado no servidor. Payloads não escolhem proprietários. Leituras conferem os vínculos entre prova, escola, turma, aluno e cartão.
- Fila transporta IDs de espaço e ator. Revalida conta ativa, permissão, prova, aluno e hierarquia antes da leitura e antes da gravação. QR só redireciona dentro da escola e espaço autorizados. Retentativas e callbacks de falha conferem recibo, cartão, tentativa, ator e espaço.
- Recusa no modo novo preserva cartão e foto para conciliação, sem transferência ou exclusão automática. Recibos recusados não são retentáveis; uma nova foto usa novo UUID. Exclusão ordinária autorizada e substituição de foto continuam sendo operações da correção.
- Idempotência conserva o índice legado `(usuario_id, chave)` nesta etapa: UUID reutilizado em outro espaço retorna 409 genérico. E deve migrar a unicidade para contexto/ator/chave antes da ativação. Locks incluem espaço; a memória de envios da câmera inclui sessão, espaço e prova, sem fotos/notas no navegador.
- Revisão manual e vínculo de aluno são transacionais. Divergências de espaço nos filhos impedem cálculo de nota e revertem a revisão; não geram nota parcial nem corrigem propriedade silenciosamente.
- URLs de imagens no modo novo incluem ator, espaço e versão do acesso assinados. Cada abertura revalida permissão, escopos e hierarquia ativos; usa cache privado sem armazenamento. Revogação invalida links anteriores. Como links temporários assinados, continuam sendo credenciais portadoras durante sua vigência; não são sessão web. Não alteram os 120 minutos de inatividade aprovados.
- Fotos novas e derivados usam diretório por espaço. Derivados legados continuam por mapeamento explícito do ID global do cartão previamente autorizado. Não houve movimentação nem limpeza de arquivos existentes.
- PDF de cartões limita alunos às turmas autorizadas da prova; resultado individual confere nota/cartão/aluno/prova/espaço. Temporários incluem espaço no prefixo. Não há mudança de layout ou do motor OMR.
- Comando `g360:cartoes-imagens` exige `--espaco` e simula por padrão no modo novo; `--aplicar` gera derivados do espaço informado. Mantém comportamento legado enquanto o adaptador estiver desligado. Nenhuma execução em produção foi feita.

## Corte e limites

`config/espacos.php` permanece com `ativo = false`, sem chave de ambiente. Não ativar D3 isoladamente. Não há migration nova nesta entrega.

Antes do corte, terminar D4 e E, conciliar dados, parar escritores e drenar jobs antigos sem contexto. Reiniciar workers com a versão do corte; não reutilizar jobs anteriores como se tivessem contexto validado. URLs antigas sem ator/espaço deixam de funcionar ao ativar e devem ser renovadas pela tela.

D4 ainda cobre relatórios coletivos, painéis, importações e propagação da seleção institucional. E ainda cobre índices, FKs compostas, obrigatoriedade e validação final do legado. V1/V2 implementam vínculos reais e contexto por aba; o adaptador atual não substitui essas etapas.

Merge desta entrega exige aprovação própria. Deploy, migração operacional, preenchimento real e ativação exigem autorização posterior. Nenhuma alteração de produção, retenção, contrato, cobrança ou validade de tokens é parte desta entrega.

## Validação

Testes fictícios com duas redes cobrem autoria, repetição, acesso misto do administrador, revogação antes/durante OMR, QR externo, contexto inválido de job, links assinados, rollback manual, gabaritos divergentes, recibos cruzados, professor limitado à turma e simulação do comando. Ensaio isolado em MariaDB e regressões da API/web no CI; resultados finais ficam registrados no PR.
