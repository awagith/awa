<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Cron;

use GrupoAwamotos\ERPIntegration\Model\ResourceModel\SyncLog as SyncLogResource;
use GrupoAwamotos\ERPIntegration\Helper\Data as Helper;
use Psr\Log\LoggerInterface;

class CleanSyncLogs
{
    /**
     * Default days to keep logs
     */
    private const DEFAULT_DAYS_TO_KEEP = 30;

    private SyncLogResource $syncLogResource;
    private Helper $helper;
    private LoggerInterface $logger;

    public function __construct(
        SyncLogResource $syncLogResource,
        Helper $helper,
        LoggerInterface $logger
    ) {
        $this->syncLogResource = $syncLogResource;
        $this->helper = $helper;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        if (!$this->helper->isEnabled()) {
            return;
        }

        try {
            $daysToKeep = self::DEFAULT_DAYS_TO_KEEP;
            $deleted = $this->syncLogResource->cleanOldLogs($daysToKeep);

            if ($deleted > 0) {
                $this->logger->info('[ERP] Sync logs cleanup completed', [
                    'deleted_records' => $deleted,
                    'days_kept' => $daysToKeep,
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Sync logs cleanup failed: ' . $e->getMessage());
        }
    }
}
