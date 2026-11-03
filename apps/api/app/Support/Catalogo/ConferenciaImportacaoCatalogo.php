<?php

namespace App\Support\Catalogo;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ConferenciaImportacaoCatalogo
{
    public function conferir(string $id, string $arquivoUfs): array
    {
        $i = DB::table('catalogo_importacoes')->where('id',$id)->first();
        if (!$i || $i->estado !== 'validado' || !$i->validado_em || (int)$i->cursor !== (int)$i->total_esperado || $i->lease_token !== null) throw new RuntimeException('Importação não está integralmente validada.');
        $m = json_decode($i->manifesto,true,512,JSON_THROW_ON_ERROR);
        if (($m['fonte']??null) !== $i->fonte || ($m['ano']??null) !== (int)$i->ano || ($m['schema']??null) !== $i->schema_versao
            || ($m['sha256']??null) !== $i->arquivo_sha256 || ($m['registros']??null) !== (int)$i->total_esperado || ($m['nacional']??false) !== true
            || ($m['hashes']['fonte']??null) !== $i->arquivo_sha256 || ($m['ibge_ufs']??null) !== 27
            || (ManifestoCatalogo::FONTES[$i->fonte]['ano']??null) !== (int)$i->ano
            || (ManifestoCatalogo::FONTES[$i->fonte]['schema']??null) !== $i->schema_versao) throw new RuntimeException('Manifesto da importação divergente.');
        if ($i->chave_ingestao !== hash('sha256',$i->fonte.':'.$i->ano.':'.$i->schema_versao.':'.($m['encoding']??'').':'.$i->arquivo_sha256.':'.$i->geografia_id)) throw new RuntimeException('Identidade da ingestão divergente.');
        $g = DB::table('catalogo_geografias')->where('id',$i->geografia_id)->first();
        if (!$g || !hash_equals($g->snapshot_sha256,hash('sha256',$g->snapshot))
            || $g->ufs_sha256 !== ($m['hashes']['ufs']??null) || $g->municipios_sha256 !== ($m['hashes']['municipios']??null)
            || $g->id !== hash('sha256',$g->ufs_sha256.$g->municipios_sha256)) throw new RuntimeException('Geografia da importação divergente.');
        $geo = json_decode($g->snapshot,true,512,JSON_THROW_ON_ERROR);
        if (count($geo['ufs']) !== 27 || count($geo['municipios']) !== ($m['ibge_municipios']??null)) throw new RuntimeException('Cobertura geográfica divergente.');
        foreach ($geo['ufs'] as $codigo=>$sigla) if ((LeitorInstituicoes::UFS_IBGE[$codigo]??null) !== $sigla) throw new RuntimeException('UF não reconhecida.');
        if (!is_file($arquivoUfs) || !is_readable($arquivoUfs) || !hash_equals($g->ufs_sha256,hash_file('sha256',$arquivoUfs))) throw new RuntimeException('Arquivo original de UFs divergente.');
        $ufs = json_decode(file_get_contents($arquivoUfs),true,512,JSON_THROW_ON_ERROR); $nomes = [];
        foreach ($ufs as $u) {
            if (!isset($u['id'],$u['sigla'],$u['nome']) || ($geo['ufs'][$u['id']]??null) !== $u['sigla'] || isset($nomes[$u['id']])
                || !is_string($u['nome']) || trim($u['nome']) === '' || mb_strlen($u['nome'])>100) throw new RuntimeException('Nomes oficiais de UFs ausentes ou divergentes.');
            $nomes[$u['id']] = trim($u['nome']);
        }
        if (count($nomes)!==27 || !hash_equals($g->ufs_sha256,hash_file('sha256',$arquivoUfs))) throw new RuntimeException('Arquivo original de UFs mudou.');
        $digest = hash_init('sha256'); $ordem = 0; $porUf = []; $porTipo = [];
        foreach (DB::table('catalogo_observacoes')->where('importacao_id',$id)->lazyById(1000,'ordem') as $o) {
            if ((int)$o->ordem !== ++$ordem || !hash_equals($o->payload_sha256,hash('sha256',$o->payload))) throw new RuntimeException('Observações incompletas ou adulteradas.');
            $r = json_decode($o->payload,true,512,JSON_THROW_ON_ERROR);
            $local = $geo['municipios'][$r['municipio_ibge']??''] ?? null;
            if (ManifestoCatalogo::payload($r) !== $o->payload || ($r['fonte']??null) !== $i->fonte || ($r['ano']??null) !== (int)$i->ano
                || ($r['namespace']??null) !== ($i->fonte === 'basica' ? 'inep_escola' : 'inep_ies')
                || $o->chave_fonte !== $r['namespace'].':'.$r['codigo'] || !$local || $r['uf'] !== $local['uf'] || $r['municipio_nome'] !== $local['nome']) throw new RuntimeException('Observação incompatível com a origem.');
            $porUf[$r['uf']] = ($porUf[$r['uf']]??0)+1; $porTipo[$r['tipo']] = ($porTipo[$r['tipo']]??0)+1;
            hash_update($digest,$o->payload."\n");
        }
        ksort($porUf); ksort($porTipo);
        if ($ordem !== (int)$i->total_esperado || !hash_equals($m['observacoes_sha256'],hash_final($digest))
            || $porUf !== $m['por_uf'] || $porTipo !== $m['por_tipo']) throw new RuntimeException('Digest ou contagem das observações divergente.');
        return ['importacao'=>$i,'manifesto'=>$m,'geografia'=>$geo,'nomes_ufs'=>$nomes];
    }
}
