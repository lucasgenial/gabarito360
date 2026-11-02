<?php
namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\{Crypt, DB};
use RuntimeException;

/** Cópia cifrada; não remove, altera ou importa eventos. Manifesto é publicado por último. */
final class ArquivoMensalAuditoria
{
    private function validarMes(string $mes): void
    {
        if (!preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])$/D', $mes)
            || $mes >= CarbonImmutable::now('UTC')->format('Y-m')) throw new RuntimeException();
    }

    public function criar(string $mes, string $destino): array
    {
        $this->validarMes($mes);
        // mkdir exclusivo: nunca aceitar diretório preexistente, mesmo se vazio ou symlink.
        if (file_exists($destino) || is_link($destino) || !mkdir($destino, 0700)) throw new RuntimeException();
        $arquivo = $destino.'/eventos.enc';
        $saida = fopen($arquivo, 'xb');
        if (!$saida) throw new RuntimeException();
        chmod($arquivo, 0600);
        $hash = hash_init('sha256'); $quantidade = 0;
        try {
            DB::transaction(function () use ($mes, $saida, $hash, &$quantidade) {
                foreach (DB::table('auditoria_eventos')->select(ConferenciaAuditoria::CAMPOS)
                    ->where('mes', $mes)->orderBy('id')->cursor() as $evento) {
                    if (substr($evento->ocorrido_em, 0, 7) !== $mes) throw new RuntimeException();
                    $linha = ConferenciaAuditoria::linhaCanonica($evento);
                    $cifrada = Crypt::encryptString($linha)."\n";
                    if (fwrite($saida, $cifrada) !== strlen($cifrada)) throw new RuntimeException();
                    hash_update($hash, $linha); $quantidade++;
                }
            });
            if (!fflush($saida)) throw new RuntimeException();
        } finally { fclose($saida); }
        $manifesto = ['versao'=>1, 'mes'=>$mes, 'quantidade'=>$quantidade,
            'sha256'=>hash_final($hash), 'arquivo_sha256'=>hash_file('sha256', $arquivo)];
        $json = json_encode($manifesto, JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT);
        $saida = fopen($destino.'/manifesto.json', 'xb');
        if (!$saida) throw new RuntimeException();
        chmod($destino.'/manifesto.json', 0600);
        try {
            if (fwrite($saida, $json) !== strlen($json) || !fflush($saida)) throw new RuntimeException();
        } finally { fclose($saida); }
        return $this->verificar($mes, $destino);
    }

    public function verificar(string $mes, string $destino): array
    {
        $this->validarMes($mes);
        $caminho = $destino.'/manifesto.json';
        if (!is_file($caminho) || filesize($caminho) > 4096) throw new RuntimeException();
        $m = json_decode(file_get_contents($caminho), true, 16, JSON_THROW_ON_ERROR);
        if (($m['versao'] ?? null) !== 1 || ($m['mes'] ?? null) !== $mes
            || !is_int($m['quantidade'] ?? null) || $m['quantidade'] < 0) throw new RuntimeException();
        foreach (['sha256','arquivo_sha256'] as $campo) {
            if (!is_string($m[$campo] ?? null) || !preg_match('/^[a-f0-9]{64}$/D', $m[$campo])) throw new RuntimeException();
        }
        $entrada = fopen($destino.'/eventos.enc', 'rb');
        if (!$entrada) throw new RuntimeException();
        $hash = hash_init('sha256'); $cifra = hash_init('sha256'); $quantidade = 0; $anterior = null;
        try {
            while (($cifrada = fgets($entrada, 1048577)) !== false) {
                if (!str_ends_with($cifrada, "\n")) throw new RuntimeException();
                $linha = Crypt::decryptString(substr($cifrada, 0, -1));
                $evento = json_decode($linha, true, 32, JSON_THROW_ON_ERROR);
                if (($evento['mes'] ?? null) !== $mes || !is_string($evento['id'] ?? null)
                    || !preg_match('/^[a-f0-9-]{36}$/D', $evento['id'])
                    || ($anterior !== null && strcmp($anterior, $evento['id']) >= 0)) throw new RuntimeException();
                $anterior = $evento['id'];
                hash_update($hash, $linha); hash_update($cifra, $cifrada); $quantidade++;
            }
            if (!feof($entrada)) throw new RuntimeException();
        } finally { fclose($entrada); }
        if ($quantidade !== $m['quantidade'] || !hash_equals($m['sha256'], hash_final($hash))
            || !hash_equals($m['arquivo_sha256'], hash_final($cifra))) throw new RuntimeException();
        return $m;
    }
}
