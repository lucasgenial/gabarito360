<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Cliente;
use App\Services\CadastroCliente;
use App\Support\{ContextoInterno, TokenInterno};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    public static function autorizar(Request $r): void
    {
        abort_unless(TokenInterno::habilitado() && ContextoInterno::pode($r->user(), $r->isMethod('GET') ? 'cliente.ver' : 'cliente.gerir'), 403);
    }

    public function index(Request $r)
    {
        self::autorizar($r);
        $f = $r->validate(['busca'=>'nullable|string|max:100', 'tipo'=>['nullable',Rule::in(Cliente::TIPOS)],
            'modalidade'=>['nullable',Rule::in(Cliente::MODALIDADES)], 'page'=>'sometimes|integer|min:1']);
        $q = Cliente::query()->select('id','nome','tipo','modalidade','situacao');
        if (!empty($f['busca'])) $q->where('nome','like','%'.$f['busca'].'%');
        foreach (['tipo','modalidade'] as $campo) if (!empty($f[$campo])) $q->where($campo,$f[$campo]);
        return ApiResponse::success($q->orderBy('nome')->orderBy('id')->paginate(20));
    }

    public function show(Request $r, int $id)
    {
        self::autorizar($r);
        $c = Cliente::findOrFail($id);
        // Projeção explícita, somente após autorização comercial; hash nunca é exposto.
        return ApiResponse::success($c->only(['id','tipo','nome','modalidade','situacao','documento',
            'contato_nome','contato_email','contato_telefone','contrato_referencia']));
    }

    public function store(Request $r, CadastroCliente $cadastro)
    {
        self::autorizar($r);
        $c = $cadastro->cadastrar($r->user(), $r->all());
        return ApiResponse::success(['id'=>$c->id], null, 201);
    }

    public function update(Request $r, int $id, CadastroCliente $cadastro)
    {
        self::autorizar($r);
        $c = $cadastro->atualizar($r->user(), $id, $r->all());
        return ApiResponse::success(['id'=>$c->id]);
    }

    public function espacos(Request $r, int $id)
    {
        self::autorizar($r);
        Cliente::findOrFail($id);
        $r->validate(['page'=>'sometimes|integer|min:1']);
        $lista = DB::table('espacos')->where('cliente_id',$id)->select('id','nome','tipo','situacao')->orderBy('id')->paginate(20);
        $contagens = DB::table('usuario_vinculos')->whereIn('espaco_id',collect($lista->items())->pluck('id'))
            ->select('espaco_id','situacao')->selectRaw('count(*) as total')->groupBy('espaco_id','situacao')->get()->groupBy('espaco_id');
        $lista->through(function ($e) use ($contagens) {
            $e->vinculos = ($contagens->get($e->id) ?? collect())->pluck('total','situacao')->map(fn ($n)=>(int)$n)->all();
            return $e;
        });
        return ApiResponse::success($lista);
    }
}
