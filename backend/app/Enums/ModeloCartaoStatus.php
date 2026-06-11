<?php

namespace App\Enums;

enum ModeloCartaoStatus: string
{
    case DRAFT = 'rascunho';
    case APPROVED = 'homologado';
    case INACTIVE = 'inativo';
}
