<?php

namespace App\Enums;

enum AplicadorTurmaPapel: string
{
    case TEACHER = 'professor';
    case APPLICATOR = 'aplicador';
    case RESPONSIBLE = 'responsavel';

    public function requiredRole(): UserRole
    {
        return match ($this) {
            self::TEACHER => UserRole::TEACHER,
            self::APPLICATOR => UserRole::APPLICATOR,
            self::RESPONSIBLE => UserRole::SCHOOL_MANAGER,
        };
    }
}
