<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Api;

/**
 * REST API for ERP to PULL orders from Magento
 *
 * Endpoints:
 * - GET  /V1/erp/orders/pending          List orders not yet synced to ERP
 * - GET  /V1/erp/orders/:incrementId     Get full order details with ERP data
 * - POST /V1/erp/orders/:incrementId/ack ERP acknowledges receipt
 */
interface OrderPullInterface
{
    /**
     * Get list of orders pending sync to ERP.
     *
     * @param int $limit Maximum number of orders to return
     * @param string|null $fromDate Optional ISO date filter (orders after this date)
     * @return mixed[]
     */
    public function getPendingOrders(int $limit = 50, ?string $fromDate = null): array;

    /**
     * Get full order details with ERP-enriched data.
     *
     * @param string $incrementId Magento order increment ID
     * @return mixed[]
     */
    public function getOrderDetails(string $incrementId): array;

    /**
     * ERP acknowledges receipt of an order.
     *
     * @param string $incrementId Magento order increment ID
     * @param string $erpOrderId The ID assigned by the ERP
     * @param string|null $message Optional message from ERP
     * @return mixed[]
     */
    public function acknowledgeOrder(
        string $incrementId,
        string $erpOrderId,
        ?string $message = null
    ): array;
}
