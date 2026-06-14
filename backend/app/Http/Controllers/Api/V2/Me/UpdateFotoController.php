<?php

namespace App\Http\Controllers\Api\V2\Me;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\V2\Me\UpdateFotoRequest;
use App\Http\Resources\Api\V2\UsuarioResource;
use App\Models\Arquivo;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateFotoController extends BaseApiController
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function __invoke(UpdateFotoRequest $request): JsonResponse
    {
        $user = $request->user();
        $file = $request->file('foto');
        $disk = (string) config('filesystems.private');

        $user = DB::transaction(function () use ($user, $file, $disk) {
            $path = Storage::disk($disk)->putFile('avatars', $file);

            $arquivo = Arquivo::query()->create([
                'disco' => $disk,
                'caminho' => $path,
                'nome_original' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'tamanho_bytes' => $file->getSize(),
                'checksum' => hash_file('sha256', $file->getRealPath()),
                'classificacao' => 'interno',
                'proprietario_tipo' => 'usuario',
                'proprietario_id' => $user->id,
                'criado_por_id' => $user->id,
            ]);

            $anterior = $user->fotoArquivo;
            $user->update(['foto_arquivo_id' => $arquivo->id]);

            if ($anterior !== null) {
                Storage::disk($anterior->disco)->delete($anterior->caminho);
                $anterior->delete();
            }

            return $user;
        });

        $this->audit->record(
            action: AuditAction::USER_UPDATED,
            entityType: 'usuario',
            entityId: $user->id,
            actorUserId: $user->id,
            metadata: ['campo' => 'foto'],
        );

        return $this->successResponse(UsuarioResource::make($user));
    }
}
