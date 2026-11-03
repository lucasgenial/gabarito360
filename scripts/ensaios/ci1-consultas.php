<?php
/** Ensaio somente leitura no catálogo nacional da homologação/clone. */
declare(strict_types=1);

use App\Services\ConsultarCatalogoInstituicoes;
use Illuminate\Support\Facades\DB;

if (PHP_SAPI !== 'cli' || getenv('G360_CI1_ENSAIO') !== '1' || gethostname() !== 'g360-homologa') {
    fwrite(STDERR, "Ensaio restrito à homologação explicitamente habilitada.\n"); exit(1);
}
$base = getenv('G360_CI1_API') ?: '/srv/gabarito360/current/apps/api';
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!app()->environment('staging') || DB::connection()->getDatabaseName() !== 'gabarito360_homolog_snapshot_20261103') {
    throw new RuntimeException('Banco/ambiente não autorizado.');
}
DB::statement('SET SESSION TRANSACTION READ ONLY');
$dir = $argv[1] ?? '';
if (!preg_match('~^/root/g360-ci1-f-[a-zA-Z0-9-]+$~D',$dir) || !is_dir($dir) || is_link($dir)) {
    throw new RuntimeException('Diretório privado do ensaio obrigatório.');
}
$casos = [
    'codigo'=>['namespace'=>'inep_escola','codigo'=>'29428254'],
    'municipio_tipo'=>['uf'=>'BA','municipio_ibge'=>'2925303','tipo'=>'escola'],
    'universidades'=>['uf'=>'BA','tipo'=>'universidade'],
    'sigla'=>['uf'=>'BA','namespace'=>'inep_ies','busca'=>'UFBA'],
    'nome'=>['uf'=>'BA','busca'=>'colegio'],
];
$limites = ['codigo'=>1000,'municipio_tipo'=>2000,'universidades'=>3000,'sigla'=>5000,'nome'=>5000];
$campos = ['instituicao_id','unidade_id','nome','sigla','namespace','codigo','tipo','natureza','dependencia',
    'dependencia_fonte','situacao_fonte','uf','municipio_ibge','municipio_nome','edicao_id','ano','fonte'];

if (($argv[2] ?? '') === 'worker') {
    $n = filter_var($argv[3] ?? null, FILTER_VALIDATE_INT);
    if ($n === false || $n < 0 || $n > 19) throw new RuntimeException('Trabalhador inválido.');
    try {
        $id = (int) DB::selectOne('SELECT CONNECTION_ID() AS id')->id;
        file_put_contents("$dir/pronto-$n.json", json_encode(['conexao'=>$id], JSON_THROW_ON_ERROR));
        $prazo = microtime(true) + 90;
        while (!is_file("$dir/iniciar")) {
            if (microtime(true) > $prazo) throw new RuntimeException('Barreira expirou.');
            usleep(20000);
        }
        $consulta = app(ConsultarCatalogoInstituicoes::class); $tempos = []; $inicial = [];
        for ($volta = 0; $volta <= 5; $volta++) {
            foreach ($casos as $nome=>$filtros) {
                $inicio = hrtime(true); $p = $consulta->buscar($filtros); $ms = (hrtime(true)-$inicio)/1e6;
                if ($p->total() < 1 || count($p->items()) > 20) throw new RuntimeException('Resultado nacional inválido.');
                foreach ($p->items() as $item) {
                    if (array_diff(array_keys((array)$item), $campos)) throw new RuntimeException('Projeção excede campos públicos.');
                    if ($item->uf !== 'BA') throw new RuntimeException('Filtro UF perdido.');
                    foreach (['tipo','namespace','codigo','municipio_ibge'] as $campo) {
                        if (isset($filtros[$campo]) && $item->$campo !== $filtros[$campo]) throw new RuntimeException('Filtro perdido.');
                    }
                }
                if ($nome === 'codigo' && $p->total() !== 1) throw new RuntimeException('Identidade duplicada.');
                if ($volta === 0) $inicial[$nome] = $ms; else $tempos[$nome][] = $ms;
            }
        }
        $a = $consulta->buscar(['uf'=>'BA','page'=>1]); $b = $consulta->buscar(['uf'=>'BA','page'=>2]);
        if (array_intersect(array_column($a->items(),'unidade_id'),array_column($b->items(),'unidade_id'))) throw new RuntimeException('Paginação repetiu unidade.');
        file_put_contents("$dir/resultado-$n.json", json_encode(['conexao'=>$id,'inicial_ms'=>$inicial,'tempos_ms'=>$tempos,
            'pico_memoria_bytes'=>memory_get_peak_usage(true),'paginacao_sem_repeticao'=>true], JSON_THROW_ON_ERROR));
        exit(0);
    } catch (Throwable $e) {
        file_put_contents("$dir/falha-$n.txt", get_class($e).': '.$e->getMessage()); exit(1);
    }
}

