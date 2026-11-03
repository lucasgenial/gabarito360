<?php

namespace App\Support\Catalogo;

use Generator;
use SplTempFileObject;

/** Arquivo temporário público; memória limitada a2MiB, sem persistir caminho de origem. */
final class PacoteRelacoesCatalogo
{
    public readonly array $manifesto;

    public function __construct(private SplTempFileObject $arquivo, array $manifesto, private array $alertas)
    {
        $digest = hash_init('sha256');
        foreach ($this->linhas() as $r) hash_update($digest,self::json($r)."\n");
        $this->manifesto = array_merge($manifesto,['hash_relacoes'=>hash_final($digest),'alertas_grafo'=>count($alertas)]);
    }

    public function linhas(int $depois = 0): Generator
    {
        $this->arquivo->rewind();
        while (!$this->arquivo->eof()) {
            $linha = $this->arquivo->fgets();
            if (trim($linha) === '') continue;
            $r = json_decode($linha,true,512,JSON_THROW_ON_ERROR);
            $r['alerta'] = $r['tipo'] === 'sede_escolar' ? ($this->alertas[$r['origem_codigo']]??null) : null;
            if ($r['ordem'] > $depois) yield $r;
        }
    }

    public static function json(array $r): string { return json_encode($r,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR); }
}
