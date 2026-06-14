<?php

namespace App\Models;

use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    protected $table = 'usuarios';

    /** @var list<string> */
    protected $fillable = [
        'nome',
        'email',
        'documento',
        'telefone',
        'foto_arquivo_id',
        'password',
        'status',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function perfis(): BelongsToMany
    {
        return $this->belongsToMany(Perfil::class, 'usuario_perfis', 'usuario_id', 'perfil_id')
            ->withPivot([
                'id',
                'nucleo_id',
                'escola_id',
                'concedido_por',
                'inicio_at',
                'fim_at',
                'created_at',
            ]);
    }

    public function perfilVinculos(): HasMany
    {
        return $this->hasMany(UsuarioPerfil::class, 'usuario_id');
    }

    public function perfisConcedidos(): HasMany
    {
        return $this->hasMany(UsuarioPerfil::class, 'concedido_por');
    }

    public function dispositivosMobile(): HasMany
    {
        return $this->hasMany(DispositivoMobile::class, 'usuario_id');
    }

    public function turmasVinculadas(): HasMany
    {
        return $this->hasMany(AplicadorTurma::class, 'usuario_id');
    }

    public function vinculosTurmaConcedidos(): HasMany
    {
        return $this->hasMany(AplicadorTurma::class, 'vinculado_por');
    }

    public function importacoesAlunosSolicitadas(): HasMany
    {
        return $this->hasMany(ImportacaoAluno::class, 'solicitado_por');
    }

    public function importacoesAlunosConfirmadas(): HasMany
    {
        return $this->hasMany(ImportacaoAluno::class, 'confirmado_por');
    }

    public function modelosCartaoCriados(): HasMany
    {
        return $this->hasMany(ModeloCartao::class, 'criado_por');
    }

    public function modelosCartaoHomologados(): HasMany
    {
        return $this->hasMany(ModeloCartao::class, 'homologado_por');
    }

    public function provasCriadas(): HasMany
    {
        return $this->hasMany(Prova::class, 'criado_por');
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(Auditoria::class, 'usuario_id');
    }

    public function fotoArquivo(): BelongsTo
    {
        return $this->belongsTo(Arquivo::class, 'foto_arquivo_id');
    }

    public function lotacoes(): HasMany
    {
        return $this->hasMany(UsuarioLotacao::class, 'usuario_id');
    }

    public function disciplinasVinculadas(): HasMany
    {
        return $this->hasMany(UsuarioDisciplina::class, 'usuario_id');
    }

    public function preferencia(): HasOne
    {
        return $this->hasOne(PreferenciaUsuario::class, 'usuario_id');
    }

    public function preferenciasNotificacao(): HasMany
    {
        return $this->hasMany(PreferenciaNotificacao::class, 'usuario_id');
    }

    public function relatoriosSolicitados(): HasMany
    {
        return $this->hasMany(Relatorio::class, 'solicitante_id');
    }

    public function sessoes(): HasMany
    {
        return $this->hasMany(SessaoUsuario::class, 'usuario_id');
    }

    public function historicosAcesso(): HasMany
    {
        return $this->hasMany(HistoricoAcesso::class, 'usuario_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => UserStatus::class,
            'email_verified_at' => 'immutable_datetime',
            'ultimo_acesso_at' => 'immutable_datetime',
            'password' => 'hashed',
        ];
    }
}
