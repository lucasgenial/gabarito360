<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Usuario;
use App\Support\{ContextoVinculo,GestaoVinculos,Permissao};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Storage};
use Illuminate\Support\Str;

class ImagemUsuarioController extends Controller
{
    public function salvar(Request $r) {
        $d=$r->validate(['tipo'=>'required|in:foto,avatar','avatar_id'=>'nullable|integer|between:1,36',
            'foto'=>'required_if:tipo,foto|file|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=4096,max_height=4096']);
        $novo=null;
        if($d['tipo']==='foto'){
            $info=getimagesize($r->file('foto')->getRealPath());
            abort_unless($info && $info[0]*$info[1]<=12000000,422,'A imagem deve ter no máximo 12 milhões de pixels.');
            $origem=imagecreatefromstring($r->file('foto')->get());abort_unless($origem,422,'Imagem inválida.');
            $destino=imagecreatetruecolor(384,384);imagefill($destino,0,0,imagecolorallocate($destino,255,255,255));$lado=min(imagesx($origem),imagesy($origem));
            imagecopyresampled($destino,$origem,0,0,(int)((imagesx($origem)-$lado)/2),(int)((imagesy($origem)-$lado)/2),384,384,$lado,$lado);
            ob_start();imagewebp($destino,null,85);$bytes=ob_get_clean();imagedestroy($origem);imagedestroy($destino);
            $novo='usuarios/fotos/'.Str::uuid().'.webp';
            abort_unless(Storage::disk('local')->put($novo,$bytes),503,'Não foi possível salvar a imagem.');
        }
        try {
            $antigo=DB::transaction(function()use($r,$d,$novo){
                $u=Usuario::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();$antigo=$u->foto_path;
                $u->forceFill(['foto_path'=>$novo,'avatar_id'=>$d['tipo']==='avatar'?($d['avatar_id']??null):null,'imagem_versao'=>$u->imagem_versao+1])->save();return $antigo;
            });
        }catch(\Throwable $e){if($novo)Storage::disk('local')->delete($novo);throw $e;}
        if($antigo)Storage::disk('local')->delete($antigo);
        return ApiResponse::success(['mensagem'=>'Imagem de perfil atualizada.']);
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
