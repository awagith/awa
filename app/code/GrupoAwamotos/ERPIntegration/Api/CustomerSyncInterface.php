<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Api;

interface CustomerSyncInterface
{
    public function syncAll(): array;
    public function getErpCustomerByTaxvat(string $taxvat): ?array;
    public function getErpCustomerByCode(int $code): ?array;
}
