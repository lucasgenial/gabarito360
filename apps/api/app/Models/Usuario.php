<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'usuarios';

    private ?int $vinculoContexto = null;
    private ?int $acessoInternoContexto = null;
    private ?int $acessoInternoVersao = null;

    /** Contexto explícito; a versão será transportada no token na próxima parte de A1. */
    public function noAcessoInterno(int $id, int $versao): static
    {
        $contexto = clone $this;
        $contexto->unsetRelations();
        $contexto->vinculoContexto = null;
        $contexto->acessoInternoContexto = $id;
        $contexto->acessoInternoVersao = $versao;
        \App\Support\ContextoInterno::resolver($contexto);
        return $contexto;
    }

    public function acessoInternoContextoId(): ?int { return $this->acessoInternoContexto; }
    public function acessoInternoContextoVersao(): ?int { return $this->acessoInternoVersao; }

    /** Cópia de leitura para um vínculo validado; não muda a conta nem outros contextos. */
    public function noVinculo(int $id): static
    {
        $contexto = clone $this;
        $contexto->unsetRelations();
        $contexto->acessoInternoContexto = null;
        $contexto->acessoInternoVersao = null;
        $contexto->vinculoContexto = $id;
        \App\Support\ContextoVinculo::resolver($contexto);
        return $contexto;
    }

    public function vinculoContextoId(): ?int
    {
        return $this->vinculoContexto;
    }

    public function fresh($with = [])
    {
        $atual = parent::fresh($with);
        if ($atual && $this->acessoInternoContexto !== null) return $atual->noAcessoInterno($this->acessoInternoContexto, $this->acessoInternoVersao);
        return $atual && $this->vinculoContexto !== null ? $atual->noVinculo($this->vinculoContexto) : $atual;
    }

    /** SerializesModels só guarda o ID da conta e perderia o vínculo. */
    public function getQueueableId()
    {
        if ($this->vinculoContexto !== null || $this->acessoInternoContexto !== null) {
            throw new \LogicException('Transporte a identidade e o contexto explicitamente e revalide no consumidor.');
        }
        return parent::getQueueableId();
    }

    public function getPerfilAttribute($perfil): ?string
    {
        if ($this->acessoInternoContexto !== null) return \App\Support\ContextoInterno::resolver($this)->perfil;
        return $this->vinculoContexto === null ? $perfil : \App\Support\ContextoVinculo::resolver($this)->perfil;
    }

    protected $fillable = [
        'perfil',
        'nome',
        'email',
        'cpf',
        'password',
        'ativo',
        'escola_nome',
        'ultimo_acesso',
        'data_nascimento',
        'telefone',
        'data_ingresso',
        'formacao_academica',
        'especializacao',
        'registro_profissional',
        'observacoes',
        'forcar_troca_senha',
    ];

    protected $hidden = [
        'password',
        'remember_token', 'email_hash', 'cpf_hash',
    ];

    protected $casts = [
        'email' => \App\Casts\DadoProtegido::class . ':1',
        'cpf' => \App\Casts\DadoProtegido::class . ':1',
        'telefone' => \App\Casts\DadoProtegido::class,

        'ativo'               => 'boolean',
        'password'            => 'hashed',
        'ultimo_acesso'       => 'datetime',
        'data_nascimento'     => 'date',
        'data_ingresso'       => 'date',
        'forcar_troca_senha'  => 'boolean',
    ];

    public function scopePorEmail($query, string $email)
    {
        return $query->where('email_hash', \App\Support\DadoProtegido::hash($email, 'email'));
    }

    public function escopos()
    {
        return $this->hasMany(UsuarioEscopo::class, 'usuario_id');
    }

    public function vinculos()
    {
        return $this->hasMany(UsuarioVinculo::class, 'usuario_id');
    }
}
