<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Api;

interface StockSyncInterface
{
    public function getStockBySku(string $sku): ?array;
    public function syncAll(): array;
}
