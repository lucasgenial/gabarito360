# 61 — V2: contexto autenticado e seleção

Origem: T1 (docs/40), V2 do plano mestre e autorização de 01/11/2026 após o PR #92. Sem deploy.

## Sequência

- V2-A: API lista vínculos válidos da própria identidade e emite token independente para um vínculo. Cada requisição reconstrói o contexto pelo token persistido, nunca pelo ID enviado em header/query. Esta entrega.
- V2-B: web associa identificador opaco por aba a token guardado no servidor; seleção automática se único vínculo, escolha se múltiplos, estado sem acesso e troca no topo. Cobrir navegação, forms, AJAX, downloads, expiração por inatividade e duas abas.
- V2-C: interface de gestão e aceite dos convites; integração e ensaio completo antes do corte.

## Contrato V2-A

`GET /api/v1/auth/vinculos` lista somente vínculos válidos da conta autenticada. `POST /api/v1/auth/vinculos/selecionar`, com `vinculo_id`, emite um novo token contextual; não altera o token de identidade nem outros contextos. Ambos exigem token persistido de identidade e respeitam troca obrigatória de senha. O modo de seleção fica disponível somente com `vinculos.obrigatorio` habilitado em testes/ensaio; padrão permanece desligado.

O token usa nome reservado `vinculo-contexto` e abilities internas `vinculo:ID` e `identidade:ID_TOKEN_ORIGEM`, gravadas exclusivamente pelo servidor. Não são permissões de negócio: os poderes são calculados pelo catálogo/vínculo a cada uso. Nenhuma migration é necessária. O middleware rejeita metadados incompletos, pai ausente/expirado, conta divergente e vínculos revogados, vencidos ou de instituição suspensa. O contexto também é validado quando o regime obrigatório está desligado, sem retornar ao perfil legado.

A saída com token contextual encerra apenas aquele token; a saída com token de identidade invalida os contextos derivados pela ausência do pai. Redefinição de senha já elimina todos os tokens. Não há validade máxima nova de oito horas. A sessão web mantém os 120 minutos de inatividade na futura integração V2-B. A seleção não reutiliza automaticamente o último vínculo da conta.

O V2-A não entrega ainda isolamento de abas na interface. Não habilitar o corte nem publicar isoladamente: V2-B/C e ensaio continuam necessários.
