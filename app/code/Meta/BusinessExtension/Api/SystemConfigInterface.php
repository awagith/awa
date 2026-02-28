<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Api;

/**
 * Interface for Meta Business Extension system configuration
 */
interface SystemConfigInterface
{
    /**
     * Check if the extension is active
     */
    public function isActive(?int $storeId = null): bool;

    /**
     * Get the Meta Pixel ID
     */
    public function getPixelId(?int $storeId = null): ?string;

    /**
     * Get the Conversions API access token (decrypted)
     */
    public function getAccessToken(?int $storeId = null): ?string;

    /**
     * Get the Graph API version
     */
    public function getApiVersion(?int $storeId = null): string;

    /**
     * Get the Meta Commerce Catalog ID
     */
    public function getCatalogId(?int $storeId = null): ?string;

    /**
     * Get the Commerce Account ID
     */
    public function getCommerceAccountId(?int $storeId = null): ?string;

    /**
     * Get the Business Manager ID
     */
    public function getBusinessManagerId(?int $storeId = null): ?string;

    /**
     * Get the External Business ID
     */
    public function getExternalBusinessId(?int $storeId = null): ?string;

    /**
     * Get the Page ID
     */
    public function getPageId(?int $storeId = null): ?string;

    /**
     * Get the out of stock threshold
     */
    public function getOutOfStockThreshold(?int $storeId = null): int;

    /**
     * Check if debug logging is enabled
     */
    public function isDebugEnabled(?int $storeId = null): bool;
}
