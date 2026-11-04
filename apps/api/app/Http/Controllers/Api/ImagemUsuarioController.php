<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Usuario;
use App\Support\{ContextoVinculo,GestaoVinculos,Permissao};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Storage};

class ImagemUsuarioController extends Controller
{
    public function salvar(Request $r) {
        $d=$r->validate(['tipo'=>'required|in:foto,avatar','avatar_id'=>'nullable|integer|between:1,36',
            'foto'=>'required_if:tipo,foto|file|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=4096,max_height=4096']);
        $novo=$d['tipo']==='foto' ? app(\App\Services\ImagemPerfil::class)->guardar($r->file('foto'),'usuarios/fotos') : null;
        try {
            $antigo=DB::transaction(function()use($r,$d,$novo){
                $u=Usuario::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();$antigo=$u->foto_path;
                $u->forceFill(['foto_path'=>$novo,'avatar_id'=>$d['tipo']==='avatar'?($d['avatar_id']??null):null,'imagem_versao'=>$u->imagem_versao+1])->save();return $antigo;
            });
        }catch(\Throwable $e){if($novo)Storage::disk('local')->delete($novo);throw $e;}
        if($antigo)Storage::disk('local')->delete($antigo);
        $u=Usuario::findOrFail($r->user()->id);
        return ApiResponse::success(['mensagem'=>'Imagem de perfil atualizada.','avatar_id'=>$u->avatar_id,'tem_foto'=>$u->tem_foto,'imagem_versao'=>$u->imagem_versao]);
    }
    public function propria(Request $r){return $this->arquivo(Usuario::findOrFail($r->user()->id));}
    public function mostrar(Request $r,int $id){
        $u=$r->user();
        abort_unless($id===(int)$u->id || (ContextoVinculo::presente($u)&&Permissao::pode($u,'usuario.ver')&&GestaoVinculos::consulta($u,true)->where('v.usuario_id',$id)->exists()),404);
        return $this->arquivo(Usuario::findOrFail($id));
    }
    private function arquivo(Usuario $u){
        abort_unless($u->foto_path && str_starts_with($u->foto_path,'usuarios/fotos/') && Storage::disk('local')->exists($u->foto_path),404);
        return response(Storage::disk('local')->get($u->foto_path))->header('Content-Type','image/webp')->header('Cache-Control','private, no-store')->header('X-Content-Type-Options','nosniff');
    }
}
