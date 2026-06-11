<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\Provas\LinkProvaTurmaAction;
use App\Actions\Provas\UnlinkProvaTurmaAction;
use App\Enums\GabaritoOficialStatus;
use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Provas\DestroyProvaTurmaRequest;
use App\Http\Requests\Provas\StoreProvaTurmaRequest;
use App\Models\Prova;
use App\Models\Turma;
use App\Models\User;
use App\Services\Authorization\ProvaTurmaScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProvaTurmaController extends Controller
{
    public function index(Request $request, ProvaTurmaScope $scope): View
    {
        Gate::authorize('viewClassLinksAny', Prova::class);
        $actor = $this->actor($request->user());
        $provas = $scope->applyProvas(
            Prova::query()->with([
                'nucleo',
                'escola',
                'gabaritosOficiais' => fn ($query) => $query->where('status', GabaritoOficialStatus::CURRENT->value),
                'provaTurmas.turma.escola',
            ]),
            $actor,
        )
            ->orderByDesc('publicada_at')
            ->orderBy('titulo')
            ->paginate(20);
        $turmas = $scope->applyTurmas(
            Turma::query()
                ->with('escola.nucleo')
                ->where('status', StatusEnum::ACTIVE->value),
            $actor,
        )
            ->orderByDesc('ano_letivo')
            ->orderBy('nome')
            ->get();
        $turmasPorProva = $provas->getCollection()->mapWithKeys(
            fn (Prova $exam): array => [
                $exam->id => $turmas
                    ->filter(fn (Turma $class): bool => $scope->canLink($actor, $exam, $class))
                    ->values(),
            ],
        );

        return view('admin.provas.index', compact('provas', 'turmasPorProva'));
    }

    public function store(StoreProvaTurmaRequest $request, LinkProvaTurmaAction $action): RedirectResponse
    {
        $action->execute(
            $request->prova(),
            $request->turma(),
            $request->validated(),
            $this->actor($request->user()),
        );

        return back()->with('success', 'Turma vinculada a prova com sucesso.');
    }

    public function destroy(DestroyProvaTurmaRequest $request, UnlinkProvaTurmaAction $action): RedirectResponse
    {
        $action->execute(
            $request->prova(),
            $request->provaTurma(),
            $this->actor($request->user()),
        );

        return back()->with('success', 'Vinculo da turma removido com sucesso.');
    }

    private function actor(mixed $actor): User
    {
        abort_unless($actor instanceof User, 401);

        return $actor;
    }
}
