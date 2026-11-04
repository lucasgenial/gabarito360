<?php

namespace App\Services;

use App\Models\Usuario;
use App\Support\{ContextoVinculo, Escopo, GruposFuncionais, Permissao, ReferenciasDisciplinas};
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** DC1 não concede vínculo, turma ou acesso a prova; organiza responsabilidades locais. */
final class GestaoDisciplinas
{
    public function contexto(Usuario $u, bool $escrever = false): object
    {
        abort_unless(config('disciplinas.ativo'), 404);
        $v = ContextoVinculo::resolver($u);
        abort_unless($v->espaco->tipo === 'escola' && in_array($v->perfil, ['coordenador', 'dir_escolar', 'vice_dir_escolar', 'professor'], true)
            && Permissao::pode($u, 'turma.ver'), 403, 'Organização pedagógica indisponível neste vínculo.');
        if ($escrever) abort_unless($v->perfil === 'coordenador' && Permissao::pode($u, 'disciplina.gerir'), 403, 'A gestão de disciplinas cabe à coordenação autorizada.');
        return $v;
    }

    public function gerir(Usuario $u): bool
    {
        $v = $this->contexto($u);
        return $v->perfil === 'coordenador' && Permissao::pode($u, 'disciplina.gerir');
    }

    public function disciplinas(Usuario $u): Builder
    {
        $v = $this->contexto($u);
        $q = DB::table('disciplinas as d')->where('d.espaco_id', $v->espaco_id);
        if ($v->perfil === 'professor') $q->whereExists(fn ($i) => $i->selectRaw('1')->from('disciplina_professores as dp')
            ->whereColumn('dp.disciplina_id', 'd.id')->whereColumn('dp.espaco_id', 'd.espaco_id')->where('dp.vinculo_id', $v->id)->where('dp.ativo', true));
        return $q;
    }

    public function disciplina(Usuario $u, int $id): object
    {
        $d = $this->disciplinas($u)->where('d.id', $id)->first();
        abort_unless($d, 404, 'Disciplina não encontrada.');
        $d->areas = $this->areasDaDisciplina((int) $d->espaco_id, (int) $d->id);
        return $d;
    }

    public function areasDaDisciplina(int $espaco, int $disciplina): array
    {
        return DB::table('disciplina_areas as da')->join('areas_conhecimento as a', function ($j) {
            $j->on('a.id', '=', 'da.area_id')->on('a.espaco_id', '=', 'da.espaco_id');
        })->where('da.espaco_id', $espaco)->where('da.disciplina_id', $disciplina)
            ->orderBy('a.nome')->orderBy('a.id')->get(['a.id', 'a.nome', 'a.sigla', 'a.ativo'])->all();
    }

    public function areasDisponiveis(Usuario $u, ?int $disciplina = null): array
    {
        $v = $this->contexto($u, true);
        if ($disciplina) $this->disciplina($u, $disciplina);
        return DB::table('areas_conhecimento as a')->where('a.espaco_id', $v->espaco_id)
            ->where(function ($q) use ($disciplina, $v) {
                $q->where('a.ativo', true);
                if ($disciplina) $q->orWhereIn('a.id', DB::table('disciplina_areas')->where('espaco_id', $v->espaco_id)->where('disciplina_id', $disciplina)->select('area_id'));
            })->orderBy('a.nome')->orderBy('a.id')->get(['a.id', 'a.nome', 'a.sigla', 'a.ativo'])->all();
    }

    /** Somente vínculos Professor vigentes; nunca serializar a conta ou documento. */
    public function professores(Usuario $u): Builder
    {
        $v = $this->contexto($u, true);
        return DB::table('usuario_vinculos as v')->join('usuarios as u', 'u.id', '=', 'v.usuario_id')
            ->where('v.espaco_id', $v->espaco_id)->where('v.perfil', 'professor')->where('v.situacao', 'ativo')
            ->where('v.inicio_em', '<=', now())->where(fn ($q) => $q->whereNull('v.fim_em')->orWhere('v.fim_em', '>', now()))->where('u.ativo', true)
            ->select('v.id as vinculo_id', 'u.nome', 'v.inicio_em', 'v.fim_em')->orderBy('u.nome')->orderBy('v.id');
    }

