<?php

namespace App\Services\Omr;

use Illuminate\Support\Facades\Process;
use Illuminate\Validation\ValidationException;

class QualidadeCartao
{
    public function avaliar(string $caminho): array
    {
        try {
            $r = Process::path(config('omr.scripts'))->timeout(min(12, (int) config('omr.timeout', 60)))
                ->run([config('omr.python'), 'ler_cartao.py', '--qualidade', $caminho]);
        } catch (\Throwable) {
            abort(503, 'A verificação da foto está indisponível. A correção atual foi mantida. Tente novamente.');
        }
        $d = json_decode(trim($r->output()), true);
        abort_unless($r->successful() && is_array($d) && isset($d['aprovada'],$d['codigo'],$d['mensagem']), 503,
            'A verificação da foto está indisponível. A correção atual foi mantida. Tente o envio novamente.');

        return $d;
    }

    public function exigir(string $caminho): array
    {
        $d = $this->avaliar($caminho);
        if (! $d['aprovada']) {
            throw ValidationException::withMessages(['imagem' => $d['mensagem']]);
        }

        return $d;
    }
}
