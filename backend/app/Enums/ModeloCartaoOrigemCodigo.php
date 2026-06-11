<?php

namespace App\Enums;

enum ModeloCartaoOrigemCodigo: string
{
    case NONE = 'nenhum';
    case EXTERNAL = 'externo';
    case AFFIXED_SYSTEM = 'sistema_afixado';
}
