<?php

namespace App\Console\Commands;

use App\Services\PreencherEspacosLegados;
use App\Support\EstruturaEspacos;
use App\Support\Instituicoes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ConciliarAdminEspacos extends Command
{
    protected $signature = 'g360:conciliar-admin-espacos {--usuario=} {--manifesto=} {--aplicar}';
    protected $description = 'Simula as duas atribuições aprovadas do único administrador legado; sem nomes ou contatos.';

    public function handle(PreencherEspacosLegados $inventario): int
    {
        $aplicar = (bool) $this->option('aplicar');
        $usuario = filter_var($this->option('usuario'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$usuario || $this->option('manifesto') !== EstruturaEspacos::VERSAO) {
            $this->error('Informe o ID técnico da conta revisada e o manifesto aprovado.');
            return self::FAILURE;
        }
        $lock = Cache::lock('g360:t2:preenchimento', 3600);
        try {
            if (!$lock->get()) throw new RuntimeException('Operação T2 em andamento.');
            if ($aplicar && !app()->environment('testing') && !app()->isDownForMaintenance()) throw new RuntimeException('Manutenção necessária.');
            DB::transaction(function () use ($usuario, $aplicar, $inventario) {
                $inventario->executar(false);
                $admins = DB::table('usuarios')->where('perfil', 'admin_rede')->lockForUpdate()->get(['id', 'ativo']);
                if ($admins->count() !== 1 || (int) $admins[0]->id !== $usuario || !$admins[0]->ativo) throw new RuntimeException('Conta ambígua.');
                if (Instituicoes::redesAtivas()->whereIn('r.id', [1, 2])->count() !== 2) throw new RuntimeException('Preenchimento não concluído.');
                foreach ([1, 2] as $rede) {
                    if (!Instituicoes::redesAtivas()->join('clientes as c', 'c.id', '=', 'er.cliente_id')
                        ->where('r.id', $rede)->where('c.chave_origem', 'LEGADO-REDE-'.$rede)
                        ->where('c.tipo', 'rede')->where('c.modalidade', 'interno')->where('c.situacao', 'ativo')->exists()) {
                        throw new RuntimeException('Cliente divergente do manifesto.');
                    }
                }
                $escopos = DB::table('usuario_escopos')->where('usuario_id', $usuario)->lockForUpdate()->get();
                foreach ($escopos as $e) {
                    if ($e->escopo_tipo !== 'rede' || !in_array((int) $e->escopo_id, [1, 2], true)) throw new RuntimeException('Acesso divergente.');
                }
                if ($escopos->count() !== $escopos->unique('escopo_id')->count()) throw new RuntimeException('Atribuição duplicada.');
                if ($aplicar) {
                    foreach ([1, 2] as $rede) {
                        if (!$escopos->contains(fn ($e) => (int) $e->escopo_id === $rede)) {
                            DB::table('usuario_escopos')->insert(['usuario_id' => $usuario, 'escopo_tipo' => 'rede', 'escopo_id' => $rede, 'created_at' => now()]);
                        }
                    }
                }
            });
            $this->line(json_encode(['manifesto' => EstruturaEspacos::VERSAO, 'usuario_id' => $usuario, 'redes' => [1, 2], 'aplicado' => $aplicar], JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Conciliação recusada. Confira conta única, manifesto, espaços, escopos existentes e manutenção. Nenhuma escrita parcial foi confirmada.');
            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }
}
