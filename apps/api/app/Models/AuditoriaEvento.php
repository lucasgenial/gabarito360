<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/** Escrita exclusiva do job; não há operações de manutenção expostas pela aplicação. */
class AuditoriaEvento extends Model
{
    protected $table = 'auditoria_eventos';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = ['*'];
    protected $casts = ['referencias'=>'array', 'ocorrido_em'=>'immutable_datetime'];

    protected static function booted(): void
    {
        static::saving(fn () => throw new LogicException('Registro de auditoria somente para leitura.'));
        static::deleting(fn () => throw new LogicException('Registro de auditoria somente para leitura.'));
    }
}
