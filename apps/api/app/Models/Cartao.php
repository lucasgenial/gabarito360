<?php

namespace App\Models;

use App\Services\Omr\ImagensCartao;
use Illuminate\Database\Eloquent\Model;

class Cartao extends Model
{
    protected $table = 'cartoes';

    protected $fillable = [
        'prova_id', 'aluno_id', 'lingua', 'imagem_url', 'status',
        'confianca_geral', 'resolvido_por', 'resolvido_em', 'revisado_por', 'revisado_em',
    ];

    protected static function booted(): void
    {
        static::deleted(fn (Cartao $cartao) => \Illuminate\Support\Facades\DB::afterCommit(fn () => app(ImagensCartao::class)->apagar($cartao->id, $cartao->espaco_id)));
    }

    public function prova()
    {
        return $this->belongsTo(Prova::class, 'prova_id');
    }

    public function aluno()
    {
        return $this->belongsTo(Aluno::class, 'aluno_id');
    }
}
