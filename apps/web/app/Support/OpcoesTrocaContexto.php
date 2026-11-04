<?php
namespace App\Support;

use App\Services\ApiClient;
use Illuminate\Http\Client\ConnectionException;

/** Opções da própria identidade; nenhum token ou poder adicional vai para o navegador. */
final class OpcoesTrocaContexto
{
    public function obter(): array
    {
        $r=request();
        if ($r->attributes->has('opcoes_troca_contexto')) return $r->attributes->get('opcoes_troca_contexto');
        $resultado=['opcoes'=>[], 'disponivel'=>false, 'erro'=>false];
        $atual=ContextoWeb::atual();
        if (session('auth_vinculos') && $atual) {
            try {
                $api=app(ApiClient::class)->identidade();
                $v=$api->get('/v1/auth/vinculos');
                $vinculos=$v->json('data');
                if (!$v->successful() || !is_array($vinculos) || !array_is_list($vinculos)) {
                    $resultado['erro']=true;
                } else {
                    foreach ($vinculos as $item) {
                        if (!is_array($item) || empty($item['id']) || empty($item['perfil']) || empty($item['espaco']['nome'])) continue;
                        $resultado['opcoes'][]=['tipo'=>'vinculo','id'=>(int)$item['id'],'nome'=>$item['espaco']['nome'],'perfil'=>$item['perfil'],
                            'atual'=>($atual['tipo_contexto'] ?? 'vinculo')!=='interno' && (int)($atual['vinculo_id'] ?? 0)===(int)$item['id']];
                    }
                    if (session('auth_internos')) {
                        $i=$api->get('/v1/auth/acessos-internos');
                        $internos=$i->json('data');
                        if ($i->status()!==404 && (!$i->successful() || !is_array($internos) || !array_is_list($internos))) $resultado['erro']=true;
                        elseif ($i->successful()) foreach ($internos as $item) {
                            if (!is_array($item) || empty($item['id']) || empty($item['perfil'])) continue;
                            $resultado['opcoes'][]=['tipo'=>'interno','id'=>(int)$item['id'],'nome'=>'Equipe Gabarito360','perfil'=>$item['perfil'],
                                'atual'=>($atual['tipo_contexto'] ?? '')==='interno' && (int)($atual['acesso_interno_id'] ?? 0)===(int)$item['id']];
                        }
                    }
                }
            } catch (ConnectionException $e) { $resultado['erro']=true; }
            $resultado['disponivel']=!$resultado['erro'] && collect($resultado['opcoes'])->contains(fn($o)=>!$o['atual']);
        }
        $r->attributes->set('opcoes_troca_contexto',$resultado);
        return $resultado;
    }
}