    public function indicacoes(Usuario $u, int $disciplina): array
    {
        $d = $this->disciplina($u, $disciplina); $v = $this->contexto($u);
        $q = DB::table('disciplina_professores as dp')->join('usuario_vinculos as v', 'v.id', '=', 'dp.vinculo_id')->join('usuarios as u', 'u.id', '=', 'v.usuario_id')
            ->where('dp.disciplina_id', $d->id)->where('dp.espaco_id', $v->espaco_id);
        if ($v->perfil === 'professor') $q->where('dp.vinculo_id', $v->id);
        return $q->orderBy('u.nome')->get(['dp.id', 'dp.vinculo_id', 'dp.ativo', 'dp.versao', 'u.nome', 'v.situacao as vinculo_situacao', 'v.inicio_em', 'v.fim_em', 'v.perfil', 'u.ativo as usuario_ativo'])->map(function ($p) {
            $p->vinculo_vigente = (bool) $p->usuario_ativo && $p->perfil === 'professor' && $p->vinculo_situacao === 'ativo' && $p->inicio_em <= now() && (!$p->fim_em || $p->fim_em > now());
            $p->vinculo_rotulo = $p->vinculo_vigente ? 'Vigente' : match (true) {
                !$p->usuario_ativo => 'Conta inativa', $p->perfil !== 'professor' => 'Perfil alterado', $p->vinculo_situacao === 'convidado' => 'Aguardando aceite',
                $p->vinculo_situacao === 'suspenso' => 'Suspenso', $p->vinculo_situacao !== 'ativo' || ($p->fim_em && $p->fim_em <= now()) => 'Encerrado',
                $p->inicio_em > now() => 'Início futuro', default => 'Indisponível',
            };
            unset($p->usuario_ativo, $p->perfil); return $p;
        })->all();
    }

    public function salvar(Usuario $u, array $dados, ?int $id = null): object
    {
        return DB::transaction(function () use ($u, $dados, $id) {
            $v = $this->travarAtor($u);
            $antes = $id ? $this->travarDisciplina($v, $id) : null;
            if ($antes) $this->versao($antes, $dados['versao']);
            $nome = preg_replace('/\s+/u', ' ', trim($dados['nome']));
            $this->campo($nome !== '', 'nome', 'Informe o nome da disciplina.');
            $chave = Str::lower(Str::ascii($nome));
            $this->campo(mb_strlen($chave) <= 100, 'nome', 'Use um nome mais curto: a forma de busca dos símbolos informados excede o limite.');
            // Serializa nomes da escola, inclusive criação concorrente da mesma referência.
            DB::table('espacos')->where('id', $v->espaco_id)->lockForUpdate()->first();
            $areasAntes = $antes ? $this->areasDaDisciplina((int) $v->espaco_id, $id) : [];
            $areasIds = array_key_exists('area_ids', $dados) ? array_map('intval', $dados['area_ids']) : null;
            if ($areasIds !== null) {
                sort($areasIds);
                $areas = DB::table('areas_conhecimento')->where('espaco_id', $v->espaco_id)->whereIn('id', $areasIds)->orderBy('id')->lockForUpdate()->get();
                $this->campo($areas->count() === count($areasIds), 'area_ids', 'Escolha somente áreas disponíveis nesta escola.');
                $anteriores = array_map(fn ($a) => (int) $a->id, $areasAntes);
                $this->campo(!$areas->contains(fn ($a) => !$a->ativo && !in_array((int) $a->id, $anteriores, true)), 'area_ids', 'Uma área foi inativada. Atualize a seleção antes de salvar.');
            }
            $this->campo(!DB::table('disciplinas')->where('espaco_id', $v->espaco_id)->where('nome_chave', $chave)->when($id, fn ($q) => $q->where('id', '!=', $id))->exists(), 'nome', 'Esta disciplina já está no catálogo da escola.');
            $ref = $antes?->referencia ?? ($dados['referencia'] ?? null);
            if (!$antes && $ref) abort_unless(collect(ReferenciasDisciplinas::todas())->contains('chave', $ref), 422, 'Referência não encontrada.');
            $ativo = (bool) ($dados['ativo'] ?? true);
            if (!$ativo) abort_if($this->atribuicoesAbertas($v->espaco_id)->whereIn('turma_disciplina_id', DB::table('turma_disciplinas')->where('disciplina_id', $id)->select('id'))->exists(), 422, 'Encerre ou cancele as atribuições abertas antes de inativar a disciplina.');
            $valores = ['nome' => $nome, 'nome_chave' => $chave, 'sigla' => trim($dados['sigla'] ?? '') ?: null, 'ativo' => $ativo,
                'referencia' => $ref, 'referencia_edicao' => $antes?->referencia_edicao ?? ($ref ? ReferenciasDisciplinas::EDICAO : null),
                'versao' => ($antes?->versao ?? 0) + 1, 'updated_at' => now()];
            if ($antes) DB::table('disciplinas')->where('id', $id)->update($valores);
            else $id = DB::table('disciplinas')->insertGetId($valores + ['espaco_id' => $v->espaco_id, 'created_at' => now()]);
            if ($areasIds !== null) {
                DB::table('disciplina_areas')->where('espaco_id', $v->espaco_id)->where('disciplina_id', $id)->whereNotIn('area_id', $areasIds)->delete();
                $anteriores = array_map(fn ($a) => (int) $a->id, $areasAntes);
                foreach (array_diff($areasIds, $anteriores) as $area) DB::table('disciplina_areas')->insert(['espaco_id' => $v->espaco_id, 'disciplina_id' => $id, 'area_id' => $area]);
            }
            $depois = DB::table('disciplinas')->find($id);
            if ($antes) $antes->areas = $areasAntes;
            $depois->areas = $this->areasDaDisciplina((int) $v->espaco_id, $id);
            $this->evento($v, 'disciplina', $id, $antes ? 'atualizada' : 'criada', $antes, $depois);
            return $depois;
        }, 3);
    }

