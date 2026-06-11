<?php

namespace App\Enums;

enum ProvaStatus: string
{
    case DRAFT = 'rascunho';
    case PUBLISHED = 'publicada';
    case FINISHED = 'finalizada';
    case ARCHIVED = 'arquivada';
}
