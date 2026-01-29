<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Model;

use Magento\Framework\Model\AbstractModel;

class SyncLog extends AbstractModel
{
    protected $_eventPrefix = 'grupoawamotos_erp_sync_log';

    protected function _construct(): void
    {
        $this->_init(ResourceModel\SyncLog::class);
    }

    public static function log(
        \Magento\Framework\ObjectManagerInterface $om,
        string $entityType,
        string $direction,
        string $status,
        string $message = '',
        ?string $erpCode = null,
        ?int $magentoId = null,
        ?int $recordsProcessed = null
    ): void {
        /** @var self $log */
        $log = $om->create(self::class);
        $log->setData([
            'entity_type' => $entityType,
            'direction' => $direction,
            'status' => $status,
            'message' => $message,
            'erp_code' => $erpCode,
            'magento_id' => $magentoId,
            'records_processed' => $recordsProcessed,
        ]);
        $log->save();
    }
}
