<?php

namespace Tests\Feature\Infrastructure;

use App\Jobs\TechnicalProbeJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use Predis\Client;
use Tests\TestCase;

class QueueAndStorageTest extends TestCase
{
    public function test_redis_and_private_storage_are_configured_for_technical_dependencies(): void
    {
        $this->assertSame('redis', config('cache.stores.redis.driver'));
        $this->assertSame('cache', config('cache.stores.redis.connection'));
        $this->assertSame('redis', config('queue.connections.redis.driver'));
        $this->assertSame('queue', config('queue.connections.redis.connection'));
        $this->assertTrue(config('queue.connections.redis.after_commit'));
        $this->assertSame(5, config('queue.connections.redis.block_for'));
        $this->assertSame('predis', config('database.redis.client'));
        $this->assertTrue(class_exists(Client::class));
        $this->assertNotContains('*', config('reverb.apps.apps.0.allowed_origins'));

        $this->assertSame('private', config('filesystems.private'));
        $this->assertSame('private', config('filesystems.disks.private.visibility'));
        $this->assertFalse(config('filesystems.disks.private.serve'));
        $this->assertNotContains(
            config('filesystems.disks.private.root'),
            config('filesystems.links'),
        );
        $this->assertSame('s3', config('filesystems.disks.s3_private.driver'));
        $this->assertSame('private', config('filesystems.disks.s3_private.visibility'));
        $this->assertArrayNotHasKey('url', config('filesystems.disks.s3_private'));
        $this->assertTrue(class_exists(AwsS3V3Adapter::class));
    }

    public function test_technical_job_can_be_queued(): void
    {
        Queue::fake();

        $probeId = (string) Str::uuid();

        TechnicalProbeJob::dispatch($probeId);

        Queue::assertPushed(
            TechnicalProbeJob::class,
            fn (TechnicalProbeJob $job): bool => $job->probeId === $probeId,
        );
    }

    public function test_technical_job_can_be_processed(): void
    {
        config([
            'cache.default' => 'array',
            'queue.default' => 'sync',
        ]);

        $probeId = (string) Str::uuid();
        $cacheKey = TechnicalProbeJob::cacheKey($probeId);

        $this->assertFalse(Cache::has($cacheKey));

        Bus::dispatch(new TechnicalProbeJob($probeId));

        $this->assertSame($probeId, Cache::get($cacheKey)['probe_id'] ?? null);
        $this->assertNotNull(Cache::get($cacheKey)['processed_at'] ?? null);
    }

    public function test_private_file_can_be_written_read_and_removed(): void
    {
        $disk = config('filesystems.private');
        Storage::fake($disk);

        $path = 'technical-probes/'.Str::uuid().'.txt';
        $contents = 'gabarito360-private-storage-probe';

        $this->assertTrue(Storage::disk($disk)->put($path, $contents));
        Storage::disk($disk)->assertExists($path);
        $this->assertSame($contents, Storage::disk($disk)->get($path));

        $this->assertTrue(Storage::disk($disk)->delete($path));
        Storage::disk($disk)->assertMissing($path);
    }
}
