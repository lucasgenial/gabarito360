<?php
namespace Tests\Unit;

use App\Support\Catalogo\PoliticaEdicaoCatalogo;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PoliticaEdicaoCatalogoTest extends TestCase
{
    public function test_edicao_antiga_recusada_mesmo_com_pedido_de_substituicao(): void
    {
        $this->expectException(RuntimeException::class); $this->expectExceptionMessage('anterior à vigente');
        PoliticaEdicaoCatalogo::conferir(2024,(object)['id'=>'atual','ano'=>2025],'atual','Motivo fictício.');
    }
    public function test_mesmo_ano_exige_alvo_exato_e_motivo(): void
    {
        $this->expectException(RuntimeException::class);
        PoliticaEdicaoCatalogo::conferir(2025,(object)['id'=>'atual','ano'=>2025],'atual',' ');
    }
    public function test_primeira_ou_nova_edicao_e_revisao_explicita_sao_admitidas(): void
    {
        PoliticaEdicaoCatalogo::conferir(2025,null,null,null);
        PoliticaEdicaoCatalogo::conferir(2026,(object)['id'=>'atual','ano'=>2025],null,null);
        PoliticaEdicaoCatalogo::conferir(2025,(object)['id'=>'atual','ano'=>2025],'atual','Revisão conferida.');
        $this->addToAssertionCount(3);
    }
}
