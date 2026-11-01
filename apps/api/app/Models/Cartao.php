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
        static::saving(function (Cartao $cartao) {
            if (!$cartao->isDirty('aluno_id') || !\App\Support\Espacos::ativo()) return;
            if (!\Illuminate\Support\Facades\DB::table('espacos')->where('id', $cartao->espaco_id)->where('tipo', 'pessoal')->exists()) return;
            $cartao->turma_aplicacao_id = $cartao->aluno_id
                ? \Illuminate\Support\Facades\DB::table('alunos')->where('id', $cartao->aluno_id)->where('espaco_id', $cartao->espaco_id)->lockForUpdate()->value('turma_id') : null;
            abort_if($cartao->aluno_id && !$cartao->turma_aplicacao_id, 409, 'Aluno fora do espaço do cartão.');
        });
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
