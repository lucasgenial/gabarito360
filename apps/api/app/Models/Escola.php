<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Escola extends Model
{
    protected $table = 'escolas';

    protected $fillable = [
        'nucleo_id', 'nome', 'inep', 'tipo_rede', 'tipo', 'escola_sede_id',
        'situacao_funcionamento', 'localizacao', 'orgao_regional',
        'logradouro', 'numero', 'complemento', 'bairro', 'cep', 'cidade', 'co_municipio', 'uf',
        'latitude', 'longitude', 'telefone', 'email', 'turnos', 'etapas', 'modalidades',
        'origem', 'ano_censo', 'edicao_manual', 'ativo',
    ];

    protected $casts = [
        'email' => \App\Casts\DadoProtegido::class,
        'telefone' => \App\Casts\DadoProtegido::class,

        'ativo' => 'boolean', 'edicao_manual' => 'boolean',
        'turnos' => 'array', 'etapas' => 'array', 'modalidades' => 'array',
    ];

    /** Oferta (listas fixas, mesmas opções no cadastro e na exibição). */
    public const TURNOS      = ['manha' => 'Manhã', 'tarde' => 'Tarde', 'noite' => 'Noite', 'integral' => 'Integral'];
    public const ETAPAS      = ['infantil' => 'Educação Infantil', 'fundamental_iniciais' => 'Fundamental — anos iniciais',
                                'fundamental_finais' => 'Fundamental — anos finais', 'medio' => 'Ensino Médio',
                                'eja' => 'EJA', 'profissional' => 'Educação Profissional'];
    public const MODALIDADES = ['regular' => 'Ensino regular', 'especial' => 'Educação especial',
                                'eja' => 'Educação de Jovens e Adultos', 'profissional' => 'Educação profissional',
                                'campo' => 'Educação do campo', 'indigena' => 'Educação indígena', 'quilombola' => 'Educação quilombola'];

    public function sede()
    {
        return $this->belongsTo(Escola::class, 'escola_sede_id');
    }

    public function anexos()
    {
        return $this->hasMany(Escola::class, 'escola_sede_id');
    }

    public function nucleo()
    {
        return $this->belongsTo(Nucleo::class, 'nucleo_id');
    }

    public function turmas()
    {
        return $this->hasMany(Turma::class, 'escola_id');
    }

    public function provas()
    {
        return $this->hasMany(Prova::class, 'escola_id');
    }
}
