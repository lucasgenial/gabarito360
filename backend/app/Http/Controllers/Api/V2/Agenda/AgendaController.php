<?php

namespace App\Http\Controllers\Api\V2\Agenda;

use App\Events\CalendarEventChanged;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Resources\Api\V2\EventoAgendaResource;
use App\Models\Escola;
use App\Models\EventoAgenda;
use App\Models\ParticipanteEvento;
use App\Models\User;
use App\Services\Atividades\ActivityService;
use App\Services\Authorization\PortalScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AgendaController extends BaseApiController
{
    public function __construct(private PortalScope $scope) {}

    /**
     * Lista eventos da agenda no escopo do ator (escola/núcleo das lotações ou
     * onde participa). Filtros: `?de=`, `?ate=`, `?escola_id=`, `?turma_id=`.
     *
     * GET /api/v2/agenda
     */
    public function index(Request $request): JsonResponse
    {
        $actor = $this->actor($request);
        $query = EventoAgenda::query();

        if (! $this->scope->isGlobalViewer($actor)) {
            $escolaIds = $this->scope->accessibleSchoolIds($actor);
            $nucleoIds = $this->scope->accessibleNucleoIds($actor);

            $query->where(function (Builder $scoped) use ($escolaIds, $nucleoIds, $actor): void {
                $scoped
                    ->whereIn('escola_id', $escolaIds)
                    ->orWhereIn('nucleo_id', $nucleoIds)
                    ->orWhereHas('participantes', fn (Builder $p) => $p->where('usuario_id', $actor->id));
            });
        }

        if (($de = $request->query('de')) !== null) {
            $query->where('inicio_at', '>=', $de);
        }
        if (($ate = $request->query('ate')) !== null) {
            $query->where('inicio_at', '<=', $ate);
        }
        if (($escolaId = $request->query('escola_id')) !== null) {
            $query->where('escola_id', $escolaId);
        }
        if (($turmaId = $request->query('turma_id')) !== null) {
            $query->where('turma_id', $turmaId);
        }

        $eventos = $query->with('participantes')->orderBy('inicio_at')->paginate(20);

        return $this->paginatedResponse($eventos, EventoAgendaResource::class);
    }

    /**
     * Cria um evento de agenda no escopo autorizado e convida participantes.
     * Idempotente via `Idempotency-Key`. Publica `calendar.event.changed`.
     *
     * POST /api/v2/agenda
     */
    public function store(Request $request, ActivityService $activity): JsonResponse
    {
        $validated = $request->validate([
            'tipo' => ['required', 'string', 'max:40'],
            'titulo' => ['required', 'string', 'max:180'],
            'descricao' => ['nullable', 'string'],
            'nucleo_id' => ['nullable', 'uuid', 'exists:nucleos,id'],
            'escola_id' => ['nullable', 'uuid', 'exists:escolas,id'],
            'turma_id' => ['nullable', 'uuid', 'exists:turmas,id'],
            'prova_id' => ['nullable', 'uuid', 'exists:provas,id'],
            'local' => ['nullable', 'string', 'max:180'],
            'inicio_at' => ['required', 'date'],
            'fim_at' => ['nullable', 'date', 'after_or_equal:inicio_at'],
            'participantes' => ['nullable', 'array'],
            'participantes.*' => ['uuid', 'exists:usuarios,id'],
        ]);

        $actor = $this->actor($request);
        [$escolaId, $nucleoId] = $this->resolveScope($validated, $actor);

        $evento = DB::transaction(function () use ($validated, $actor, $escolaId, $nucleoId, $activity): EventoAgenda {
            $evento = EventoAgenda::query()->create([
                'tipo' => $validated['tipo'],
                'titulo' => $validated['titulo'],
                'descricao' => $validated['descricao'] ?? null,
                'nucleo_id' => $nucleoId,
                'escola_id' => $escolaId,
                'turma_id' => $validated['turma_id'] ?? null,
                'prova_id' => $validated['prova_id'] ?? null,
                'local' => $validated['local'] ?? null,
                'inicio_at' => $validated['inicio_at'],
                'fim_at' => $validated['fim_at'] ?? null,
                'status' => 'agendado',
                'criado_por_id' => $actor->id,
            ]);

            // Organizador (criador) já entra confirmado.
            ParticipanteEvento::query()->create([
                'evento_id' => $evento->id,
                'usuario_id' => $actor->id,
                'papel' => 'organizador',
                'status' => 'confirmado',
                'respondido_at' => now(),
            ]);

            foreach (array_unique($validated['participantes'] ?? []) as $usuarioId) {
                if ($usuarioId === $actor->id) {
                    continue;
                }
                ParticipanteEvento::query()->create([
                    'evento_id' => $evento->id,
                    'usuario_id' => $usuarioId,
                    'papel' => 'convidado',
                    'status' => 'convidado',
                ]);
            }

            $activity->record('agenda.evento_criado', 'Evento criado: '.$evento->titulo, [
                'ator_id' => $actor->id,
                'nucleo_id' => $nucleoId,
                'escola_id' => $escolaId,
                'sujeito_tipo' => 'evento_agenda',
                'sujeito_id' => $evento->id,
            ]);

            CalendarEventChanged::dispatch($evento, 'created');

            return $evento;
        });

        return $this->successResponse(new EventoAgendaResource($evento->load('participantes')), 201);
    }

    /**
     * Detalhe de um evento (no escopo do ator).
     *
     * GET /api/v2/agenda/{evento}
     */
    public function show(Request $request, EventoAgenda $evento): JsonResponse
    {
        abort_unless($this->podeVer($evento, $this->actor($request)), 403);

        return $this->successResponse(new EventoAgendaResource($evento->load('participantes')));
    }

    /**
     * Atualiza um evento. Publica `calendar.event.changed`.
     *
     * PUT /api/v2/agenda/{evento}
     */
    public function update(Request $request, EventoAgenda $evento, ActivityService $activity): JsonResponse
    {
        $actor = $this->actor($request);
        abort_unless($this->podeVer($evento, $actor), 403);

        $validated = $request->validate([
            'titulo' => ['sometimes', 'string', 'max:180'],
            'descricao' => ['nullable', 'string'],
            'local' => ['nullable', 'string', 'max:180'],
            'inicio_at' => ['sometimes', 'date'],
            'fim_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'in:agendado,confirmado,cancelado,concluido'],
        ]);

        $inicio = $validated['inicio_at'] ?? $evento->inicio_at;
        if (isset($validated['fim_at']) && $validated['fim_at'] !== null && strtotime((string) $validated['fim_at']) < strtotime((string) $inicio)) {
            throw ValidationException::withMessages(['fim_at' => ['O término não pode ser anterior ao início.']]);
        }

        $evento->update($validated);

        $activity->record('agenda.evento_atualizado', 'Evento atualizado: '.$evento->titulo, [
            'ator_id' => $actor->id,
            'nucleo_id' => $evento->nucleo_id,
            'escola_id' => $evento->escola_id,
            'sujeito_tipo' => 'evento_agenda',
            'sujeito_id' => $evento->id,
        ]);

        $acao = ($validated['status'] ?? null) === 'cancelado' ? 'cancelled' : 'updated';
        CalendarEventChanged::dispatch($evento, $acao);

        return $this->successResponse(new EventoAgendaResource($evento->refresh()->load('participantes')));
    }

    /**
     * Confirma a participação do ator no evento (somente participante).
     *
     * POST /api/v2/agenda/{evento}/confirmar
     */
    public function confirmar(Request $request, EventoAgenda $evento): JsonResponse
    {
        $actor = $this->actor($request);
        $participante = ParticipanteEvento::query()
            ->where('evento_id', $evento->id)
            ->where('usuario_id', $actor->id)
            ->first();

        abort_if($participante === null, 403);

        $participante->update(['status' => 'confirmado', 'respondido_at' => now()]);

        return $this->successResponse(new EventoAgendaResource($evento->load('participantes')));
    }

    /**
     * Resolve e autoriza o escopo (escola/núcleo) do evento.
     *
     * @param  array<string, mixed>  $validated
     * @return array{0: ?string, 1: ?string} [escolaId, nucleoId]
     */
    private function resolveScope(array $validated, User $actor): array
    {
        $escolaId = $validated['escola_id'] ?? null;
        $nucleoId = $validated['nucleo_id'] ?? null;

        if ($escolaId !== null) {
            $escola = Escola::query()->findOrFail($escolaId);
            abort_unless($this->scope->canViewSchool($actor, $escola), 403);

            return [$escolaId, $nucleoId ?? $escola->nucleo_id];
        }

        if ($nucleoId !== null) {
            abort_unless($this->scope->canViewNucleo($actor, $nucleoId), 403);

            return [null, $nucleoId];
        }

        throw ValidationException::withMessages(['escopo' => ['Informe escola_id ou nucleo_id.']]);
    }

    private function podeVer(EventoAgenda $evento, User $actor): bool
    {
        if ($this->scope->isGlobalViewer($actor)) {
            return true;
        }
        if ($evento->escola_id !== null && $this->scope->accessibleSchoolIds($actor)->contains($evento->escola_id)) {
            return true;
        }
        if ($evento->nucleo_id !== null && $this->scope->accessibleNucleoIds($actor)->contains($evento->nucleo_id)) {
            return true;
        }

        return $evento->participantes()->where('usuario_id', $actor->id)->exists();
    }
}
