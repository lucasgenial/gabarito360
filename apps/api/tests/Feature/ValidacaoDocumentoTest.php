<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\EmissaoDocumento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ValidacaoDocumentoTest extends TestCase
{
    use RefreshDatabase;

    private function emitir(): array
    {
        $u = Usuario::forceCreate(['nome'=>'Emissor privado fictício','email'=>'emitente@example.test','cpf'=>'00000000001','password'=>'teste','perfil'=>'coordenador','ativo'=>true]);
        $service = app(EmissaoDocumento::class);
        $d = $service->preparar($u, 'professor', 'Escola fictícia', null);
        $file = tempnam(sys_get_temp_dir(), 'g360_test_emissao_');
        try { file_put_contents($file, '%PDF-ficticio privado'); $service->registrar($d, $file); }
        finally { unlink($file); }
        return $d;
    }

    public function test_validacao_publica_distingue_registro_de_integridade_sem_expor_dados_privados(): void
    {
        $d = $this->emitir(); $codigo = $d['pdf']['codigo'];
        $resp = $this->postJson('/api/v1/documentos/validar', ['codigo'=>$codigo]);
        $resp->assertOk()->assertJsonPath('data.estado', 'emissao_encontrada')->assertHeader('Cache-Control','no-store, private');
        $this->assertStringNotContainsString('Emissor privado', $resp->getContent());
        $this->assertStringNotContainsString('emissor_id', $resp->getContent());
        $this->assertStringNotContainsString('arquivo_sha256', $resp->getContent());
        $this->postJson('/api/v1/documentos/validar', ['codigo'=>strtolower($codigo), 'sha256'=>hash('sha256','%PDF-ficticio privado')])->assertOk()->assertJsonPath('data.estado','arquivo_confirmado');
        $this->postJson('/api/v1/documentos/validar', ['codigo'=>$codigo,'sha256'=>str_repeat('0',64)])->assertOk()->assertJsonPath('data.estado','arquivo_divergente');
        $this->postJson('/api/v1/documentos/validar', ['codigo'=>'G360-'.str_repeat('0',32)])->assertOk()->assertJsonPath('data.estado','nao_encontrado');
        $this->postJson('/api/v1/documentos/validar', ['codigo'=>'1','sha256'=>'arquivo'])->assertUnprocessable();
    }

    public function test_rotacao_com_chave_anterior_preserva_selos_sem_confiar_em_outra_chave(): void
    {
        $d = $this->emitir(); $antiga = config('app.key');
        config(['app.key'=>'base64:'.base64_encode(random_bytes(32)), 'app.previous_keys'=>[$antiga]]);
        $this->postJson('/api/v1/documentos/validar', ['codigo'=>$d['pdf']['codigo']])->assertOk();
        config(['app.previous_keys'=>[]]);
        $this->postJson('/api/v1/documentos/validar', ['codigo'=>$d['pdf']['codigo']])->assertStatus(503);
    }

    public function test_registro_de_emissao_nao_pode_ser_alterado(): void
    {
        $d = $this->emitir();
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('documento_emissoes')->where('id', $d['registro']['id'])->update(['quantidade'=>9]);
    }
}
