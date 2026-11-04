<?php

namespace App\Services;

use App\Support\DocumentoIdentidade;
use Illuminate\Support\Facades\DB;

/** Sem rota HTTP. Um resultado declarado nunca autoriza leitura, vínculo ou fusão. */
final class ConsultaIdentidadeInterna
{
    public function porCpf(string $cpf): ?array
    {
        $indice = DocumentoIdentidade::indiceCpf($cpf);
        $r = DB::table('pessoa_documento_indices as i')
            ->join('pessoa_documentos as d', 'd.id', '=', 'i.documento_id')
            ->join('pessoas as p', 'p.id', '=', 'd.pessoa_id')
            ->where('i.tipo', 'CPF')->where('i.pais', 'BR')
            ->where('i.versao', DocumentoIdentidade::VERSAO_INDICE)->where('i.indice', $indice)
            ->first(['p.uuid', 'd.situacao']);
        return $r ? ['pessoa_referencia' => $r->uuid, 'documento_situacao' => $r->situacao] : null;
    }
}
