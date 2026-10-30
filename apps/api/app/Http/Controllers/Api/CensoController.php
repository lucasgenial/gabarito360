<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Support\Censo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Arquivo: apps/api/app/Http/Controllers/Api/CensoController.php
 * Função: Consulta à base do Censo Escolar no cadastro de escola (docs/21, etapa 6):
 *         pelo código INEP (preenche o formulário e sugere o núcleo) ou pelo nome.
 */
class CensoController extends Controller
{
    /** GET /censo/escolas/{inep} — dados da escola no Censo, já no formato do cadastro. */
    public function buscar(Request $request, string $inep): JsonResponse
    {
        $c = DB::table('censo_escolas')->where('codigo_inep', preg_replace('/\D/', '', $inep))->first();
        if (!$c) {
            return ApiResponse::notFound('Código INEP não encontrado na base do Censo Escolar. Confira o número ou preencha os dados à mão.');
        }

        $existente = DB::table('escolas')->where('inep', $c->codigo_inep)->first(['id', 'nome']);

        return ApiResponse::success([
            'escola'          => Censo::paraEscola($c),
            'nucleo_sugerido' => $this->nucleoDoOrgao($request, $c->orgao_regional),
            'ja_cadastrada'   => $existente ? ['id' => $existente->id, 'nome' => $existente->nome] : null,
        ]);
    }

    /** GET /censo/escolas?busca=&municipio= — até 20 escolas pelo nome (para quem não sabe o INEP). */
    public function pesquisar(Request $request): JsonResponse
    {
        $busca = trim((string) $request->query('busca', ''));
        if (mb_strlen($busca) < 3) {
            return ApiResponse::success(['escolas' => []]);
        }
        $q = DB::table('censo_escolas')->where('nome', 'like', '%' . str_replace(' ', '%', $busca) . '%');
        if ($m = trim((string) $request->query('municipio', ''))) {
            $q->where('no_municipio', 'like', "%{$m}%");
        }

        $escolas = $q->orderBy('nome')->limit(20)->get()->map(fn ($c) => [
            'inep'      => $c->codigo_inep,
            'nome'      => Censo::titulo($c->nome),
            'municipio' => Censo::titulo($c->no_municipio) . '/' . $c->sg_uf,
            'esfera'    => Censo::ESFERAS[$c->tp_dependencia] ?? null,
            'situacao'  => Censo::SITUACAO[$c->tp_situacao] ?? null,
        ]);

        return ApiResponse::success(['escolas' => $escolas]);
    }

    /** Núcleo do usuário (ou da rede, para o admin) cujo órgão regional é o da escola no Censo. */
    private function nucleoDoOrgao(Request $request, ?string $orgao): ?array
    {
        if (!$orgao) {
            return null;
        }
        $u = $request->user();
        $q = DB::table('nucleos')->where('orgao_regional', $orgao);
        if ($u->perfil === 'dir_nucleo') {
            $q->whereIn('id', DB::table('usuario_escopos')->where('usuario_id', $u->id)->where('escopo_tipo', 'nucleo')->pluck('escopo_id'));
        }
        $n = $q->first(['id', 'nome']);

        return $n ? ['id' => $n->id, 'nome' => $n->nome] : null;
    }
}
