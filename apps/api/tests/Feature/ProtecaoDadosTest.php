<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Mail\EmailModelo;
use App\Services\ContaUsuario;
use App\Support\DadoProtegido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ProtecaoDadosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Infraestrutura Laravel da fila, já presente na cópia MariaDB de produção.
        \Illuminate\Support\Facades\Schema::create('jobs', function ($t) {
            $t->bigIncrements('id'); $t->string('queue'); $t->longText('payload');
            $t->unsignedTinyInteger('attempts'); $t->unsignedInteger('reserved_at')->nullable();
            $t->unsignedInteger('available_at'); $t->unsignedInteger('created_at');
        });
        \Illuminate\Support\Facades\Schema::create('failed_jobs', function ($t) {
            $t->id(); $t->string('uuid')->unique(); $t->text('connection'); $t->text('queue');
            $t->longText('payload'); $t->longText('exception'); $t->timestamp('failed_at');
        });
    }

    private function usuario(): Usuario
    {
        return Usuario::create(['nome' => 'Pessoa fictícia', 'perfil' => 'professor',
            'email' => 'pessoa@example.test', 'cpf' => '00000000123', 'telefone' => '11999990000',
            'password' => 'senha-ficticia', 'ativo' => true]);
    }

    public function test_campos_cifrados_busca_login_unicidade_e_alteracao(): void
    {
        $u = $this->usuario();
        $raw = DB::table('usuarios')->find($u->id);
        foreach (['email', 'cpf', 'telefone'] as $campo) {
            $this->assertStringStartsWith('g360:v1:', $raw->$campo);
            $this->assertSame($u->$campo, Crypt::decryptString(substr($raw->$campo, 8)));
            $this->assertStringNotContainsString($u->$campo, $raw->$campo);
        }
        $this->assertSame($u->id, Usuario::porEmail('PESSOA@example.test')->sole()->id);
        $this->assertArrayNotHasKey('email_hash', $u->toArray());
        $this->assertArrayNotHasKey('cpf_hash', $u->toArray());
        $this->postJson('/api/v1/auth/login', ['email' => 'PESSOA@example.test', 'password' => 'senha-ficticia'])
            ->assertOk()->assertJsonPath('data.email', 'pessoa@example.test');
        $this->assertTrue(Validator::make(['email' => 'PESSOA@example.test'], [
            'email' => new \App\Rules\UnicoProtegido('usuarios', 'email'),
        ])->fails());
        $this->assertFalse(Validator::make(['email' => $u->email], [
            'email' => new \App\Rules\UnicoProtegido('usuarios', 'email', $u->id),
        ])->fails());
        $u->update(['email' => 'outro@example.test', 'telefone' => null]);
        $this->assertFalse(Usuario::porEmail('pessoa@example.test')->exists());
        $this->assertSame($u->id, Usuario::porEmail('outro@example.test')->sole()->id);
        $this->assertNull($u->fresh()->telefone);
    }

    public function test_migracao_converte_registro_legado_e_link_antigo_e_pode_retomar(): void
    {
        $u = $this->usuario();
        DB::table('usuarios')->where('id', $u->id)->update([
            'email' => $u->email, 'cpf' => $u->cpf, 'telefone' => $u->telefone,
            'email_hash' => null, 'cpf_hash' => null,
        ]);
        DB::table('password_reset_tokens')->insert(['email' => $u->email,
            'token' => bcrypt('token-legado'), 'created_at' => now()]);
        $migration = require database_path('migrations/2026_10_31_000004_proteger_cpf_e_contatos.php');
        $migration->up();
        $cifrado = DB::table('usuarios')->where('id', $u->id)->value('email');
        $migration->up();
        $this->assertSame($cifrado, DB::table('usuarios')->where('id', $u->id)->value('email'));
        $this->assertSame($u->email, $u->fresh()->email);
        Mail::fake();
        $this->postJson('/api/v1/auth/redefinir-senha', ['email' => $u->email, 'token' => 'token-legado',
            'password' => 'nova-senha-ficticia', 'password_confirmation' => 'nova-senha-ficticia'])->assertOk();
    }

    public function test_link_novo_sem_email_e_job_cifrado_restauravel_com_a_chave(): void
    {
        Mail::fake();
        $u = $this->usuario();
        app(ContaUsuario::class)->enviarLinkDeSenha($u);
        $mail = Mail::queued(EmailModelo::class)->sole();
        $this->assertStringNotContainsString('pessoa', $mail->dados['link']);
        parse_str(parse_url($mail->dados['link'], PHP_URL_QUERY), $link);
        $this->assertArrayNotHasKey('email', $link);
        $this->postJson('/api/v1/auth/redefinir-senha', ['email' => $link['conta'], 'token' => $link['token'],
            'password' => 'nova-senha-ficticia', 'password_confirmation' => 'nova-senha-ficticia'])->assertOk();
        $queue = app('queue')->connection('database');
        $id = $queue->push(new \Illuminate\Mail\SendQueuedMailable($mail), '', 'padrao');
        $payload = DB::table('jobs')->where('id', $id)->value('payload');
        $this->assertStringNotContainsString($u->email, $payload);
        $this->assertStringNotContainsString($link['token'], $payload);
        $command = json_decode($payload, true)['data']['command'];
        $restaurado = unserialize(Crypt::decrypt($command));
        $this->assertSame($mail->dados['link'], $restaurado->mailable->dados['link']);
        $incorreta = new \Illuminate\Encryption\Encrypter(str_repeat('y', 32), 'AES-256-CBC');
        try {
            $incorreta->decrypt($command);
            $this->fail('Uma chave diferente não pode abrir o backup.');
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            $this->assertTrue(true);
        }
    }

    public function test_falha_da_fila_guarda_diagnostico_sem_corpo_ou_argumentos(): void
    {
        $u = $this->usuario();
        $mail = (new EmailModelo('recuperar-senha', 'Assunto', ['nome' => 'Pessoa fictícia']))->to($u->email);
        $id = app('queue')->connection('database')->push(new \Illuminate\Mail\SendQueuedMailable($mail), '', 'padrao');
        $payload = DB::table('jobs')->where('id', $id)->value('payload');
        app('queue.failer')->log('database', 'padrao', $payload, new \RuntimeException('SMTP '.$u->email.' senha-secreta'));
        $row = DB::table('failed_jobs')->sole();
        $this->assertStringNotContainsString($u->email, $row->exception.$row->payload);
        $this->assertStringNotContainsString('senha-secreta', $row->exception);
        $this->assertStringContainsString('RuntimeException', $row->exception);
    }

    public function test_contatos_escolares_e_cpf_de_aluno_sao_cifrados_sem_mudar_a_resposta(): void
    {
        $rede = DB::table('redes')->insertGetId(['nome' => 'Rede fictícia', 'tipo' => 'estadual', 'uf' => 'BA']);
        $nucleo = DB::table('nucleos')->insertGetId(['rede_id' => $rede, 'nome' => 'Núcleo fictício']);
        $escola = \App\Models\Escola::create(['nucleo_id' => $nucleo, 'nome' => 'Escola fictícia',
            'inep' => '29000001', 'tipo_rede' => 'estadual', 'email' => 'escola@example.test', 'telefone' => '11999990002']);
        $turma = DB::table('turmas')->insertGetId(['escola_id' => $escola->id, 'nome' => '3A',
            'serie' => '3ª série', 'turno' => 'tarde', 'ano_letivo' => 2026]);
        $aluno = \App\Models\Aluno::create(['turma_id' => $turma, 'nome' => 'Aluno fictício',
            'matricula' => 'L3-FICTICIO', 'cpf' => '00000000321', 'ativo' => true]);
        $lead = \App\Models\Lead::create(['nome' => 'Contato fictício', 'email' => 'contato@example.test',
            'telefone' => '11999990003', 'mensagem' => 'Teste']);
        foreach ([$escola, $aluno, $lead] as $model) {
            $campos = $model instanceof \App\Models\Aluno ? ['cpf'] : ['email', 'telefone'];
            foreach ($campos as $campo) {
                $raw = DB::table($model->getTable())->where('id', $model->id)->value($campo);
                $this->assertStringStartsWith('g360:v1:', $raw);
                $this->assertSame($model->$campo, $model->fresh()->$campo);
            }
        }
        $this->assertArrayNotHasKey('cpf_hash', $aluno->fresh()->toArray());
        $this->assertTrue(Validator::make(['cpf' => '00000000321'], [
            'cpf' => new \App\Rules\UnicoProtegido('alunos', 'cpf'),
        ])->fails());
        $aluno->update(['cpf' => null]);
        $this->assertNull(DB::table('alunos')->where('id', $aluno->id)->value('cpf_hash'));
    }

    public function test_banco_impede_duplicidade_mesmo_com_cifras_aleatorias(): void
    {
        $this->usuario();
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        Usuario::create(['nome' => 'Outra pessoa fictícia', 'perfil' => 'professor',
            'email' => 'PESSOA@example.test', 'cpf' => '00000000456', 'password' => 'senha-ficticia', 'ativo' => true]);
    }

    public function test_cifra_adulterada_nao_e_aceita_como_texto_simples(): void
    {
        $u = $this->usuario();
        DB::table('usuarios')->where('id', $u->id)->update(['telefone' => 'telefone-em-claro']);
        $this->expectException(\Illuminate\Contracts\Encryption\DecryptException::class);
        $u->fresh()->telefone;
    }

    public function test_migracao_impede_reversao_silenciosa_para_texto_simples(): void
    {
        $migration = require database_path('migrations/2026_10_31_000004_proteger_cpf_e_contatos.php');
        $this->expectException(\RuntimeException::class);
        $migration->down();
    }
}