if (count(glob("$dir/pronto-*.json")) || is_file("$dir/iniciar")) throw new RuntimeException('Use diretório novo.');
if (DB::table('catalogo_entradas')->count() < 216753) throw new RuntimeException('Volume nacional ausente.');
$processos = []; $inicio = microtime(true); $codigos = [];
try {
    for ($n=0; $n<20; $n++) {
        $p = proc_open([PHP_BINARY,__FILE__,$dir,'worker',(string)$n],
            [0=>['file','/dev/null','r'],1=>['file',"$dir/stdout-$n.log",'a'],2=>['file',"$dir/stderr-$n.log",'a']], $pipes);
        if (!is_resource($p)) throw new RuntimeException('Falha ao iniciar trabalhador.');
        $processos[$n] = $p;
    }
    $prazo = microtime(true)+90;
    while (count(glob("$dir/pronto-*.json")) < 20) {
        if (microtime(true)>$prazo) throw new RuntimeException('Vinte conexões não abriram no prazo.');
        usleep(50000);
    }
    $ids = array_map(fn($f)=>(int)json_decode(file_get_contents($f),true,512,JSON_THROW_ON_ERROR)['conexao'],glob("$dir/pronto-*.json"));
    if (count(array_unique($ids)) !== 20) throw new RuntimeException('Conexões não distintas.');
    $abertas = DB::table('information_schema.PROCESSLIST')->whereIn('ID',$ids)->count();
    if ($abertas !== 20) throw new RuntimeException('Não coexistiram vinte conexões.');
    touch("$dir/iniciar");
    foreach ($processos as $n=>$p) { $codigos[$n] = proc_close($p); unset($processos[$n]); }
    if (array_filter($codigos,fn($c)=>$c!==0)) throw new RuntimeException('Falha de trabalhador; consulte logs privados.');
    $medidos = []; $iniciais = []; $memoria = 0;
    foreach (glob("$dir/resultado-*.json") as $f) {
        $r = json_decode(file_get_contents($f),true,512,JSON_THROW_ON_ERROR);
        $memoria = max($memoria,$r['pico_memoria_bytes']);
        foreach ($r['tempos_ms'] as $nome=>$t) $medidos[$nome] = array_merge($medidos[$nome]??[],$t);
        foreach ($r['inicial_ms'] as $nome=>$t) $iniciais[$nome][] = $t;
    }
    $relatorio = ['conexoes_distintas_coexistentes'=>$abertas,'trabalhadores'=>20,'erros'=>0,
        'pico_memoria_por_processo_bytes'=>$memoria,'duracao_s'=>round(microtime(true)-$inicio,2),'casos'=>[]];
    $aprovado = $memoria<=128*1024*1024;
    foreach ($medidos as $nome=>$t) {
        sort($t); $p95 = $t[(int)ceil(count($t)*0.95)-1];
        $relatorio['casos'][$nome] = ['amostras'=>count($t),'p50_ms'=>round($t[(int)ceil(count($t)*0.5)-1],2),
            'p95_ms'=>round($p95,2),'max_ms'=>round(max($t),2),'limite_p95_ms'=>$limites[$nome],
            'primeira_passagem_max_ms'=>round(max($iniciais[$nome]),2),'meta_atendida'=>$p95<=$limites[$nome]];
        $aprovado = $aprovado && $p95 <= $limites[$nome];
    }
    $relatorio['aprovado'] = $aprovado;
    file_put_contents("$dir/relatorio.json",json_encode($relatorio,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
    echo json_encode($relatorio,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;
    exit($aprovado ? 0 : 2);
} finally {
    foreach ($processos as $p) { proc_terminate($p); proc_close($p); }
}
