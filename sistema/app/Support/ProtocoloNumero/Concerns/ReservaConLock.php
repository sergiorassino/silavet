<?php

namespace App\Support\ProtocoloNumero\Concerns;

use Illuminate\Support\Facades\DB;
use RuntimeException;

trait ReservaConLock
{
    private const LOCK_TIMEOUT = 15;

    protected function withLock(string $lockKey, callable $callback): mixed
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return DB::transaction($callback);
        }

        $lockName = 'vl_protocolo_'.$lockKey;
        $result = DB::selectOne('SELECT GET_LOCK(?, ?) AS acquired', [$lockName, self::LOCK_TIMEOUT]);

        if (! $result || (int) ($result->acquired ?? 0) !== 1) {
            throw new RuntimeException('No se pudo reservar el número de protocolo. Intente nuevamente.');
        }

        try {
            return $callback();
        } finally {
            DB::selectOne('SELECT RELEASE_LOCK(?)', [$lockName]);
        }
    }
}
