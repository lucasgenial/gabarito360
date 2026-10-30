<?php

namespace App\Console\Commands;

use App\Support\CatalogoPermissoes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Arquivo: apps/api/app/Console/Commands/SincronizarPermissoes.php
 * Função: Grava o catálogo de permissões (docs/03 v2) nas tabelas `permissoes` e
 *         `perfil_permissoes`. Idempotente: rode após mudar App\Support\CatalogoPermissoes.
 *         Não mexe nas extras/retiradas de usuários.
 */
class SincronizarPermissoes extends Command
{
    protected $signature = 'g360:permissoes-sincronizar';

    protected $description = 'Sincroniza o catálogo de permissões e o padrão/extra de cada perfil';

    public function handle(): int
    {
        DB::transaction(function () {
            $agora = now();
            foreach (CatalogoPermissoes::LISTA as [$chave, $modulo, $descricao]) {
                DB::table('permissoes')->updateOrInsert(
                    ['chave' => $chave],
                    ['modulo' => $modulo, 'descricao' => $descricao, 'updated_at' => $agora, 'created_at' => $agora]
                );
            }
            DB::table('permissoes')->whereNotIn('chave', array_column(CatalogoPermissoes::LISTA, 0))->delete();

            DB::table('perfil_permissoes')->delete();
            $linhas = [];
            foreach (CatalogoPermissoes::porPerfil() as $perfil => $grupos) {
                foreach (['padrao', 'extra'] as $tipo) {
                    foreach ($grupos[$tipo] as $chave) {
                        $linhas[] = ['perfil' => $perfil, 'permissao' => $chave, 'tipo' => $tipo];
                    }
                }
            }
            DB::table('perfil_permissoes')->insert($linhas);
        });

        $this->info(count(CatalogoPermissoes::LISTA) . ' permissões sincronizadas.');

        return self::SUCCESS;
    }
}
