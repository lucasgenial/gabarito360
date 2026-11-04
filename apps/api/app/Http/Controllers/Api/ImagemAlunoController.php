<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\ImagemPerfil;
use App\Support\Escopo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Storage};

class ImagemAlunoController extends Controller
{
    public function salvar(Request $r, int $id)
    {
        // Resolver o alcance antes de receber/processar a imagem de outro aluno.
        Escopo::alunos($r->user())->findOrFail($id);
        $d = $r->validate(['tipo'=>'required|in:foto,avatar','avatar_id'=>'nullable|integer|between:1,36',
            'foto'=>'required_if:tipo,foto|'.ImagemPerfil::REGRA]);
        $novo = $d['tipo']==='foto' ? app(ImagemPerfil::class)->guardar($r->file('foto'), 'alunos/fotos') : null;
        try {
            $antigo = DB::transaction(function () use ($r,$id,$d,$novo) {
                $aluno = Escopo::alunos($r->user())->lockForUpdate()->findOrFail($id);
                $antigo = $aluno->foto_path;
                $aluno->forceFill(['foto_path'=>$novo,'avatar_id'=>$d['tipo']==='avatar'?($d['avatar_id']??null):null,
                    'imagem_versao'=>$aluno->imagem_versao+1])->save();
                return $antigo;
            });
        } catch (\Throwable $e) { if ($novo) Storage::disk('local')->delete($novo); throw $e; }
        if ($antigo) Storage::disk('local')->delete($antigo);
        $aluno = Escopo::alunos($r->user())->findOrFail($id);
        return ApiResponse::success(['avatar_id'=>$aluno->avatar_id,'tem_foto'=>$aluno->tem_foto,'imagem_versao'=>$aluno->imagem_versao]);
    }

    public function mostrar(Request $r, int $id)
    {
        $aluno = Escopo::alunos($r->user())->findOrFail($id);
        abort_unless($aluno->foto_path && str_starts_with($aluno->foto_path,'alunos/fotos/') && Storage::disk('local')->exists($aluno->foto_path),404);
        return response(Storage::disk('local')->get($aluno->foto_path))->header('Content-Type','image/webp')
            ->header('Cache-Control','private, no-store')->header('X-Content-Type-Options','nosniff');
    }
}
