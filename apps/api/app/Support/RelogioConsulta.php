<?php

namespace App\Support;

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

/** Relógio avaliado pelo banco em cada execução, inclusive após joinSub/toSql. */
final class RelogioConsulta
{
    public static function agora(): Expression
    {
        return DB::raw(DB::connection()->getDriverName() === 'sqlite' ? 'CURRENT_TIMESTAMP' : 'UTC_TIMESTAMP()');
    }

    /** Dia corrente em São Paulo (-03:00 desde 2019), sem converter datas históricas. */
    public static function hojeSaoPaulo(): Expression
    {
        return DB::raw(DB::connection()->getDriverName() === 'sqlite'
            ? "date(CURRENT_TIMESTAMP, '-3 hours')"
            : 'DATE(UTC_TIMESTAMP() - INTERVAL 3 HOUR)');
    }

    public static function hojeUtc(): Expression
    {
        return DB::raw(DB::connection()->getDriverName() === 'sqlite' ? 'date(CURRENT_TIMESTAMP)' : 'DATE(UTC_TIMESTAMP())');
    }
}
