# 14 — Plano de Execução

Este documento detalha como cada MP deve ser executado, na ordem correta e com critérios claros.

---

## Como Usar Este Documento

1. Identifique o MP a ser executado
2. Verifique se todas as dependências estão com status **Concluído**
3. Leia o objetivo, critérios de aceite e testes
4. Execute
5. Marque como **Concluído** quando todos os critérios forem atendidos

---

## Sequência Recomendada de Execução

```
MP-002 (Docs) ─┐
MP-001 (Repo) ─┤
               ├─→ MP-003 (DB) ─→ MP-004 (API Base) ─→ MP-005 (WEB Base)
                                                │
                                 ┌──────────────┤
                                 ▼              ▼
                              MP-014          MP-012    MP-013
                              (Núcleos)       (Auth)    (Perfis)
                                 │
                              MP-015 (Escolas)
                                 │
                              MP-016 (Turmas)
                                 │
                         ┌───────┴────────┐
                         ▼                ▼
                      MP-017           MP-018
                      (Alunos)         (Professores)
                                          │
                                       MP-019 (Provas)
                                          │
                                       MP-020 (Gabaritos)
                                          │
                                       MP-021 (OMR)
                                          │
                                       MP-022 (Relatórios)

MP-005 (WEB) ─→ MP-006 ─→ MP-007
                        ─→ MP-008
                        ─→ MP-009
                        ─→ MP-010
                        ─→ MP-011

MP-004 + MP-021 ─→ MP-023 (Android)
```

---

## MP-001 — Estrutura do Repositório

**Duração estimada:** 2 horas

**Passos:**

1. Criar projeto Laravel na pasta `apps/api`:
   ```bash
   composer create-project laravel/laravel apps/api
   ```

2. Criar projeto Laravel na pasta `apps/web`:
   ```bash
   composer create-project laravel/laravel apps/web
   ```

3. Criar projeto React Native na pasta `apps/android`:
   ```bash
   npx react-native init Gabarito360Android --directory apps/android
   ```

4. Criar projeto React Native na pasta `apps/ios`:
   ```bash
   npx react-native init Gabarito360iOS --directory apps/ios
   ```

5. Configurar `.gitignore` na raiz para ignorar:
   - `apps/*/vendor/`
   - `apps/*/node_modules/`
   - `apps/*/.env`
   - `apps/*/storage/logs/*.log`

6. Criar `README.md` na raiz com instruções de setup

**Critérios de Aceite:**
- [ ] `apps/api/` existe com Laravel funcional
- [ ] `apps/web/` existe com Laravel funcional
- [ ] `apps/android/` existe com React Native configurado
- [ ] `apps/ios/` existe com React Native configurado
- [ ] `.gitignore` adequado
- [ ] `git status` não mostra arquivos sensíveis

---

## MP-003 — Banco de Dados

**Duração estimada:** 4 horas

**Configuração:**
```env
# apps/api/.env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gabarito360
DB_USERNAME=gabarito360
DB_PASSWORD=secret
```

**Ordem das migrations:**
1. `2026_01_01_000001_create_redes_table`
2. `2026_01_01_000002_create_nucleos_table`
3. `2026_01_01_000003_create_escolas_table`
4. `2026_01_01_000004_create_turmas_table`
5. `2026_01_01_000005_create_alunos_table`
6. `2026_01_01_000006_create_usuarios_table`
7. `2026_01_01_000007_create_usuario_escopos_table`
8. `2026_01_01_000008_create_provas_table`
9. `2026_01_01_000009_create_prova_turmas_table`
10. `2026_01_01_000010_create_gabaritos_table`
11. `2026_01_01_000011_create_gabarito_questoes_table`
12. `2026_01_01_000012_create_cartoes_table`
13. `2026_01_01_000013_create_cartao_respostas_table`
14. `2026_01_01_000014_create_notas_table`
15. `2026_01_01_000015_create_ambiguidade_logs_table`
16. `2026_01_01_000016_create_visitas_table`
17. `2026_01_01_000017_create_sincronizacoes_seges_table`

**Seeders para desenvolvimento:**
- `RedeSeeder` — 1 rede municipal
- `NucleoSeeder` — 3 núcleos
- `EscolaSeeder` — 6 escolas (5 ativas, 1 inativa)
- `TurmaSeeder` — 12 turmas (6º ao 9º ano)
- `AlunoSeeder` — 30 alunos por turma
- `UsuarioSeeder` — 1 usuário por perfil + senha `password`

**Critérios de Aceite:**
- [ ] `php artisan migrate:fresh` executa sem erros
- [ ] `php artisan db:seed` popula os dados
- [ ] Banco consultável via MySQL client
- [ ] Todas as FKs com ON DELETE configurado corretamente

---

## MP-004 — API Base

**Duração estimada:** 6 horas

