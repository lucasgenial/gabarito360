<?php

namespace App\Enums;

enum LeituraCartaoStatus: string
{
    case RECEIVED = 'recebida';
    case UNDER_REVIEW = 'em_revisao';
    case REVIEWED = 'revisada';
    case CONFIRMED = 'confirmada';
    case CANCELLED = 'cancelada';
    case FAILED = 'falhou';
}
