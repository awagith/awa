<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Api;

interface OrderSyncInterface
{
    public function sendOrder(\Magento\Sales\Api\Data\OrderInterface $order): array;
    public function getOrderHistory(int $erpClientCode, int $limit = 50): array;
}
