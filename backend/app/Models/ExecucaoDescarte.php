<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecucaoDescarte extends Model
{
    use HasUuids;

    protected $table = 'execucoes_descarte';

    protected $guarded = [];

    public function politicaRetencao(): BelongsTo
    {
        return $this->belongsTo(PoliticaRetencao::class, 'politica_retencao_id');
    }

    public function solicitacaoLgpd(): BelongsTo
    {
        return $this->belongsTo(SolicitacaoLgpd::class, 'solicitacao_lgpd_id');
    }

    public function executadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executado_por_id');
    }

    protected function casts(): array
    {
        return [
            'afetados' => 'integer',
            'detalhes' => 'array',
            'executado_at' => 'immutable_datetime',
        ];
    }
}
