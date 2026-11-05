<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerfilController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public function show(): View
    {
        $resp = $this->api->get('/v1/auth/me');

        if ($resp->failed()) {
            abort(422, 'Não foi possível carregar seu perfil.');
        }

        return view('perfil.show', ['usuario' => $resp->json('data')]);
    }

    public function imagem(Request $r) {
        $d=$r->validateWithBag('imagem',['tipo'=>'required|in:avatar,foto','avatar_id'=>'nullable|integer|between:1,36','foto'=>'required_if:tipo,foto|file|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=4096,max_height=4096']);
        try {$resp=$r->input('tipo')==='foto'?$this->api->postFile('/v1/auth/imagem','foto',$r->file('foto'),['tipo'=>'foto']):$this->api->post('/v1/auth/imagem',['tipo'=>'avatar','avatar_id'=>$r->input('avatar_id')]);}
        catch(\Illuminate\Http\Client\ConnectionException){if($r->expectsJson())return response()->json(['message'=>'Não foi possível confirmar a atualização. Atualize a página para conferir.'],503);return back()->with('erro_imagem','Não foi possível confirmar a atualização. Atualize a página.');}
        if($resp->status()===429){
            $segundos=max(1,min(60,(int)$resp->header('Retry-After')?:60));
            $mensagem='Muitas tentativas seguidas. Aguarde alguns instantes e tente novamente.';
            if($r->expectsJson())return response()->json(['message'=>$mensagem,'retry_after'=>$segundos],429)->header('Retry-After',(string)$segundos);
            return back()->with('erro_imagem',$mensagem);
        }
        if($resp->successful()){
            $d=array_intersect_key($resp->json('data')??[],array_flip(['avatar_id','tem_foto','imagem_versao']));session(['auth_avatar_id'=>$d['avatar_id']??null,'auth_tem_foto'=>$d['tem_foto']??false,'auth_imagem_versao'=>$d['imagem_versao']??1]);
            $d['foto_url']=route('perfil.foto',['v'=>$d['imagem_versao']??1]);
            if($r->expectsJson())return response()->json(['data'=>$d]);
        } elseif($r->expectsJson()) return response()->json(['message'=>$resp->json('message')??'Não foi possível atualizar a imagem.'], $resp->status()>=500?503:422);
        return back()->with($resp->successful()?'sucesso_imagem':'erro_imagem',$resp->successful()?'Imagem de perfil atualizada.':($resp->json('message')??'Não foi possível atualizar a imagem.'));
    }
    public function foto(){return $this->arquivo('/v1/auth/foto');}
    public function fotoUsuario(int $id){return $this->arquivo('/v1/usuarios/'.$id.'/foto');}
    private function arquivo(string $path){
        $r=$this->api->getArquivo($path);abort_unless($r->successful(),$r->status()===403?403:404);
        return response($r->body())->header('Content-Type','image/webp')->header('Cache-Control','private, no-store')->header('X-Content-Type-Options','nosniff');
    }

    public function update(Request $request)
    {
        $request->validate(['nome' => 'required|string|max:200']);

        $resp = $this->api->put('/v1/auth/me', ['nome' => $request->input('nome')]);

        if ($resp->failed()) {
            return back()->with('erro', $resp->json('message') ?? 'Não foi possível atualizar os dados.');
        }

        session(['auth_nome' => $request->input('nome')]);

        return back()->with('sucesso', 'Dados atualizados.');
    }

    public function formTrocarSenha()
    {
        return view('auth.trocar-senha');
    }

    /** Troca obrigatória: exigida pelo gestor, antes de liberar o sistema. */
    public function trocarSenha(Request $request)
    {
        $request->validate([
            'senha_atual' => 'required|string',
            'password'    => 'required|string|min:8|confirmed',
        ]);

        $resp = $this->api->put('/v1/auth/me', $request->only('senha_atual', 'password', 'password_confirmation'));

        if ($resp->failed()) {
            return back()->with('erro', $resp->json('message') ?? 'Não foi possível alterar a senha.');
        }

        session()->forget('auth_trocar_senha');

        return redirect()->route($request->boolean('retorno_convite') ? 'vinculos.convite' : 'painel')->with('sucesso', 'Senha alterada.');
    }

    public function senha(Request $request)
    {
        $request->validate([
            'senha_atual'          => 'required|string',
            'password'             => 'required|string|min:8|confirmed',
        ]);

        $resp = $this->api->put('/v1/auth/me', [
            'senha_atual'          => $request->input('senha_atual'),
            'password'             => $request->input('password'),
            'password_confirmation'=> $request->input('password_confirmation'),
        ]);

        if ($resp->failed()) {
            return back()->with('erro', $resp->json('message') ?? 'Não foi possível alterar a senha.');
        }

        return back()->with('sucesso', 'Senha alterada.');
    }

    public function email(Request $request)
    {
        $dados = $request->validate([
            'novo_email'  => 'required|email|max:190',
            'senha_atual' => 'required|string',
        ]);

        $resp = $this->api->post('/v1/auth/email', $dados);

        if ($resp->failed()) {
            return back()->with('erro', $resp->json('message') ?? 'Não foi possível pedir a troca de e-mail.');
        }

        return back()->with('sucesso', 'Enviamos um código de confirmação para o novo e-mail. O atual segue valendo até lá.');
    }

    /** Abrir a página nunca confirma nem consome o código. */
    public function showConfirmarEmail(Request $request)
    {
        if ($request->hasAny(['token', 'conta', 'email'])) return redirect()->route('email.confirmar')
            ->with('erro', 'Cole o código recebido por e-mail no formulário.');
        return view('auth.confirmar-email');
    }

    /** Confirmação explícita por POST, protegida pelo middleware web/CSRF. */
    public function confirmarEmail(Request $request)
    {
        abort_if($request->query->has('token'), 422, 'Cole o código no formulário.');
        $dados = $request->validate(['token' => 'required|alpha_num:ascii|size:64'], [
            'token.required' => 'Cole o código recebido por e-mail.',
            'token.alpha_num' => 'Copie os 64 caracteres do código recebido por e-mail.',
            'token.size' => 'Copie os 64 caracteres do código recebido por e-mail.',
        ]);
        try { $resp = $this->api->post('/v1/auth/confirmar-email', $dados); }
        catch (\Illuminate\Http\Client\ConnectionException) {
            return redirect()->route('email.confirmar')->with('erro', 'Não foi possível confirmar agora. Tente novamente.');
        }
        $destino = session('auth_token') ? 'perfil' : 'login';

        if ($resp->failed()) {
            return redirect()->route('email.confirmar')->with('erro', 'Código inválido ou expirado. Peça a confirmação de e-mail novamente.');
        }

        return redirect()->route($destino)->with('sucesso', 'E-mail confirmado.');
    }
}
