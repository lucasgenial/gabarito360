<?php

namespace App\Enums;

enum TipoDeteccao: string
{
    case MARKED = 'marcada';
    case BLANK = 'branco';
    case MULTIPLE = 'dupla';
    case AMBIGUOUS = 'ambigua';
}
