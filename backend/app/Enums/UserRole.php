<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMINISTRATOR = 'administrador_geral';
    case EDUCATION_MANAGER = 'gestor_nucleo';
    case SCHOOL_MANAGER = 'responsavel_escola';
    case TEACHER = 'professor';
    case APPLICATOR = 'aplicador';
    case VIEWER = 'consulta';
    case TECHNICAL_SUPPORT = 'suporte_tecnico';

    public function label(): string
    {
        return match ($this) {
            self::ADMINISTRATOR => 'Administrador Geral',
            self::EDUCATION_MANAGER => 'Gestor do Nucleo',
            self::SCHOOL_MANAGER => 'Responsavel da Escola',
            self::TEACHER => 'Professor',
            self::APPLICATOR => 'Aplicador',
            self::VIEWER => 'Leitor/Consulta',
            self::TECHNICAL_SUPPORT => 'Suporte Tecnico',
        };
    }

    public function allowedScope(): AccessScope
    {
        return match ($this) {
            self::ADMINISTRATOR, self::TECHNICAL_SUPPORT => AccessScope::GLOBAL,
            self::EDUCATION_MANAGER => AccessScope::EDUCATION_CENTER,
            self::SCHOOL_MANAGER => AccessScope::SCHOOL,
            self::TEACHER, self::APPLICATOR, self::VIEWER => AccessScope::OPERATIONAL,
        };
    }
}
