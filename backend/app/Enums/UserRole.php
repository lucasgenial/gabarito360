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
}
