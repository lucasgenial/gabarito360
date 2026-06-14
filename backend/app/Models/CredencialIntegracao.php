<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CredencialIntegracao extends Model
{
    use HasUuids;

    protected $table = 'credenciais_integracoes';

    /** @var list<string> */
    protected $fillable = [
        'integracao_id',
        'chave',
        'valor_criptografado',
    ];

    public function integracao(): BelongsTo
    {
        return $this->belongsTo(Integracao::class, 'integracao_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'valor_criptografado' => 'encrypted',
        ];
    }
}
