<?php

namespace App\Enums;

enum IntegracaoStatus: string
{
    case CONNECTED = 'conectada';
    case PENDING = 'pendente';
    case ERROR = 'erro';
    case DISCONNECTED = 'desconectada';
}