**Instalação:**
```bash
cd apps/api
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

**Estrutura de resposta padrão:**
```php
// app/Http/Responses/ApiResponse.php
class ApiResponse
{
    public static function success($data, $meta = null, $code = 200)
    public static function error($message, $errors = [], $code = 422)
    public static function unauthorized($message = 'Não autenticado.')
    public static function forbidden($message = 'Acesso negado.')
    public static function notFound($message = 'Recurso não encontrado.')
}
```

**Testes obrigatórios:**
```bash
# Todos os testes devem passar
php artisan test --filter=Auth
```
- Teste: login com credenciais válidas retorna token
- Teste: login com credenciais inválidas retorna 401
- Teste: logout invalida o token
- Teste: rota protegida sem token retorna 401
- Teste: rota com perfil errado retorna 403

---

## MP-005 — WEB Base

**Duração estimada:** 8 horas

**Entregáveis técnicos:**
- `resources/views/layouts/app.blade.php` — layout principal
- `resources/views/layouts/auth.blade.php` — layout de autenticação
- `resources/css/gov.css` — tokens copiados de style-system/
- `resources/js/app.js` — interações copiadas de style-system/
- `app/Services/ApiClient.php` — cliente HTTP para a API

**Layout app.blade.php deve incluir:**
- GovBar (faixa gov.br)
- Header com logo, nav, badge contexto, toggle tema, menu usuário
- Slot para breadcrumb
- Slot para conteúdo principal

**Testes manuais:**
- [ ] Acessar `/login` exibe tela idêntica ao mockup
- [ ] Login com e-mail e senha válidos redireciona para /painel
- [ ] /painel redireciona para dashboard do perfil correto
- [ ] Toggle de tema claro/escuro persiste na sessão
- [ ] Logout redireciona para /login

---

## MP-015 — Escolas

**Duração estimada:** 8 horas

**Testes de integração obrigatórios:**

*API:*
- GET /api/v1/escolas retorna lista paginada (admin)
- GET /api/v1/escolas retorna 403 para professor
- POST /api/v1/escolas cria escola com dados válidos
- POST /api/v1/escolas retorna 422 sem nome
- POST /api/v1/escolas/{id}/desativar muda ativo para false

*WEB:*
- [ ] Grid de escolas renderiza todos os cards
- [ ] KPIs exibem totais corretos
- [ ] Modal de nova escola abre e fecha corretamente
- [ ] Busca em tempo real filtra os cards
- [ ] Escola inativa exibe visual diferenciado e botão "Reativar"
- [ ] Toast "Escola salva com sucesso!" aparece após salvar

---

## MP-020 — Gabaritos

**Duração estimada:** 10 horas

**Editor de bolhas:**
- Implementar JavaScript para toggle de bolhas
- Contador X/N deve atualizar em tempo real
- Barra de progresso animada
- Painel "Padrões desta prova" expansível

**Testes obrigatórios:**

*API:*
- PUT /api/v1/provas/{id}/gabarito atualiza gabarito (pré-publicação)
- POST /api/v1/provas/{id}/publicar com gabarito completo → status "publicada"
- POST /api/v1/provas/{id}/publicar com gabarito incompleto → 422
- GET /api/v1/provas/{id}/gabarito retorna gabarito publicado
- PUT /api/v1/provas/{id}/gabarito após publicação → 403

*WEB:*
- [ ] Editor de bolhas idêntico ao mockup criar-prova.html
- [ ] Contador e barra atualizam ao clicar nas bolhas
- [ ] Botão "Publicar" desabilitado com gabarito incompleto
- [ ] Gabarito.html exibe gabarito somente-leitura
- [ ] Exportar PDF abre diálogo de impressão

---

## MP-021 — OMR

**Duração estimada:** 16 horas (mais complexo do MVP)

**Sub-tarefas:**
1. Definir estratégia do motor OMR
2. Implementar OmrDriverInterface
3. Implementar upload de imagem (API)
4. Implementar Job de processamento
5. Implementar lógica de confiança e ambiguidade
6. Implementar resolução manual de ambíguos
7. Implementar polling no frontend
8. Implementar cálculo de nota após leitura completa

**Testes obrigatórios:**
- POST /api/v1/provas/{id}/cartoes com imagem válida retorna 202 (processando)
- GET /api/v1/provas/{id}/cartoes/status retorna contadores corretos
- POST /api/v1/cartoes/{id}/resolver-ambiguidade atualiza cartão e nota
- Após todos os cartões lidos, status da prova muda para "corrigida"
- [ ] Tela de acompanhamento atualiza via polling
- [ ] Lista de ambíguos atualiza ao resolver

---

## MP-022 — Relatórios

**Duração estimada:** 8 horas

**Testes obrigatórios:**

*resultado.html:*
- [ ] Gráfico donut exibe percentual correto
- [ ] Nota final formatada com vírgula (ex.: 8,0)
- [ ] Badge Aprovado/Recuperação correto
- [ ] Folha de respostas: verde para acertos, vermelho para erros, amarelo para branco
- [ ] Breadcrumb dinâmico funciona para todas as origens

*relatorio-prova.html:*
- [ ] KPIs com valores corretos
- [ ] Tabela com todos os alunos da prova
- [ ] Link "Ver prova" para cada aluno

---

## Política de Definição de "Concluído"

Um MP está **Concluído** quando:
1. Todos os critérios de aceite marcados com [x]
2. Todos os testes unitários passando
3. Todos os testes de integração passando
4. View visualmente fiel ao mockup correspondente
5. Commit criado seguindo a convenção
6. Nenhuma regressão nos MPs anteriores
