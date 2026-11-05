<?php

namespace App\Console\Commands;

use App\Models\Cartao;
use App\Models\Prova;
use App\Services\Omr\HistoricoCartao;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PreservarCartoes extends Command
{
    protected $signature = 'g360:preservar-cartoes {--executar : Preservar evidências existentes sem recalcular respostas ou notas}';

    protected $description = 'Confere e preserva a base inicial do histórico de cartões; padrão é somente conferência.';

    public function handle(HistoricoCartao $h): int
    {
        $ids = Cartao::whereNull('tentativa_id')->whereIn('status', ['lido', 'ambiguo'])->orderBy('id')->pluck('id');
        foreach ($ids as $id) {
            $c = Cartao::findOrFail($id);
            if ($c->imagem_url && ! Storage::disk('public')->exists($c->imagem_url)) {
                $this->error('Há uma foto indisponível. Nenhum novo registro foi iniciado; restaure os arquivos antes de continuar.');

                return self::FAILURE;
            }
        }
        if (! $this->option('executar')) {
            $this->info('Cartões conferidos: '.$ids->count().'. Nenhuma resposta, nota, imagem ou registro foi alterado.');

            return self::SUCCESS;
        }
        $total = 0;
        foreach ($ids as $id) {
            DB::transaction(function () use ($id, $h, &$total) {
                $c = Cartao::find($id);
                if (! $c) {
                    return;
                }Prova::whereKey($c->prova_id)->lockForUpdate()->firstOrFail();
                $c = Cartao::whereKey($id)->lockForUpdate()->first();
                if (! $c || $c->tentativa_id) {
                    return;
                }$h->registrarAtual($c, null, 'registro_anterior');
                $total++;
            }, 3);
        }
        $this->info('Cartões preservados: '.$total.'. Leitura e notas não foram recalculadas.');

        return self::SUCCESS;
    }
}
