# UR4-R1 — recuperar o acesso expirado e reduzir o custo da navegação

Correção prioritária identificada na validação real da homologação após PR197. Produção permanece fora da autorização.

O GET inicial mostrava a estrutura do painel mesmo quando a identidade continuava válida e o cache do vínculo já havia expirado. A consulta seguinte recebia409, mas o cliente mostrava um erro genérico sem conduzir à seleção. A expiração conjunta da referência de recurso e do vínculo também podia sugerir indevidamente a lista de registros. A hidratação com os cabeçalhos reais precisava tratar a ausência da identidade como401 JSON, além de manter o CSRF419.

A correção verifica o contexto antes de emitir a estrutura ou resolver a referência, preservando a ordem CSRF→revisão→contexto→alvo→autorização. Leituras sem acesso seguem para a seleção explícita de vínculo;401/419 conduzem ao login fixo sem reenviar operações. Revisão antiga mantém409 e oferece somente a seleção de vínculo. Nenhum URL arbitrário devolvido por JSON é seguido e nenhum perfil é escolhido automaticamente.

A tela de seleção descarta a transição abandonada e o estado da entrada atual do histórico. A troca confirmada pelo modal faz o mesmo somente na aba que a solicitou. Não substitui a revisão de formulários antigos, não limpa as outras entradas nem renova referências de outros acessos.

O processamento das URLs também repetia a compilação do catálogo para cada link. A compilação passa a ocorrer uma vez por Request, guardando apenas metadados e regex. O cache não contém identificadores de recurso, consultas, referências, identidade ou permissões e não atravessa requisições.

| Parte interna | Verificação exigida |
|---|---|
| Recuperação | Vínculo expirado isoladamente ou junto da referência; identidade ausente; cabeçalhos reais de hidratação; saída para seleção/login. |
| Fronteiras | CSRF419, revisão antiga409, nenhuma consulta/alteração indevida, nenhum replay ou renovação de referência antiga. |
| Desempenho | Comparação isolada com rotas em cache; emissão das119 operações e constraints preservadas; medição no servidor. |
| Interface | Parecer prévio e final UI/UX, recuperação desktop/celular e bloqueio de aba antiga. JavaScript externo. |
| Entrega | CI, revisão independente, merge, backup, deploy só na homologação, dados e arquivos idênticos e observação operacional. |

O PR197/336793e permanece como baseline da homologação para esta correção. Não fechar UR4 na planilha antes de validar esta recuperação real. O contrato e a fonte da API permanecem inalterados.
