<?php

use App\Support\DadoProtegido;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Valida as chaves antes do primeiro DDL. MariaDB não reverte DDL.
        DadoProtegido::hash('verificacao', 'email');
        Crypt::decryptString(Crypt::encryptString('verificacao'));
        $campos = [
            'usuarios' => ['email', 'cpf', 'telefone'],
            'alunos' => ['cpf'],
            'leads' => ['email', 'telefone'],
            'escolas' => ['email', 'telefone'],
            'trocas_email' => ['novo_email'],
        ];
        $indices = ['usuarios' => ['email', 'cpf'], 'alunos' => ['cpf']];
        foreach ($indices as $tabela => $lista) {
            foreach ($lista as $campo) {
                if (!Schema::hasColumn($tabela, $campo.'_hash')) {
                    Schema::table($tabela, fn (Blueprint $t) => $t->char($campo.'_hash', 64)->nullable()->unique());
                }
                $nome = $tabela.'_'.$campo.'_unique';
                if (Schema::hasIndex($tabela, $nome)) {
                    Schema::table($tabela, fn (Blueprint $t) => $t->dropUnique($nome));
                }
            }
        }
        foreach ($campos as $tabela => $lista) {
            Schema::table($tabela, function (Blueprint $t) use ($lista, $tabela) {
                foreach ($lista as $campo) {
                    $obrigatorio = ($tabela === 'usuarios' && $campo !== 'telefone')
                        || ($tabela === 'leads' && $campo === 'email') || $tabela === 'trocas_email';
                    $t->text($campo)->nullable(!$obrigatorio)->change();
                }
            });
            DB::table($tabela)->orderBy('id')->chunkById(200, function ($rows) use ($tabela, $lista, $indices) {
                DB::transaction(function () use ($rows, $tabela, $lista, $indices) {
                    foreach ($rows as $row) {
                        $dados = [];
                        foreach ($lista as $campo) {
                            $valor = $row->$campo;
                            if ($valor !== null) {
                                if (str_starts_with($valor, 'g360:v1:')) {
                                    $valor = Crypt::decryptString(substr($valor, 8));
                                } else {
                                    $dados[$campo] = 'g360:v1:'.Crypt::encryptString($valor);
                                }
                            }
                            if (in_array($campo, $indices[$tabela] ?? [], true)) {
                                $dados[$campo.'_hash'] = DadoProtegido::hash($valor, $campo);
                            }
                        }
                        if ($dados) DB::table($tabela)->where('id', $row->id)->update($dados);
                    }
                });
            });
        }
        // A coluna é apenas a chave do registro de recuperação, não precisa ser reversível.
        DB::transaction(function () {
            foreach (DB::table('password_reset_tokens')->get(['email']) as $row) {
                if (str_contains($row->email, '@')) {
                    DB::table('password_reset_tokens')->where('email', $row->email)
                        ->update(['email' => DadoProtegido::hash($row->email, 'email')]);
                }
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Dados protegidos: reversão exige restauração validada do backup e das chaves; não descriptografar automaticamente.');
    }
};
