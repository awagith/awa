<?php
declare(strict_types=1);

namespace GrupoAwamotos\Fitment\Cron;

use Psr\Log\LoggerInterface;

class FallbackRebuild
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute(): void
    {
        try {
            if (!defined('BP')) {
                define('BP', dirname(__DIR__, 4));
            }
            require BP . '/app/bootstrap.php';
            // Simula argv para rebuild com truncate diário
            $GLOBALS['argv'] = ['fallback_search_rebuild.php', '--truncate'];
            require BP . '/scripts/fallback_search_rebuild.php';
        } catch (\Throwable $e) {
            $this->logger->error('[Fitment] Fallback rebuild falhou: ' . $e->getMessage());
        }
    }
}
