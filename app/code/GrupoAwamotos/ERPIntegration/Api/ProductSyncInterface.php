<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Api;

interface ProductSyncInterface
{
    public function syncAll(): array;
    public function syncBySku(string $sku): bool;
    public function getErpProducts(int $limit = 0, int $offset = 0): array;
}
