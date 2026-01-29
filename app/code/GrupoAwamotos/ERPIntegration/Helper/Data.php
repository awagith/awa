<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;

class Data extends AbstractHelper
{
    private const XML_PREFIX = 'grupoawamotos_erp/';

    private EncryptorInterface $encryptor;

    public function __construct(
        Context $context,
        EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
        $this->encryptor = $encryptor;
    }

    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PREFIX . 'connection/enabled',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getHost(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PREFIX . 'connection/host',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getPort(): int
    {
        return (int) ($this->scopeConfig->getValue(
            self::XML_PREFIX . 'connection/port',
            ScopeInterface::SCOPE_STORE
        ) ?: 1433);
    }

    public function getDatabase(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PREFIX . 'connection/database',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getUsername(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PREFIX . 'connection/username',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getPassword(): string
    {
        $value = (string) $this->scopeConfig->getValue(
            self::XML_PREFIX . 'connection/password',
            ScopeInterface::SCOPE_STORE
        );
        return $value ? $this->encryptor->decrypt($value) : '';
    }

    public function getDriver(): string
    {
        return (string) ($this->scopeConfig->getValue(
            self::XML_PREFIX . 'connection/driver',
            ScopeInterface::SCOPE_STORE
        ) ?: 'auto');
    }

    public function getConnectionTimeout(): int
    {
        return (int) ($this->scopeConfig->getValue(
            self::XML_PREFIX . 'connection/timeout',
            ScopeInterface::SCOPE_STORE
        ) ?: 30);
    }

    public function getTrustServerCertificate(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PREFIX . 'connection/trust_certificate',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function isProductSyncEnabled(): bool
    {
        return $this->isEnabled() && $this->scopeConfig->isSetFlag(
            self::XML_PREFIX . 'sync_products/enabled',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductSyncFrequency(): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PREFIX . 'sync_products/frequency',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function filterComercializa(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PREFIX . 'sync_products/filter_comercializa',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function isStockSyncEnabled(): bool
    {
        return $this->isEnabled() && $this->scopeConfig->isSetFlag(
            self::XML_PREFIX . 'sync_stock/enabled',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function isStockRealtime(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PREFIX . 'sync_stock/realtime',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getStockFilial(): int
    {
        return (int) ($this->scopeConfig->getValue(
            self::XML_PREFIX . 'sync_stock/filial',
            ScopeInterface::SCOPE_STORE
        ) ?: 1);
    }

    public function getStockCacheTtl(): int
    {
        return (int) ($this->scopeConfig->getValue(
            self::XML_PREFIX . 'sync_stock/cache_ttl',
            ScopeInterface::SCOPE_STORE
        ) ?: 300);
    }

    public function isCustomerSyncEnabled(): bool
    {
        return $this->isEnabled() && $this->scopeConfig->isSetFlag(
            self::XML_PREFIX . 'sync_customers/enabled',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function isOrderSyncEnabled(): bool
    {
        return $this->isEnabled() && $this->scopeConfig->isSetFlag(
            self::XML_PREFIX . 'sync_orders/enabled',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function sendOrderOnPlace(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PREFIX . 'sync_orders/send_on_place',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function isPriceSyncEnabled(): bool
    {
        return $this->isEnabled() && $this->scopeConfig->isSetFlag(
            self::XML_PREFIX . 'sync_prices/enabled',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function isSuggestionsEnabled(): bool
    {
        return $this->isEnabled() && $this->scopeConfig->isSetFlag(
            self::XML_PREFIX . 'suggestions/enabled',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getMaxSuggestions(): int
    {
        return (int) ($this->scopeConfig->getValue(
            self::XML_PREFIX . 'suggestions/max_suggestions',
            ScopeInterface::SCOPE_STORE
        ) ?: 10);
    }
}
