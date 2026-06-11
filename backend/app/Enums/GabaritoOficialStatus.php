<?php

namespace App\Enums;

enum GabaritoOficialStatus: string
{
    case DRAFT = 'rascunho';
    case CURRENT = 'vigente';
    case REPLACED = 'substituido';
}