    public function indicar(Usuario $u, int $disciplina, array $dados): object
    {
        return DB::transaction(function () use ($u, $disciplina, $dados) {
            $v = $this->travarAtor($u); $d = $this->travarDisciplina($v, $disciplina);
            $professor = $this->travarProfessor($v, (int) $dados['vinculo_id'], (bool) $dados['ativo']);
            $q = DB::table('disciplina_professores')->where('espaco_id', $v->espaco_id)->where('disciplina_id', $disciplina)->where('vinculo_id', $professor->id);
            $antes = (clone $q)->lockForUpdate()->first();
            if ($antes) $this->versao($antes, $dados['versao'] ?? 0);
            abort_unless($d->ativo || !$dados['ativo'], 422, 'Reative a disciplina antes de indicar professores.');
            if (!$dados['ativo']) abort_if($this->atribuicoesAbertas($v->espaco_id)->where('vinculo_id', $professor->id)
                ->whereIn('turma_disciplina_id', DB::table('turma_disciplinas')->where('disciplina_id', $disciplina)->select('id'))->exists(), 422, 'Encerre ou cancele as atribuições abertas deste professor antes de retirar a indicação.');
            $valores = ['ativo' => $dados['ativo'], 'versao' => ($antes?->versao ?? 0) + 1, 'updated_at' => now()];
            if ($antes) $q->update($valores);
            else DB::table('disciplina_professores')->insert($valores + ['espaco_id' => $v->espaco_id, 'disciplina_id' => $disciplina, 'vinculo_id' => $professor->id, 'created_at' => now()]);
            $depois = $q->first(); $this->evento($v, 'indicacao', $depois->id, $dados['ativo'] ? 'indicada' : 'retirada', $antes, $depois);
            return $depois;
        }, 3);
    }

    public function turma(Usuario $u, int $id): object
    {
        $v = $this->contexto($u);
        $t = Escopo::turmas($u)->where('turmas.espaco_id', $v->espaco_id)->where('turmas.escola_id', $v->espaco->escola_id)->find($id);
        abort_unless($t, 404, 'Turma não encontrada.'); return $t;
    }

    public function componentes(Usuario $u, int $turma): array
    {
        $t = $this->turma($u, $turma); $v = $this->contexto($u);
        $q = DB::table('turma_disciplinas as td')->join('disciplinas as d', 'd.id', '=', 'td.disciplina_id')
            ->where('td.espaco_id', $v->espaco_id)->where('td.turma_id', $t->id);
        if ($v->perfil === 'professor') $q->whereExists(fn ($a) => $a->selectRaw('1')->from('docente_atribuicoes as a')->whereColumn('a.turma_disciplina_id', 'td.id')->where('a.vinculo_id', $v->id));
        return $q->orderBy('d.nome')->get(['td.*', 'd.nome', 'd.sigla', 'd.ativo as disciplina_ativa'])->map(function ($c) use ($u, $v) {
            $c->atribuicoes = $this->atribuicoes($v, $c->id); return $c;
        })->all();
    }

