<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Api;

interface PriceSyncInterface
{
    public function syncAll(): array;
    public function getPriceBySku(string $sku): ?array;
}
