<?php
namespace App\Http\Controllers;

use App\Services\ApiClient;
use App\Support\Acesso;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;

class InstituicoesPessoaisController extends Controller
{
    public const TIPOS=['escola'=>'Escola','faculdade'=>'Faculdade','centro_universitario'=>'Centro universitário','universidade'=>'Universidade','instituto_federal'=>'Instituto federal','cefet'=>'CEFET','instituto'=>'Instituto','outro'=>'Outro'];
    public function __construct(private ApiClient $api) {}
    private function autorizar(bool $gravar=false): void
    {
        abort_unless(config('catalogo.interface_ativa')===true,404);
        abort_unless(Acesso::pessoal() && Acesso::pode('catalogo.consultar') && (!$gravar || Acesso::pode('catalogo.referencia_pessoal.gerir')),403);
    }
    private function get(string $path,array $query=[]): array
    {
        try { $r=$this->api->get('/v1/catalogo/'.$path,$query); }
        catch(ConnectionException) { abort(503,'Não foi possível consultar o catálogo. Tente novamente.'); }
        $status=in_array($r->status(),[403,404,409,422],true)?$r->status():503;
        abort_unless($r->successful(),$status,match($status){403=>'Acesso indisponível neste contexto.',404=>'Referência não encontrada.',409=>'O catálogo mudou. Volte à busca e confira novamente.',422=>'Filtros inválidos. Confira a UF, o município, o tipo e o código.',default=>'Não foi possível consultar o catálogo. Tente novamente.'});
        return $r->json('data');
    }
    public function index(Request $r)
    {
        $this->autorizar();$f=$r->validate(['page'=>'sometimes|integer|min:1|max:100000']);
        return view('instituicoes.index',['dados'=>$this->get('referencias-pessoais',$f),'tipos'=>self::TIPOS]);
    }
    public function buscar(Request $r)
    {
        $this->autorizar();
        $f=array_filter($r->only(['uf','municipio_ibge','tipo','dependencia','busca','namespace','codigo','situacao','page']),fn($v)=>$v!==null && $v!=='');
        $erros=[];
        foreach($f as $campo=>$valor)if(!is_scalar($valor)){unset($f[$campo]);$erros[$campo]=['Informe um único valor para este filtro.'];}
        $modo=$r->query('modo')==='codigo' || (!empty($f['codigo']) && $r->query('modo')!=='nome') ? 'codigo' : 'nome';
        $ufs=$this->get('ufs')['itens'];$municipios=[];
        if(!empty($f['uf']) && !empty($f['municipio_ibge'])) {
            try {$resp=$this->api->get('/v1/catalogo/municipios',['uf'=>$f['uf'],'codigo_ibge'=>$f['municipio_ibge']]);}
            catch(ConnectionException){abort(503,'Não foi possível consultar o catálogo. Tente novamente.');}
            if($resp->status()===422)$erros=$resp->json('errors')??['municipio_ibge'=>['Confira a UF e o município.']];
            elseif($resp->successful())$municipios=$resp->json('data.itens')??[];
            else abort(in_array($resp->status(),[403,404],true)?$resp->status():503);
        }
        $temBusca=count(array_intersect(array_keys($f),['uf','municipio_ibge','busca','codigo']))>0;
        if(!$temBusca && $r->boolean('pesquisar'))$erros['busca']=['Informe um nome, uma localização ou use a busca por código.'];
        $dados=null;
        if($temBusca && !$erros){
            try {$resp=$this->api->get('/v1/catalogo/instituicoes',$f);}
            catch(ConnectionException){abort(503,'Não foi possível consultar o catálogo. Tente novamente.');}
            if($resp->status()===422)$erros=$resp->json('errors')??['busca'=>['Confira os filtros da busca.']];
            elseif($resp->successful())$dados=$resp->json('data');
            else abort(in_array($resp->status(),[403,404,409],true)?$resp->status():503,'Não foi possível consultar o catálogo. Tente novamente.');
        }
        return response()->view('instituicoes.buscar',compact('f','modo','ufs','municipios','dados')+[
            'tipos'=>self::TIPOS,'errors'=>(new \Illuminate\Support\ViewErrorBag())->put('default',new \Illuminate\Support\MessageBag($erros)),
        ],$erros?422:200);
    }
    public function municipios(Request $r)
    {
        $this->autorizar();
        return response()->json($this->get('municipios',array_filter($r->only(['uf','busca','page']),fn($v)=>$v!==null && $v!=='')));
    }
    public function conferir(Request $r,string $unidade)
    {
        $this->autorizar();$v=$r->validate(['edicao_id'=>'required|uuid']);$e=$this->get('instituicoes/'.$unidade);
        abort_unless($e['edicao_id']===strtolower($v['edicao_id']),409,'O catálogo mudou. Volte à busca e confira novamente.');
        return view('instituicoes.conferir',['e'=>$e,'tipos'=>self::TIPOS]);
    }
    public function selecionar(Request $r)
    {
        $this->autorizar(true);$d=$r->validate(['unidade_id'=>'required|uuid','edicao_id'=>'required|uuid','confirmacao'=>'required|accepted']);
        try { $resp=$this->api->post('/v1/catalogo/referencias-pessoais',$d); }
        catch(ConnectionException) { return back()->with('erro','Não foi possível confirmar a operação. Consulte Minhas instituições antes de tentar novamente.'); }
        if($resp->successful()) return redirect()->route('instituicoes.index')->with('sucesso','Instituição adicionada às suas referências pessoais.');
        abort_if($resp->status()===403,403);
        return redirect()->route('instituicoes.buscar')->with('erro',$resp->status()===409?'O catálogo mudou. Busque e confira novamente.':'Não foi possível adicionar. Confira a instituição novamente.');
    }
    public function arquivar(Request $r,string $id)
    {
        $this->autorizar(true);$d=$r->validate(['versao'=>'required|integer|min:1']);
        try { $resp=$this->api->post('/v1/catalogo/referencias-pessoais/'.$id.'/arquivar',$d); }
        catch(ConnectionException) { return back()->with('erro','Não foi possível confirmar o arquivamento. Atualize a lista antes de repetir.'); }
        abort_if(in_array($resp->status(),[403,404],true),$resp->status());
        return redirect()->route('instituicoes.index')->with($resp->successful()?'sucesso':'erro',$resp->successful()?'Referência arquivada. O histórico foi preservado.':'Não foi possível arquivar. Atualize a lista e tente novamente.');
    }
}
