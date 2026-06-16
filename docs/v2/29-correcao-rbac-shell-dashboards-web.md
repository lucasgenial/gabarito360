# Correcao V2: shell, dashboards e permissao por perfil

## Problema

As telas web autenticadas estavam expondo a mesma navegacao e o mesmo dashboard
para perfis diferentes. Isso contrariava o contrato V2:

- `/painel` deve resolver o painel conforme o ator autenticado;
- a navegacao do shell deve exibir apenas itens permitidos;
- a identidade do menu deve mostrar papel e escopo, nao apenas e-mail;
- perfis operacionais, como aplicador, nao devem ver a mesma superficie de um
  administrador.

## Referencias consultadas

- `docs/v2/telas/_shell.md`
- `docs/v2/telas/dashboards.md`
- `docs/v2/telas/escolas.md`
- `docs/v2/telas/provas.md`
- `docs/v2/telas/turmas.md`
- `docs/v2/telas/correcao.md`
- `style-system/dashboard-admin.html`
- `style-system/dashboard-professor.html`
- `style-system/dashboard.html`
- `style-system/escolas.html`

## Decisao

Criar um contexto web V2 derivado do perfil principal e das permissoes reais do
usuario. Esse contexto passa a alimentar:

- badge de contexto;
- papel exibido no menu;
- itens de navegacao do shell;
- variante do dashboard em `/painel`;
- acoes rapidas permitidas.

## Mapeamento inicial

| Perfil | Dashboard | Navegacao inicial |
|---|---|---|
| `administrador_geral` | administrativo/rede | Painel, Provas, Turmas, Escolas, Correcoes |
| `gestor_nucleo` | diretor de nucleo | Painel, Provas, Turmas, Escolas, Correcoes |
| `responsavel_escola` | diretor escolar | Painel, Turmas, Provas, Correcoes |
| `professor` | professor | Painel, Provas, Turmas, Correcoes |
| `aplicador` | operacional/aplicador | Painel, Turmas, Correcoes |
| `consulta` | consulta | Painel, Turmas, Resultados quando houver |
| `suporte_tecnico` | diagnostico | Painel, Configuracoes quando aplicavel |

## Escopo desta correcao

1. Corrigir shell e dashboard para diferenciar admin e aplicador.
2. Remover links visiveis sem permissao no shell.
3. Garantir que a tela de escolas nao apareca para aplicador.
4. Preparar base para migrar Provas, Turmas e Correcoes de `layouts.admin` para
   o shell V2 em passos pequenos.

## Criterios de aceite

- Admin local ve dashboard administrativo, com acoes de rede.
- Aplicador local ve dashboard operacional, sem acesso visual a escolas ou
  usuarios.
- Navegacao do aplicador exibe apenas as superficies autorizadas.
- Testes web cobrem a diferenca entre admin e aplicador.