    public function adicionarComponente(Usuario $u, int $turma, int $disciplina): object
    {
        return DB::transaction(function () use ($u, $turma, $disciplina) {
            $v = $this->travarAtor($u); $d = $this->travarDisciplina($v, $disciplina); $t = $this->turma($u, $turma);
            DB::table('turmas')->where('id', $t->id)->lockForUpdate()->first();
            abort_unless($d->ativo && $t->ativo, 422, 'Turma e disciplina precisam estar ativas.');
            $chave = ['espaco_id' => $v->espaco_id, 'turma_id' => $t->id, 'disciplina_id' => $d->id];
            if ($existente = DB::table('turma_disciplinas')->where($chave)->first()) return $existente;
            $id = DB::table('turma_disciplinas')->insertGetId($chave + ['versao' => 1, 'created_at' => now(), 'updated_at' => now()]);
            $depois = DB::table('turma_disciplinas')->find($id); $this->evento($v, 'componente', $id, 'adicionado', null, $depois); return $depois;
        }, 3);
    }

    public function componente(Usuario $u, int $turma, int $componente): object
    {
        $c = collect($this->componentes($u, $turma))->firstWhere('id', $componente);
        abort_unless($c, 404, 'Disciplina da turma não encontrada.'); return $c;
    }

    public function situacaoComponente(Usuario $u, int $turma, int $componente, array $dados): object
    {
        return DB::transaction(function () use ($u, $turma, $componente, $dados) {
            $v = $this->travarAtor($u); $c = $this->componente($u, $turma, $componente); $d = $this->travarDisciplina($v, $c->disciplina_id);
            $c = DB::table('turma_disciplinas')->where('id', $c->id)->lockForUpdate()->first(); $this->versao($c, $dados['versao']);
            if ($dados['ativo']) abort_unless($d->ativo && $this->turma($u, $turma)->ativo, 422, 'Turma e catálogo precisam estar ativos para reativar esta disciplina.');
            else abort_if($this->atribuicoesAbertas($v->espaco_id)->where('turma_disciplina_id', $c->id)->exists(), 422, 'Encerre ou cancele as atribuições abertas antes de inativar a disciplina nesta turma.');
            DB::table('turma_disciplinas')->where('id', $c->id)->update(['ativo' => $dados['ativo'], 'versao' => $c->versao + 1, 'updated_at' => now()]);
            $depois = DB::table('turma_disciplinas')->find($c->id); $this->evento($v, 'componente', $c->id, $dados['ativo'] ? 'reativado' : 'inativado', $c, $depois); return $depois;
        }, 3);
    }

    public function elegiveis(Usuario $u, int $turma, int $disciplina): array
    {
        $this->turma($u, $turma); $this->disciplina($u, $disciplina);
        return $this->professores($u)->whereIn('v.id', DB::table('disciplina_professores')->where('disciplina_id', $disciplina)->where('ativo', true)->select('vinculo_id'))
            ->whereIn('v.id', DB::table('vinculo_turmas')->where('turma_id', $turma)->select('vinculo_id'))->get()->all();
    }

