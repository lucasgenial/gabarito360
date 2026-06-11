<?php

namespace App\Enums;

enum StudentImportStatus: string
{
    case VALIDATED = 'validada';
    case HAS_ERRORS = 'com_erros';
    case PROCESSING = 'processando';
    case COMPLETED = 'concluida';
    case FAILED = 'falhou';
}
