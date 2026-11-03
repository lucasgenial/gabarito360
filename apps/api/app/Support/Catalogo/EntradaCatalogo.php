<?php

namespace App\Support\Catalogo;

use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

final class EntradaCatalogo
{
    public static function normalizar(string $texto): string
    {
        return preg_replace('/\s+/u',' ',mb_strtolower(Str::ascii(trim($texto))));
    }

    public static function projetar(object $observacao, object $publicacao): array
    {
        $r = json_decode($observacao->payload,true,512,JSON_THROW_ON_ERROR);
        $base = $r['namespace'].'/'.$r['codigo'];
        $instituicao = Uuid::uuid5(Uuid::NAMESPACE_URL,'https://gabarito360.com.br/catalogo/instituicao/'.$base)->toString();
        $unidade = Uuid::uuid5(Uuid::NAMESPACE_URL,'https://gabarito360.com.br/catalogo/unidade/'.$base.'/'.$r['natureza_localizacao'])->toString();
        $dependencias = $r['fonte'] === 'basica'
            ? ['1'=>'federal','2'=>'estadual','3'=>'municipal','4'=>'privada']
            : ['1'=>'federal','2'=>'estadual','3'=>'municipal','4'=>'privada','5'=>'privada','7'=>'especial'];
        return ['publicacao_id'=>$publicacao->id,'importacao_id'=>$publicacao->importacao_id,'geografia_id'=>$publicacao->geografia_id,
            'instituicao_id'=>$instituicao,'unidade_id'=>$unidade,'observacao_id'=>(int)$observacao->id,'ordem'=>(int)$observacao->ordem,
            'namespace'=>$r['namespace'],'codigo'=>$r['codigo'],'natureza'=>$r['natureza_localizacao'],
            'nome'=>$r['nome'],'sigla'=>$r['sigla'],'nome_normalizado'=>self::normalizar($r['nome']),
            'sigla_normalizada'=>$r['sigla'] === null ? null : self::normalizar($r['sigla']),
            'tipo'=>$r['tipo'],'dependencia_fonte'=>$r['dependencia_fonte'],'dependencia'=>$dependencias[$r['dependencia_fonte']] ?? null,
            'situacao_fonte'=>$r['situacao_fonte'],'uf'=>$r['uf'],'municipio_ibge'=>$r['municipio_ibge']];
    }

    public static function serializar(array $entrada): string { return json_encode($entrada,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR); }
}
