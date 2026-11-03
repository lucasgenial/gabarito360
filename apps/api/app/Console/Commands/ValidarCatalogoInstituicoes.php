<?php
namespace App\Console\Commands;

use App\Support\Catalogo\ManifestoCatalogo;
use Illuminate\Console\Command;

/** CI1-B1: valida fontes nacionais sem abrir espaços ou alterar dados. */
class ValidarCatalogoInstituicoes extends Command
{
    protected $signature = 'g360:catalogo-validar {arquivo} {--fonte=basica} {--ano=2025} {--ufs=} {--municipios=} {--encoding=Windows-1252} {--sha256=} {--nacional}';
    protected $description = 'Valida o catálogo público INEP/IBGE em streaming, sem gravar no banco';

    public function handle(ManifestoCatalogo $validador): int
    {
        try {
            $resultado = $validador->validar($this->argument('arquivo'), $this->option('fonte'), (int) $this->option('ano'),
                (string) $this->option('ufs'), (string) $this->option('municipios'), $this->option('encoding'),
                $this->option('sha256'), (bool) $this->option('nacional'));
            $this->line(json_encode($resultado['manifesto'], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        } catch (\Throwable $e) { $this->error($e->getMessage()); return self::FAILURE; }
    }
}