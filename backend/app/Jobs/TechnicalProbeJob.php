<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class TechnicalProbeJob implements ShouldQueueAfterCommit
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(public readonly string $probeId)
    {
        if (! Str::isUuid($probeId)) {
            throw new InvalidArgumentException('The technical probe identifier must be a UUID.');
        }
    }

    public function handle(): void
    {
        Cache::put(
            self::cacheKey($this->probeId),
            [
                'probe_id' => $this->probeId,
                'processed_at' => now()->toIso8601String(),
            ],
            now()->addMinutes(5),
        );
    }

    public static function cacheKey(string $probeId): string
    {
        return "technical_probe:{$probeId}";
    }
}
