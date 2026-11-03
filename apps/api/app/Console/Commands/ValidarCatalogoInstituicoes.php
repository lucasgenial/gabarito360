<?php
namespace App\Console\Commands;

use App\Support\Catalogo\LeitorInstituicoes;
use Illuminate\Console\Command;
use RuntimeException;

/** CI1-B1: valida fontes nacionais sem importar cadastros, abrir espaços ou alterar dados. */
class ValidarCatalogoInstituicoes extends Command
{
    protected $signature='g360:catalogo-validar {arquivo} {--fonte=basica} {--ano=2025} {--ufs=} {--municipios=} {--encoding=Windows-1252} {--sha256=} {--nacional}';
    protected $description='Valida o catálogo público INEP/IBGE em streaming, sem gravar no banco';

    public function handle(): int
    {
        try {
            foreach ([$this->argument('arquivo'),$this->option('ufs'),$this->option('municipios')] as $arquivo) {
                if (!is_string($arquivo) || !is_file($arquivo) || !is_readable($arquivo)) throw new RuntimeException('Informe os três arquivos públicos legíveis: fonte, UFs e municípios.');
            }
            $arquivo=$this->argument('arquivo'); $hash=hash_file('sha256',$arquivo);
            if ($this->option('sha256') && !hash_equals(strtolower($this->option('sha256')),$hash)) throw new RuntimeException('SHA-256 da fonte divergente.');
            $geo=LeitorInstituicoes::geografia($this->option('ufs'),$this->option('municipios'));
            if ($this->option('nacional') && count($geo['ufs'])!==27) throw new RuntimeException('Geografia nacional precisa conter as 27 UFs.');
            $leitor=new LeitorInstituicoes($geo['municipios']);
            $vistos=[]; $ufs=[]; $tipos=[]; $linhas=0; $derivadas=0;
            foreach ($leitor->ler($arquivo,$this->option('fonte'),(int)$this->option('ano'),$this->option('encoding')) as $r) {
                if (isset($vistos[$r['codigo']])) throw new RuntimeException('Identificador duplicado na fonte.');
                $vistos[$r['codigo']]=true; $linhas++;
                $ufs[$r['uf']]=($ufs[$r['uf']]??0)+1;
                $tipos[$r['tipo']]=($tipos[$r['tipo']]??0)+1;
                if ($r['uf_derivada_ibge']) $derivadas++;
            }
            if (!$linhas || ($this->option('nacional') && count($ufs)!==27)) throw new RuntimeException('Fonte vazia ou cobertura nacional incompleta.');
            if (!hash_equals($hash,hash_file('sha256',$arquivo))) throw new RuntimeException('Arquivo mudou durante a validação.');
            ksort($ufs); ksort($tipos);
            $this->line(json_encode(['modo'=>'validacao_sem_gravacao','fonte'=>$this->option('fonte'),'ano'=>(int)$this->option('ano'),
                'sha256'=>$hash,'ibge_ufs'=>count($geo['ufs']),'ibge_municipios'=>count($geo['municipios']),
                'registros'=>$linhas,'por_uf'=>$ufs,'por_tipo'=>$tipos,'ufs_derivadas_ibge'=>$derivadas,
                'memoria_pico_bytes'=>memory_get_peak_usage(true)],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        } catch (\Throwable $e) { $this->error($e->getMessage()); return self::FAILURE; }
    }
}
