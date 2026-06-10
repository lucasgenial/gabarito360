<?php

namespace App\Enums;

enum UserStatus: string
{
    case ACTIVE = 'ativo';
    case INACTIVE = 'inativo';
    case BLOCKED = 'bloqueado';
}
