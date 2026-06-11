<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\Nucleos\CreateNucleoAction;
use App\Actions\Nucleos\InactivateNucleoAction;
use App\Actions\Nucleos\UpdateNucleoAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Nucleos\ListNucleosRequest;
use App\Http\Requests\Nucleos\StoreNucleoRequest;
use App\Http\Requests\Nucleos\UpdateNucleoRequest;
use App\Models\Nucleo;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class NucleoController extends Controller
{
    public function index(ListNucleosRequest $request): View
    {
        Gate::authorize('viewAny', Nucleo::class);

        $filters = $request->validated();
        $query = Nucleo::query()->orderBy('nome')->orderBy('id');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($query) use ($filters): void {
                $query
                    ->where('nome', 'ilike', '%'.$filters['search'].'%')
                    ->orWhere('codigo', 'ilike', '%'.$filters['search'].'%');
            });
        }

        $nucleos = $query->paginate($filters['per_page'] ?? 20)->withQueryString();

        return view('admin.nucleos.index', compact('nucleos', 'filters'));
    }

    public function store(StoreNucleoRequest $request, CreateNucleoAction $action): RedirectResponse
    {
        Gate::authorize('create', Nucleo::class);
        $action->execute($request->validated(), $this->actor($request->user()));

        return back()->with('success', 'Nucleo criado com sucesso.');
    }

    public function edit(Nucleo $nucleo): View
    {
        Gate::authorize('update', $nucleo);

        return view('admin.nucleos.edit', compact('nucleo'));
    }

    public function update(
        UpdateNucleoRequest $request,
        Nucleo $nucleo,
        UpdateNucleoAction $action,
    ): RedirectResponse {
        Gate::authorize('update', $nucleo);
        $action->execute($nucleo, $request->validated(), $this->actor($request->user()));

        return back()->with('success', 'Nucleo atualizado com sucesso.');
    }

    public function destroy(
        ListNucleosRequest $request,
        Nucleo $nucleo,
        InactivateNucleoAction $action,
    ): RedirectResponse {
        Gate::authorize('delete', $nucleo);
        $action->execute($nucleo, $this->actor($request->user()));

        return redirect()->route('admin.nucleos.index')->with('success', 'Nucleo inativado com sucesso.');
    }

    private function actor(mixed $actor): User
    {
        abort_unless($actor instanceof User, 401);

        return $actor;
    }
}
