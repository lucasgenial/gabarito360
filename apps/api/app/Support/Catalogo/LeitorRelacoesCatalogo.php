<?php

namespace App\Support\Catalogo;

use RuntimeException;
use SplTempFileObject;

final class LeitorRelacoesCatalogo
{
    public const SCHEMA = 'relacoes_2025_v1';
    private const COLUNAS = ['NU_ANO_CENSO','CO_ENTIDADE','CO_ESCOLA_SEDE_VINCULADA','CO_IES_OFERTANTE'];

    public function preparar(string $arquivo, string $sha256, int $totalEsperado, int $ano = 2025): PacoteRelacoesCatalogo
    {
        if ($ano !== 2025 || $totalEsperado<1) throw new RuntimeException('Edição de relações não suportada.');
        $this->conferirArquivo($arquivo,$sha256);
        $h = fopen($arquivo,'rb');
        if (!$h) throw new RuntimeException('Arquivo de relações indisponível.');
        $temp = new SplTempFileObject(2*1024*1024);
        $grafo = []; $vistos = []; $linhas = 0; $ordem = 0; $tipos = ['sede_escolar'=>0,'ies_vinculada'=>0];
        try {
            $cab = fgetcsv($h,262144,';','"','');
            if (!$cab) throw new RuntimeException('Cabeçalho de relações ausente.');
            $cab = array_map(fn($c)=>trim(ltrim($c,"\xEF\xBB\xBF")),$cab);
            if (count($cab)!==count(array_unique($cab)) || array_diff(self::COLUNAS,$cab)) throw new RuntimeException('Cabeçalho de relações incompatível.');
            $indices = array_flip($cab);
            while (($dados = fgetcsv($h,262144,';','"','')) !== false) {
                $linhas++;
                if (count($dados)!==count($cab)) throw new RuntimeException('Linha de relações com quantidade de campos incompatível.');
                $codigo = trim($dados[$indices['CO_ENTIDADE']]);
                if (trim($dados[$indices['NU_ANO_CENSO']]) !== (string)$ano || !preg_match('/^[0-9]{8}$/D',$codigo) || isset($vistos[$codigo])) throw new RuntimeException('Ano, origem ou duplicidade inválidos no arquivo de relações.');
                $vistos[$codigo] = true;
                foreach (['sede_escolar'=>'CO_ESCOLA_SEDE_VINCULADA','ies_vinculada'=>'CO_IES_OFERTANTE'] as $tipo=>$campo) {
                    $bruto = trim($dados[$indices[$campo]]);
                    if ($bruto === '' || preg_match('/^0+$/D',$bruto)) continue;
                    if (!preg_match($tipo === 'sede_escolar' ? '/^[0-9]{8}$/D' : '/^[0-9]{1,14}$/D',$bruto)) throw new RuntimeException('Código de relação incompatível com o dicionário.');
                    $alvo = $tipo === 'sede_escolar' ? $bruto : ltrim($bruto,'0');
                    $r = ['ordem'=>++$ordem,'linha_fonte'=>$linhas+1,'origem_codigo'=>$codigo,'tipo'=>$tipo,
                        'alvo_namespace'=>$tipo === 'sede_escolar' ? 'inep_escola' : 'inep_ies','alvo_codigo_bruto'=>$bruto,'alvo_codigo'=>$alvo];
                    $temp->fwrite(PacoteRelacoesCatalogo::json($r)."\n"); $tipos[$tipo]++;
                    if ($tipo === 'sede_escolar') $grafo[$codigo] = $alvo;
                }
            }
        } finally { fclose($h); }
        if ($linhas !== $totalEsperado) throw new RuntimeException('Contagem do arquivo de relações não corresponde à publicação.');
        $this->conferirArquivo($arquivo,$sha256);
        $alertas = []; $visitados = [];
        foreach ($grafo as $inicio=>$_) {
            $caminho = []; $n = (string)$inicio;
            while (isset($grafo[$n]) && !isset($visitados[$n])) {
                if (isset($caminho[$n])) {
                    $ciclo = array_slice(array_keys($caminho),$caminho[$n]);
                    foreach ($ciclo as $codigo) $alertas[$codigo] = count($ciclo) === 1 ? 'autorreferencia' : 'ciclo';
                    break;
                }
                $caminho[$n] = count($caminho); $n = $grafo[$n];
            }
            foreach ($caminho as $codigo=>$_) $visitados[$codigo] = true;
        }
        return new PacoteRelacoesCatalogo($temp,['schema'=>self::SCHEMA,'ano'=>$ano,'sha256'=>$sha256,
            'registros_fonte'=>$linhas,'total'=>$ordem,'por_tipo'=>$tipos,'campos'=>self::COLUNAS],$alertas);
    }

    public function conferirArquivo(string $arquivo, string $esperado): void
    {
        if (!preg_match('/^[a-f0-9]{64}$/D',$esperado) || !is_file($arquivo) || !is_readable($arquivo)
            || !hash_equals($esperado,hash_file('sha256',$arquivo))) throw new RuntimeException('SHA-256 do arquivo de relações divergente.');
    }
}
