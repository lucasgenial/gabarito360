<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\Escolas\CreateEscolaAction;
use App\Actions\Escolas\InactivateEscolaAction;
use App\Actions\Escolas\UpdateEscolaAction;
use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Escolas\ListEscolasRequest;
use App\Http\Requests\Escolas\StoreEscolaRequest;
use App\Http\Requests\Escolas\UpdateEscolaRequest;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\User;
use App\Services\Authorization\SchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EscolaController extends Controller
{
    public function index(ListEscolasRequest $request, SchoolScope $schoolScope): View
    {
        Gate::authorize('viewAny', Escola::class);

        $actor = $this->actor($request->user());
        $filters = $request->validated();
        $query = $schoolScope->apply(Escola::query()->with('nucleo'), $actor)
            ->orderBy('nome')
            ->orderBy('id');

        if (isset($filters['nucleo_id'])) {
            $query->where('nucleo_id', $filters['nucleo_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($query) use ($filters): void {
                $query
                    ->where('nome', 'like', '%'.$filters['search'].'%')
                    ->orWhere('codigo', 'like', '%'.$filters['search'].'%');
            });
        }

        $escolas = $query->paginate($filters['per_page'] ?? 20)->withQueryString();
        $nucleos = Nucleo::query()
            ->where('status', StatusEnum::ACTIVE->value)
            ->orderBy('nome')
            ->get()
            ->filter(fn (Nucleo $nucleo): bool => Gate::forUser($actor)->allows('create', [Escola::class, $nucleo]));

        return view('admin.escolas.index', compact('escolas', 'nucleos', 'filters'));
    }

    public function store(StoreEscolaRequest $request, CreateEscolaAction $action): RedirectResponse
    {
        $attributes = $request->validated();
        $nucleo = Nucleo::query()->findOrFail($attributes['nucleo_id']);
        Gate::authorize('create', [Escola::class, $nucleo]);
        $action->execute($attributes, $this->actor($request->user()));

        return back()->with('success', 'Escola criada com sucesso.');
    }

    public function edit(Escola $escola): View
    {
        Gate::authorize('update', $escola);
        $escola->load('nucleo');

        return view('admin.escolas.edit', compact('escola'));
    }

    public function update(
        UpdateEscolaRequest $request,
        Escola $escola,
        UpdateEscolaAction $action,
    ): RedirectResponse {
        Gate::authorize('update', $escola);
        $action->execute($escola, $request->validated(), $this->actor($request->user()));

        return back()->with('success', 'Escola atualizada com sucesso.');
    }

    public function destroy(
        ListEscolasRequest $request,
        Escola $escola,
        InactivateEscolaAction $action,
    ): RedirectResponse {
        Gate::authorize('delete', $escola);
        $action->execute($escola, $this->actor($request->user()));

        return redirect()->route('admin.escolas.index')->with('success', 'Escola inativada com sucesso.');
    }

    private function actor(mixed $actor): User
    {
        abort_unless($actor instanceof User, 401);

        return $actor;
    }
}
