<?php

namespace App\Support\Catalogo;

use RuntimeException;

/** Validação pura compartilhada pela simulação e preparação persistida. */
class ManifestoCatalogo
{
    public const FONTES = [
        'basica' => ['ano'=>2025, 'schema'=>'basica_2025_v2_1', 'revisao'=>'v2',
            'url'=>'https://download.inep.gov.br/dados_abertos/microdados_censo_escolar_2025_.zip',
            'membro'=>'microdados_censo_escolar_2025_v2/dados/Tabela_Escola_2025_V2.csv'],
        'superior' => ['ano'=>2024, 'schema'=>'superior_2024_1', 'revisao'=>'original',
            'url'=>'https://download.inep.gov.br/microdados/microdados_censo_da_educacao_superior_2024.zip',
            'membro'=>'microdados_censo_da_educacao_superior_2024/dados/MICRODADOS_ED_SUP_IES_2024.CSV'],
    ];

    public function validar(string $arquivo, string $fonte, int $ano, string $ufs, string $municipios,
        string $encoding = 'Windows-1252', ?string $esperado = null, bool $nacional = false): array
    {
        $origem = self::FONTES[$fonte] ?? null;
        if (!$origem || $origem['ano'] !== $ano) throw new RuntimeException('Edição exige adaptador próprio.');
        $arquivos = ['fonte'=>$arquivo, 'ufs'=>$ufs, 'municipios'=>$municipios];
        $hashes = [];
        foreach ($arquivos as $tipo => $caminho) {
            if (!is_file($caminho) || !is_readable($caminho)) throw new RuntimeException('Informe os três arquivos públicos legíveis: fonte, UFs e municípios.');
            $hashes[$tipo] = hash_file('sha256', $caminho);
        }
        if ($esperado !== null && (!preg_match('/^[a-f0-9]{64}$/Di', $esperado) || !hash_equals(strtolower($esperado), $hashes['fonte']))) {
            throw new RuntimeException('SHA-256 da fonte divergente.');
        }
        $geo = LeitorInstituicoes::geografia($ufs, $municipios);
        if ($nacional && count($geo['ufs']) !== 27) throw new RuntimeException('Geografia nacional precisa conter as 27 UFs.');
        $vistos = []; $porUf = []; $tipos = []; $linhas = 0; $derivadas = 0; $campos = [];
        $digest = hash_init('sha256');
        foreach ((new LeitorInstituicoes($geo['municipios']))->ler($arquivo, $fonte, $ano, $encoding) as $r) {
            if (isset($vistos[$r['codigo']])) throw new RuntimeException('Identificador duplicado na fonte.');
            $vistos[$r['codigo']] = true; $linhas++;
            $porUf[$r['uf']] = ($porUf[$r['uf']] ?? 0) + 1;
            $tipos[$r['tipo']] = ($tipos[$r['tipo']] ?? 0) + 1;
            if ($r['uf_derivada_ibge']) $derivadas++;
            $campos = $r['campos_presentes'];
            hash_update($digest, self::payload($r)."\n");
        }
        if (!$linhas || ($nacional && count($porUf) !== 27)) throw new RuntimeException('Fonte vazia ou cobertura nacional incompleta.');
        $this->conferirIntegridade($arquivos, $hashes);
        ksort($porUf); ksort($tipos);
        return ['geografia'=>$geo, 'manifesto'=>[
            'modo'=>'validacao_sem_gravacao', 'fonte'=>$fonte, 'ano'=>$ano, 'encoding'=>$encoding,
            'schema'=>$origem['schema'], 'revisao'=>$origem['revisao'], 'origem_url'=>$origem['url'], 'membro'=>$origem['membro'],
            'sha256'=>$hashes['fonte'], 'hashes'=>$hashes, 'nacional'=>$nacional,
            'ibge_ufs'=>count($geo['ufs']), 'ibge_municipios'=>count($geo['municipios']),
            'registros'=>$linhas, 'por_uf'=>$porUf, 'por_tipo'=>$tipos, 'ufs_derivadas_ibge'=>$derivadas,
            'campos_presentes'=>$campos, 'observacoes_sha256'=>hash_final($digest), 'memoria_pico_bytes'=>memory_get_peak_usage(true),
        ]];
    }

    public static function payload(array $r): string
    {
        $campos = ['linha','fonte','ano','namespace','codigo','nome','sigla','tipo','uf','uf_codigo',
            'municipio_ibge','municipio_nome','uf_derivada_ibge','uf_original','situacao_fonte','dependencia_fonte','natureza_localizacao'];
        return json_encode(array_intersect_key($r, array_flip($campos)), JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    }

    public function conferirIntegridade(array $arquivos, array $hashes): void
    {
        foreach ($arquivos as $tipo => $arquivo) {
            if (!is_file($arquivo) || !hash_equals($hashes[$tipo], hash_file('sha256', $arquivo))) {
                throw new RuntimeException('Arquivo mudou durante a validação ou preparação.');
            }
        }
    }
}