    public function atribuir(Usuario $u, int $turma, int $componente, array $dados): object
    {
        return DB::transaction(function () use ($u, $turma, $componente, $dados) {
            $v = $this->travarAtor($u); abort_unless(Permissao::pode($u, 'turma.vincular_professor'), 403);
            $c = $this->componente($u, $turma, $componente); $d = $this->travarDisciplina($v, $c->disciplina_id);
            $c = DB::table('turma_disciplinas')->where('id', $c->id)->lockForUpdate()->first();
            $pedido = ['turma_disciplina_id' => $c->id, 'vinculo_id' => (int) $dados['vinculo_id'], 'inicio_em' => $dados['inicio_em'], 'fim_em' => $dados['fim_em'] ?? null, 'papel' => $dados['papel']];
            $hash = hash('sha256', json_encode($pedido, JSON_THROW_ON_ERROR));
            $anterior = DB::table('docente_atribuicoes')->where('espaco_id', $v->espaco_id)->where('ator_vinculo_id', $v->id)->where('chave', $dados['chave'])->first();
            if ($anterior) { abort_unless(hash_equals($anterior->pedido_hash, $hash), 409, 'Esta operação já foi usada com outros dados.'); return $anterior; }
            $this->versao($c, $dados['versao']); $t = $this->turma($u, $turma);
            abort_unless($d->ativo && $t->ativo && $c->ativo, 422, 'Turma, catálogo e disciplina da turma precisam estar ativos.');
            $p = $this->travarProfessor($v, $pedido['vinculo_id']);
            $this->campo(DB::table('disciplina_professores')->where('espaco_id', $v->espaco_id)->where('disciplina_id', $d->id)->where('vinculo_id', $p->id)->where('ativo', true)->exists(), 'vinculo_id', 'Adicione o professor à disciplina antes de atribuí-lo.');
            $this->campo(DB::table('vinculo_turmas')->where('espaco_id', $v->espaco_id)->where('turma_id', $turma)->where('vinculo_id', $p->id)->exists(), 'vinculo_id', 'O professor precisa de acesso a esta turma.');
            $hoje = $this->hoje(); $this->campo($pedido['inicio_em'] >= $hoje, 'inicio_em', 'O início não pode ser anterior a hoje.');
            $this->campo(!$pedido['fim_em'] || $pedido['fim_em'] > $pedido['inicio_em'], 'fim_em', 'O fim deve ser posterior ao início.');
            // Atribuição futura nunca ultrapassa o vínculo já conhecido.
            if ($p->fim_em) {
                $limite = \Carbon\CarbonImmutable::parse($p->fim_em, 'UTC')->setTimezone('America/Sao_Paulo')->format('Y-m-d');
                $this->campo($pedido['fim_em'] && $pedido['fim_em'] <= $limite && $pedido['inicio_em'] < $limite, 'fim_em', 'O período deve caber na vigência do vínculo do professor.');
            }
            $sobrepostas = DB::table('docente_atribuicoes')->where('turma_disciplina_id', $c->id)->where('situacao', '!=', 'cancelada')
                ->where(fn ($q) => $q->whereNull('fim_em')->orWhere('fim_em', '>', $pedido['inicio_em']));
            if ($pedido['fim_em']) $sobrepostas->where('inicio_em', '<', $pedido['fim_em']);
            $this->campo(!(clone $sobrepostas)->where('vinculo_id', $p->id)->exists(), 'inicio_em', 'Este professor já possui uma atribuição neste período.');
            $this->campo($pedido['papel'] !== 'titular' || !$sobrepostas->exists(), 'papel', 'Há outro professor neste período. Escolha codocência ou substituição, ou encerre a atribuição anterior.');
            $id = DB::table('docente_atribuicoes')->insertGetId($pedido + ['espaco_id' => $v->espaco_id, 'ator_vinculo_id' => $v->id, 'chave' => $dados['chave'], 'pedido_hash' => $hash, 'situacao' => 'ativa', 'versao' => 1, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('turma_disciplinas')->where('id', $c->id)->increment('versao');
            $depois = DB::table('docente_atribuicoes')->find($id); $this->evento($v, 'atribuicao', $id, 'criada', null, $depois); return $depois;
        }, 3);
    }

    public function encerrar(Usuario $u, int $turma, int $componente, int $id, array $dados): object
    {
        return DB::transaction(function () use ($u, $turma, $componente, $id, $dados) {
            $v = $this->travarAtor($u); abort_unless(Permissao::pode($u, 'turma.vincular_professor'), 403);
            $c = $this->componente($u, $turma, $componente); $this->travarDisciplina($v, $c->disciplina_id);
            DB::table('turma_disciplinas')->where('id', $c->id)->lockForUpdate()->first();
            $antes = DB::table('docente_atribuicoes')->where('espaco_id', $v->espaco_id)->where('turma_disciplina_id', $c->id)->where('id', $id)->lockForUpdate()->first();
            abort_unless($antes, 404); $this->versao($antes, $dados['versao']);
            $hoje = $this->hoje(); abort_unless($antes->situacao === 'ativa' && (!$antes->fim_em || $antes->fim_em > $hoje), 422, 'Esta atribuição já terminou.');
            $futura = $antes->inicio_em > $hoje;
            abort_unless($dados['acao'] === ($futura ? 'cancelar' : 'encerrar'), 422, $futura ? 'Cancele a atribuição que ainda não começou.' : 'Encerre a atribuição em curso.');
            $valores = ['situacao' => $futura ? 'cancelada' : 'encerrada', 'versao' => $antes->versao + 1, 'updated_at' => now()];
            if (!$futura) $valores['fim_em'] = $hoje;
            DB::table('docente_atribuicoes')->where('id', $id)->update($valores);
            DB::table('turma_disciplinas')->where('id', $c->id)->increment('versao');
            $depois = DB::table('docente_atribuicoes')->find($id); $this->evento($v, 'atribuicao', $id, $futura ? 'cancelada' : 'encerrada', $antes, $depois); return $depois;
        }, 3);
    }

    private function atribuicoes(object $v, int $componente): array
    {
        $q = DB::table('docente_atribuicoes as a')->join('usuario_vinculos as v', 'v.id', '=', 'a.vinculo_id')->join('usuarios as u', 'u.id', '=', 'v.usuario_id')
            ->where('a.espaco_id', $v->espaco_id)->where('a.turma_disciplina_id', $componente);
        if ($v->perfil === 'professor') $q->where('a.vinculo_id', $v->id);
        return $q->orderByDesc('a.inicio_em')->orderByDesc('a.id')->get(['a.id', 'a.vinculo_id', 'a.inicio_em', 'a.fim_em', 'a.papel', 'a.situacao', 'a.versao', 'u.nome', 'v.situacao as vinculo_situacao', 'v.inicio_em as vinculo_inicio', 'v.fim_em as vinculo_fim', 'u.ativo as usuario_ativo'])->map(function ($a) {
            $hoje = $this->hoje();
            $a->estado_periodo = match (true) {
                $a->situacao === 'cancelada' => 'Cancelada', $a->situacao === 'encerrada' => 'Encerrada',
                $a->fim_em && $a->fim_em <= $hoje => 'Concluída', $a->inicio_em > $hoje => 'Programada', default => 'Em curso',
            };
            $a->vinculo_vigente = (bool) $a->usuario_ativo && $a->vinculo_situacao === 'ativo' && $a->vinculo_inicio <= now() && (!$a->vinculo_fim || $a->vinculo_fim > now());
            unset($a->vinculo_inicio, $a->vinculo_fim, $a->usuario_ativo);
            return $a;
        })->all();
    }

    private function travarAtor(Usuario $u): object
    {
        DB::table('usuario_vinculos')->where('id', $u->vinculoContextoId())->lockForUpdate()->first();
        return $this->contexto($u, true);
    }

    private function travarDisciplina(object $v, int $id): object
    {
        $d = DB::table('disciplinas')->where('espaco_id', $v->espaco_id)->where('id', $id)->lockForUpdate()->first();
        abort_unless($d, 404, 'Disciplina não encontrada.'); return $d;
    }

    private function travarProfessor(object $v, int $id, bool $vigente = true): object
    {
        $p = DB::table('usuario_vinculos')->where('espaco_id', $v->espaco_id)->where('id', $id)->where('perfil', 'professor')->lockForUpdate()->first();
        abort_unless($p, 404, 'Professor não encontrado nesta escola.');
        if ($vigente) {
            $this->campo($p->situacao === 'ativo' && $p->inicio_em <= now() && (!$p->fim_em || $p->fim_em > now()) && DB::table('usuarios')->where('id', $p->usuario_id)->where('ativo', true)->exists(), 'vinculo_id', 'O vínculo Professor não está vigente.');
            GruposFuncionais::conferir((int) $p->usuario_id, (int) $p->espaco_id, 'professor', now(), $p->fim_em, (int) $p->id);
        }
        return $p;
    }

    private function atribuicoesAbertas(int $espaco): Builder
    {
        return DB::table('docente_atribuicoes')->where('espaco_id', $espaco)->where('situacao', 'ativa')->where(fn ($q) => $q->whereNull('fim_em')->orWhere('fim_em', '>', $this->hoje()));
    }

    private function versao(object $registro, int $versao): void
    {
        abort_unless((int) $registro->versao === $versao, 409, 'Os dados mudaram. Atualize a página antes de salvar novamente.');
    }

    private function hoje(): string { return now('America/Sao_Paulo')->toDateString(); }

    private function campo(bool $valido, string $campo, string $mensagem): void
    {
        if (!$valido) throw ValidationException::withMessages([$campo => $mensagem]);
    }

    /** Auditoria interna compartilhada com o catálogo de áreas, sempre sob a transação autorizada. */
    public function evento(object $v, string $entidade, int $id, string $acao, ?object $antes, object $depois): void
    {
        $limpar = function (?object $r): ?string { if (!$r) return null; $a = (array) $r; unset($a['chave'], $a['pedido_hash']); return json_encode($a, JSON_THROW_ON_ERROR); };
        DB::table('disciplina_eventos')->insert(['referencia' => (string) Str::uuid(), 'espaco_id' => $v->espaco_id, 'ator_vinculo_id' => $v->id,
            'entidade' => $entidade, 'entidade_id' => $id, 'acao' => $acao, 'antes' => $limpar($antes), 'depois' => $limpar($depois), 'ocorrido_em' => now()]);
    }
}
