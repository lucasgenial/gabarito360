<?php

namespace Tests\Feature;

use App\Services\Omr\NativoOmrDriver;
use App\Services\Omr\QualidadeCartao;
use Illuminate\Support\Facades\Process;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class QualidadeCartaoTest extends TestCase
{
    public function test_validador_invoca_python_e_nao_aceita_retorno_tecnico_invalido(): void
    {
        $this->app->forgetInstance(QualidadeCartao::class);
        Process::fake(['*' => Process::result(output: 'resposta técnica inválida')]);
        try {
            app(QualidadeCartao::class)->exigir('/foto-ficticia.jpg');
            $this->fail('Deveria recusar');
        } catch (HttpException $e) {
            $this->assertSame(503, $e->getStatusCode());
        }
        Process::assertRan(fn ($p) => in_array('--qualidade', $p->command, true));
    }

    public function test_foto_cortada_retornada_pelo_python_e_erro_de_campo(): void
    {
        $this->app->forgetInstance(QualidadeCartao::class);
        Process::fake(['*' => Process::result(output: json_encode(['aprovada' => false, 'codigo' => 'marcador_cortado', 'mensagem' => 'Inclua os quatro quadrados.']))]);
        $this->expectException(ValidationException::class);
        app(QualidadeCartao::class)->exigir('/foto-ficticia.jpg');
    }

    public function test_qualidade_valida_e_retornada_para_registro_do_cartao(): void
    {
        $this->app->forgetInstance(QualidadeCartao::class);
        Process::fake(['*' => Process::result(output: json_encode(['aprovada' => true, 'codigo' => 'adequada', 'mensagem' => 'Foto adequada.', 'versao' => 'omr-qualidade-1']))]);
        $this->assertSame('omr-qualidade-1', app(QualidadeCartao::class)->exigir('/foto-ficticia.jpg')['versao']);
    }

    public function test_driver_propaga_recusa_geometrica_em_vez_de_branco_confiavel(): void
    {
        Process::fake(['*' => Process::result(output: json_encode(['ok' => false, 'erro' => 'Marcadores cortados', 'qualidade' => ['aprovada' => false, 'codigo' => 'marcadores_incompletos', 'mensagem' => 'Inclua toda a folha.']]))]);
        $driver = new NativoOmrDriver;
        $r = $driver->processar('/foto-ficticia.jpg', 3, 5);
        $this->assertFalse($driver->identificacao()['qualidade']['aprovada']);
        $this->assertCount(3, $r);
        $this->assertTrue(collect($r)->every(fn ($q) => $q['ambigua'] && $q['confianca'] === 0.0));
    }
}
