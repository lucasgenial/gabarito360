<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Turma;
use App\Services\{ArquivamentoCadastro,ImagemPerfil};
use App\Support\Escopo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImagemTurmaController extends Controller
{
    public function salvar(Request $r, int $id)
    {
        // Resolver o alcance antes de validar ou processar o arquivo.
        Escopo::turmas($r->user())->findOrFail($id);
        $d = $r->validate([
            'tipo'=>'required|in:foto,avatar',
            'avatar_id'=>'nullable|integer|between:1,36',
            'foto'=>'required_if:tipo,foto|'.ImagemPerfil::REGRA,
        ]);
        $novo = $d['tipo']==='foto'
            ? app(ImagemPerfil::class)->guardar($r->file('foto'), 'turmas/fotos') : null;
        $antigo = null;
        try {
            // Mesma barreira ator→espaço→turma e autorização atual das edições cadastrais.
            // A imagem é metadado: turma arquivada permanece editável, sem associação nova.
            $turma = app(ArquivamentoCadastro::class)->atualizar($r->user(),'turma',$id,[],
                function (Turma $t) use ($d,$novo,&$antigo) {
                    abort_if($t->imagem_versao >= 4294967295,409,'Atualização da imagem indisponível.');
                    $antigo = $t->foto_path;
                    $t->forceFill(['foto_path'=>$novo,
                        'avatar_id'=>$d['tipo']==='avatar'&&isset($d['avatar_id'])?(int)$d['avatar_id']:null,
                        'imagem_versao'=>$t->imagem_versao+1])->save();
                });
        } catch (\Throwable $e) {
            if ($novo) Storage::disk('local')->delete($novo);
            throw $e;
        }
        // UUIDs exclusivos: uma gravação concorrente nunca reutiliza um arquivo antigo.
        if ($this->caminhoValido($antigo)) Storage::disk('local')->delete($antigo);
        return ApiResponse::success(['avatar_id'=>$turma->avatar_id,
            'tem_foto'=>$turma->tem_foto,'imagem_versao'=>$turma->imagem_versao]);
    }

    public function mostrar(Request $r, int $id)
    {
        $turma = Escopo::turmas($r->user())->findOrFail($id);
        abort_unless($this->caminhoValido($turma->foto_path)
            && Storage::disk('local')->exists($turma->foto_path),404);
        return response(Storage::disk('local')->get($turma->foto_path))
            ->header('Content-Type','image/webp')->header('Cache-Control','private, no-store')
            ->header('X-Content-Type-Options','nosniff');
    }

    private function caminhoValido(?string $caminho): bool
    {
        return is_string($caminho)
            && preg_match('~\Aturmas/fotos/[a-f0-9-]{36}\.webp\z~D',$caminho)===1;
    }
}
