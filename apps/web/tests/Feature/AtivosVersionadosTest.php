<?php

namespace Tests\Feature;

use App\Support\Ativos;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class AtivosVersionadosTest extends TestCase
{
    private string $arquivo;

    protected function setUp(): void
    {
        parent::setUp();
        Ativos::limpar();
        $this->arquivo = public_path('js/teste-ativo-f3.js');
        file_put_contents($this->arquivo, 'console.log(1);');
    }

    protected function tearDown(): void
    {
        @unlink($this->arquivo);
        Ativos::limpar();
        parent::tearDown();
    }

    public function test_versao_vem_do_conteudo_e_nao_da_data(): void
    {
        $esperada = substr(hash('xxh128', 'console.log(1);'), 0, 16);
        $this->assertSame($esperada, Ativos::versao('js/teste-ativo-f3.js'));
        $this->assertSame(asset('js/teste-ativo-f3.js').'?v='.$esperada, Ativos::url('/js/teste-ativo-f3.js'));

        touch($this->arquivo, time() + 3600);
        Ativos::limpar();
        $this->assertSame($esperada, Ativos::versao('js/teste-ativo-f3.js'));

        file_put_contents($this->arquivo, 'console.log(2);');
        Ativos::limpar();
        $this->assertNotSame($esperada, Ativos::versao('js/teste-ativo-f3.js'));
    }

    public function test_arquivo_ausente_ou_fora_do_public_nao_recebe_versao(): void
    {
        $this->assertNull(Ativos::versao('js/nao-existe-f3.js'));
        $this->assertSame(asset('js/nao-existe-f3.js'), Ativos::url('js/nao-existe-f3.js'));
        $this->assertNull(Ativos::versao('../.env'));
    }

    public function test_diretiva_blade_escapa_e_versiona(): void
    {
        $html = Blade::render("<script src=\"@ativo('js/teste-ativo-f3.js')\"></script>");
        $this->assertMatchesRegularExpression('#src="[^"]*/js/teste-ativo-f3\.js\?v=[a-f0-9]{16}"#', $html);
    }

    public function test_views_nao_referenciam_css_ou_js_local_sem_versao(): void
    {
        $sem = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))) as $f) {
            if (! str_ends_with($f->getFilename(), '.blade.php')) {
                continue;
            }
            $conteudo = file_get_contents($f->getPathname());
            if (preg_match_all("#asset\\('[^']+\\.(?:css|js)'\\)|filemtime\\(#", $conteudo, $m)) {
                $sem[] = $f->getFilename().': '.implode(', ', $m[0]);
            }
        }
        $this->assertSame([], $sem);
    }
}
