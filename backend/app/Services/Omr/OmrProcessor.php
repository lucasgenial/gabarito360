<?php

namespace App\Services\Omr;

use RuntimeException;
use Symfony\Component\Process\Process;

class OmrProcessor
{
    /** @return array<string, mixed> */
    public function process(string $imagePath): array
    {
        $root = realpath(base_path('..'));
        $config = realpath((string) config('gabarito360.omr.config_path'));

        if ($root === false || $config === false || ! is_file($imagePath)) {
            throw new RuntimeException('OMR_INPUT_NOT_AVAILABLE');
        }

        $process = new Process([
            (string) config('gabarito360.omr.python_binary'),
            '-m',
            'omr.process',
            '--image',
            $imagePath,
            '--config',
            $config,
        ], $root);
        $process->setTimeout((int) config('gabarito360.omr.timeout_seconds'));
        $process->mustRun();
        $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($result) || ! isset($result['responses'], $result['model'])) {
            throw new RuntimeException('OMR_INVALID_OUTPUT');
        }

        return $result;
    }
}
