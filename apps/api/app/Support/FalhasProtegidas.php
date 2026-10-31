<?php

namespace App\Support;

use App\Logging\ProtegerDados;
use Illuminate\Queue\Failed\DatabaseUuidFailedJobProvider;

class FalhasProtegidas extends DatabaseUuidFailedJobProvider
{
    public function log($connection, $queue, $payload, $exception)
    {
        return parent::log($connection, $queue, $payload,
            json_encode(ProtegerDados::excecao($exception), JSON_THROW_ON_ERROR));
    }
}
