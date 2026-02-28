<?php

declare(strict_types=1);

namespace Meta\Promotions\Observer;

use JsonException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Meta\BusinessExtension\Api\SystemConfigInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Psr\Log\LoggerInterface;

/**
 * Observer to sync sales rule to Meta when saved
 */
class SalesRuleSave implements ObserverInterface
{
    private const DISCOUNT_TYPE_MAP = [
        'by_percent' => 'PERCENTAGE',
        'by_fixed' => 'FIXED_AMOUNT',
        'cart_fixed' => 'FIXED_AMOUNT',
        'buy_x_get_y' => 'BUY_X_GET_Y',
    ];

    public function __construct(
        private readonly SystemConfigInterface $config,
        private readonly FBEHelper $fbeHelper,
        private readonly LoggerInterface $logger,
        private readonly ?StoreManagerInterface $storeManager = null
    ) {
    }

    public function execute(Observer $observer): void
    {
        try {
            $rule = $observer->getEvent()->getData('rule');
            if (!$rule) {
                return;
            }

            $ruleId = (int) $rule->getRuleId();
            if ($ruleId <= 0) {
                return;
            }

            $storeId = $this->resolveRuleStoreId($rule);
            if (!$this->config->isActive($storeId)) {
                return;
            }

            $commerceAccountId = $this->config->getCommerceAccountId($storeId);
            if ($commerceAccountId === null) {
                return;
            }

            $simpleAction = $rule->getSimpleAction();
            $discountType = self::DISCOUNT_TYPE_MAP[$simpleAction] ?? 'PERCENTAGE';
            $ruleName = trim((string) $rule->getName());
            if ($ruleName === '') {
                $ruleName = 'Promoção ' . $ruleId;
            }
            $description = trim((string) ($rule->getDescription() ?: $ruleName));

            $promotionData = [
                'id' => 'promo_' . $ruleId,
                'title' => $ruleName,
                'description' => $description,
                'discount_type' => $discountType,
                'discount_amount' => (float) $rule->getDiscountAmount(),
                'start_date' => $rule->getFromDate() ?: date('Y-m-d'),
                'status' => $rule->getIsActive() ? 'ACTIVE' : 'PAUSED'
            ];

            $toDate = $rule->getToDate();
            if ($toDate) {
                $promotionData['end_date'] = $toDate;
            }

            $couponCode = $rule->getCouponCode();
            if ($couponCode) {
                $promotionData['coupon_code'] = $couponCode;
            }

            $endpoint = $commerceAccountId . '/promotions';
            $result = $this->fbeHelper->apiPost($endpoint, [
                'data' => json_encode(
                    [$promotionData],
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                )
            ], $storeId);
            if (isset($result['error'])) {
                $this->logger->warning('[Meta Promotions] Rule sync API error', [
                    'store_id' => $storeId,
                    'rule_id' => $ruleId,
                    'http_status' => $result['http_status'] ?? null,
                    'error' => $result['error']
                ]);
            }

            $this->logger->info('[Meta Promotions] Rule synced', [
                'store_id' => $storeId,
                'rule_id' => $ruleId,
                'name' => $ruleName
            ]);
        } catch (JsonException $e) {
            $this->logger->error('[Meta Promotions] SalesRuleSave payload encode failed', [
                'error' => $e->getMessage()
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[Meta Promotions] SalesRuleSave failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function resolveRuleStoreId(object $rule): ?int
    {
        if ($this->storeManager === null) {
            return null;
        }

        try {
            $websiteIds = $rule->getWebsiteIds();
            if (is_array($websiteIds)) {
                foreach ($websiteIds as $websiteId) {
                    $websiteId = (int) $websiteId;
                    if ($websiteId <= 0) {
                        continue;
                    }

                    $website = $this->storeManager->getWebsite($websiteId);
                    $defaultStore = $website->getDefaultStore();
                    if ($defaultStore) {
                        $storeId = (int) $defaultStore->getId();
                        if ($storeId > 0) {
                            return $storeId;
                        }
                    }
                }
            }

            $currentStoreId = (int) $this->storeManager->getStore()->getId();

            return $currentStoreId > 0 ? $currentStoreId : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
