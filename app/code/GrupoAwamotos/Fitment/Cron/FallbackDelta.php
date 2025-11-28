<?php
declare(strict_types=1);

namespace GrupoAwamotos\Fitment\Cron;

use Psr\Log\LoggerInterface;

class FallbackDelta
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
            // Executa o delta diretamente
            require BP . '/scripts/fallback_search_delta.php';
        } catch (\Throwable $e) {
            $this->logger->error('[Fitment] Fallback delta falhou: ' . $e->getMessage());
        }
    }
}
