<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Model\System;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Meta\BusinessExtension\Api\SystemConfigInterface;

/**
 * System configuration for Meta Business Extension
 */
class Config implements SystemConfigInterface
{
    private const DEFAULT_API_VERSION = 'v18.0';
    private const XML_PATH_ACTIVE = 'facebook_business_extension/general/active';
    private const XML_PATH_PIXEL_ID = 'facebook_business_extension/general/pixel_id';
    private const XML_PATH_ACCESS_TOKEN = 'facebook_business_extension/general/access_token';
    private const XML_PATH_API_VERSION = 'facebook_business_extension/general/api_version';
    private const XML_PATH_CATALOG_ID = 'facebook_business_extension/catalog/catalog_id';
    private const XML_PATH_OUT_OF_STOCK_THRESHOLD = 'facebook_business_extension/catalog/out_of_stock_threshold';
    private const XML_PATH_BM_ID = 'facebook_business_extension/business/business_manager_id';
    private const XML_PATH_EXTERNAL_BIZ_ID = 'facebook_business_extension/business/external_business_id';
    private const XML_PATH_COMMERCE_ACCOUNT_ID = 'facebook_business_extension/business/commerce_account_id';
    private const XML_PATH_PAGE_ID = 'facebook_business_extension/business/page_id';
    private const XML_PATH_DEBUG = 'facebook_business_extension/debug/enabled';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor
    ) {
    }

    public function isActive(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ACTIVE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getPixelId(?int $storeId = null): ?string
    {
        return $this->normalizeIdentifier($this->getStringValue(self::XML_PATH_PIXEL_ID, $storeId));
    }

    public function getAccessToken(?int $storeId = null): ?string
    {
        $value = $this->getStringValue(self::XML_PATH_ACCESS_TOKEN, $storeId);

        if ($value === null || $value === '') {
            return null;
        }

        try {
            $decrypted = trim((string) $this->encryptor->decrypt($value));
        } catch (\Throwable) {
            return null;
        }

        return $decrypted !== '' ? $decrypted : null;
    }

    public function getApiVersion(?int $storeId = null): string
    {
        $value = $this->getStringValue(self::XML_PATH_API_VERSION, $storeId);

        if ($value === null) {
            return self::DEFAULT_API_VERSION;
        }

        $normalized = strtolower(trim($value));

        if ($normalized !== '' && $normalized[0] !== 'v') {
            $normalized = 'v' . $normalized;
        }

        if (preg_match('/^v\d+(?:\.\d+)?$/', $normalized) !== 1) {
            return self::DEFAULT_API_VERSION;
        }

        return $normalized;
    }

    public function getCatalogId(?int $storeId = null): ?string
    {
        return $this->normalizeIdentifier($this->getStringValue(self::XML_PATH_CATALOG_ID, $storeId));
    }

    public function getCommerceAccountId(?int $storeId = null): ?string
    {
        return $this->normalizeIdentifier($this->getStringValue(self::XML_PATH_COMMERCE_ACCOUNT_ID, $storeId));
    }

    public function getBusinessManagerId(?int $storeId = null): ?string
    {
        return $this->normalizeIdentifier($this->getStringValue(self::XML_PATH_BM_ID, $storeId));
    }

    public function getExternalBusinessId(?int $storeId = null): ?string
    {
        $value = $this->getStringValue(self::XML_PATH_EXTERNAL_BIZ_ID, $storeId);
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\s+/', '', $value);

        return is_string($normalized) && $normalized !== '' ? $normalized : null;
    }

    public function getPageId(?int $storeId = null): ?string
    {
        return $this->normalizeIdentifier($this->getStringValue(self::XML_PATH_PAGE_ID, $storeId));
    }

    public function getOutOfStockThreshold(?int $storeId = null): int
    {
        $value = (int) $this->scopeConfig->getValue(
            self::XML_PATH_OUT_OF_STOCK_THRESHOLD,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return max(0, $value);
    }

    public function isDebugEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_DEBUG,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    private function getStringValue(string $path, ?int $storeId = null): ?string
    {
        $value = $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeIdentifier(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\s+/', '', $value);

        return $normalized !== null && $normalized !== '' ? $normalized : null;
    }
}
