<?php

namespace App\Logging;

use Illuminate\Log\LogManager;
use Illuminate\Log\Logger;

/** Protege inclusive canais sob demanda e a saída de emergência. */
class GerenciadorProtegido extends LogManager
{
    protected function tap($name, Logger $logger)
    {
        $logger = parent::tap($name, $logger);
        (new ProtegerDados)($logger);
        return $logger;
    }

    protected function createEmergencyLogger()
    {
        $logger = parent::createEmergencyLogger();
        (new ProtegerDados)($logger);
        return $logger;
    }
}
