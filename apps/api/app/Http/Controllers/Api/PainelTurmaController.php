<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\{PainelTurma,EmissaoDocumento};
use App\Support\{Cabecalho,EspacoPessoal,ContextoCorrecao,Permissao};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;

class PainelTurmaController extends Controller
{
    private function filtros(Request $r): array { return $r->validate(['disciplina_id'=>'nullable|integer|min:1','professor_id'=>'nullable|integer|min:1','inicio'=>'nullable|date_format:Y-m-d','fim'=>'nullable|date_format:Y-m-d|after_or_equal:inicio']); }
    public function show(Request $r,int $id) { return ApiResponse::success(app(PainelTurma::class)->montar($r->user(),$id,$this->filtros($r))); }
    public function pdf(Request $r,int $id)
    {
        $f=$this->filtros($r); $o=$r->validate(['orientacao'=>'nullable|in:retrato,paisagem']);
        $versao=ContextoCorrecao::versaoAcesso($r->user());
        $dto=app(PainelTurma::class)->montar($r->user(),$id,$f);
        $espaco=\App\Support\Escopo::turmas($r->user())->findOrFail($id)->espaco_id;
        $emissao=app(EmissaoDocumento::class); $e=$emissao->preparar($r->user(),'painel_turma',$dto['cabecalho']['nome']??'',$espaco);
        $saida=tempnam(sys_get_temp_dir(),'g360_turma_'); abort_unless($saida!==false,503); chmod($saida,0600);
        try {
            $d=$dto+['saida'=>$saida,'orientacao'=>$o['orientacao']??'retrato','emissao'=>$e['pdf'],'identidade'=>Cabecalho::paraPdf($dto['cabecalho'],(bool)EspacoPessoal::doAtor($r->user()))];
            $p=Process::path(config('omr.scripts'))->timeout(90)->input(json_encode($d,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))->run([config('omr.python'),'gerar_painel_turma.py']);
            abort_unless($p->successful()&&is_file($saida)&&filesize($saida)>0,503,'Não foi possível gerar o documento.');
            abort_unless(hash_equals($versao,ContextoCorrecao::versaoAcesso($r->user()))&&Permissao::pode($r->user(),'relatorio.turma'),403);
            $atual=app(PainelTurma::class)->montar($r->user(),$id,$f);
            abort_unless(hash_equals(hash('sha256',json_encode($dto)),hash('sha256',json_encode($atual))),409,'Os dados mudaram. Gere novamente o relatório.');
            $emissao->registrar($e,$saida);
            return response()->download($saida,'painel-turma.pdf',['Content-Type'=>'application/pdf','Cache-Control'=>'private, no-store'])->deleteFileAfterSend();
        }catch(\Throwable $e){@unlink($saida);throw $e;}
    }
}
