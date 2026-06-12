<?php

namespace App\Enums;

enum AplicacaoStatus: string
{
    case DRAFT = 'rascunho';
    case SCHEDULED = 'agendada';
    case IN_PROGRESS = 'em_andamento';
    case FINISHED = 'finalizada';
    case CANCELLED = 'cancelada';
}
