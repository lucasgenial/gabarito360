<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class DispositivoMobile extends Model
{
    use HasUuids;

    protected $table = 'dispositivos_mobile';

    /** @var list<string> */
    protected $fillable = [
        'usuario_id',
        'identificador',
        'plataforma',
        'modelo_dispositivo',
        'versao_sistema',
        'versao_app',
        'ultimo_acesso_at',
        'revogado_at',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(PersonalAccessToken::class, 'dispositivo_mobile_id');
    }

    public function revoke(): void
    {
        DB::transaction(function (): void {
            $this->forceFill(['revogado_at' => now()])->save();
            $this->tokens()->delete();
        });
    }

    public function isRevoked(): bool
    {
        return $this->revogado_at !== null;
    }

    public function supportsCurrentAppVersion(): bool
    {
        return version_compare(
            $this->versao_app,
            (string) config('gabarito360.mobile.minimum_app_version'),
            '>=',
        );
    }

    public function markAccessed(): void
    {
        if ($this->ultimo_acesso_at === null || $this->ultimo_acesso_at->lt(now()->subMinutes(15))) {
            $this->forceFill(['ultimo_acesso_at' => now()])->save();
        }
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'ultimo_acesso_at' => 'immutable_datetime',
            'revogado_at' => 'immutable_datetime',
        ];
    }
}
