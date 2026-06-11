<?php

namespace App\Enums;

enum MatriculaTurmaStatus: string
{
    case ACTIVE = 'ativa';
    case TRANSFERRED = 'transferida';
    case CLOSED = 'encerrada';
}
