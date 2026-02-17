<?php
declare(strict_types=1);

namespace GrupoAwamotos\Fitment\Cron;

use Psr\Log\LoggerInterface;

/**
 * Cron job para reconstrução completa do índice FULLTEXT fallback (diário 03:15).
 *
 * Executa como sub-processo para evitar que exit() do script mate o cron scheduler.
 */
class FallbackRebuild
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute(): void
    {
        $script = BP . '/scripts/fallback_search_rebuild.php';

        if (!file_exists($script)) {
            $this->logger->error('[Fitment] Script não encontrado: ' . $script);
            return;
        }

        $phpBin = PHP_BINARY ?: '/usr/bin/php';
        $cmd = escapeshellarg($phpBin) . ' ' . escapeshellarg($script) . ' --truncate 2>&1';

        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);

        $outputStr = implode("\n", $output);

        if ($exitCode !== 0) {
            $this->logger->error(
                '[Fitment] Fallback rebuild falhou (exit ' . $exitCode . '): ' . $outputStr
            );
        } else {
            $this->logger->info('[Fitment] Fallback rebuild OK: ' . $outputStr);
        }
    }
}
