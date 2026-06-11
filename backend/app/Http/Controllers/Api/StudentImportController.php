<?php

namespace App\Http\Controllers\Api;

use App\Actions\StudentImports\ConfirmStudentImportAction;
use App\Actions\StudentImports\CreateStudentImportAction;
use App\Http\Requests\StudentImports\StoreStudentImportRequest;
use App\Http\Resources\StudentImportResource;
use App\Models\ImportacaoAluno;
use App\Models\User;
use App\Services\Authorization\StudentImportScope;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

class StudentImportController extends BaseApiController
{
    public function store(StoreStudentImportRequest $request, CreateStudentImportAction $action): JsonResponse
    {
        $file = $request->file('arquivo');
        abort_unless($file instanceof UploadedFile, 422);
        $import = $action->execute(
            $file,
            $request->school(),
            $request->class(),
            $this->actor($request->user()),
        );

        return $this->successResponse(StudentImportResource::make($import)->resolve($request), 201);
    }

    public function show(Request $request, string $importacao, StudentImportScope $scope): JsonResponse
    {
        $import = $this->authorizedImport($importacao, $this->actor($request->user()), $scope);
        Gate::authorize('view', $import);

        return $this->successResponse(StudentImportResource::make($import)->resolve($request));
    }

    public function confirm(
        Request $request,
        string $importacao,
        StudentImportScope $scope,
        ConfirmStudentImportAction $action,
    ): JsonResponse {
        $actor = $this->actor($request->user());
        $import = $this->authorizedImport($importacao, $actor, $scope);
        Gate::authorize('confirm', $import);
        $import = $action->execute($import, $actor);

        return $this->successResponse(StudentImportResource::make($import)->resolve($request), 202);
    }

    private function authorizedImport(string $id, User $actor, StudentImportScope $scope): ImportacaoAluno
    {
        $import = ImportacaoAluno::query()->with('escola')->find($id);

        if (! $import instanceof ImportacaoAluno || ! $scope->canAccess($actor, $import)) {
            throw (new ModelNotFoundException)->setModel(ImportacaoAluno::class, [$id]);
        }

        return $import;
    }

    private function actor(mixed $actor): User
    {
        abort_unless($actor instanceof User, 401);

        return $actor;
    }
}
