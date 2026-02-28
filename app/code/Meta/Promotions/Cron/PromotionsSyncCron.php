<?php

declare(strict_types=1);

namespace Meta\Promotions\Cron;

use JsonException;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Meta\BusinessExtension\Api\SystemConfigInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Psr\Log\LoggerInterface;

/**
 * Cron job to sync active promotions/coupons to Meta Commerce (daily 3am)
 */
class PromotionsSyncCron
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
        private readonly CollectionFactory $collectionFactory,
        private readonly LoggerInterface $logger,
        private readonly ?StoreManagerInterface $storeManager = null
    ) {
    }

    public function execute(): void
    {
        $storeId = $this->resolveCronStoreId();
        if (!$this->config->isActive($storeId)) {
            return;
        }

        $commerceAccountId = $this->config->getCommerceAccountId($storeId);
        if ($commerceAccountId === null) {
            return;
        }

        $this->logger->info('[Meta Cron] Starting promotions sync', [
            'store_id' => $storeId
        ]);

        try {
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('is_active', 1)
                ->addFieldToFilter('use_auto_generation', 0);

            $now = date('Y-m-d');
            $promotions = [];

            foreach ($collection as $rule) {
                $ruleId = (int) $rule->getRuleId();
                if ($ruleId <= 0) {
                    continue;
                }

                $fromDate = $rule->getFromDate();
                $toDate = $rule->getToDate();

                if ($toDate && $toDate < $now) {
                    continue;
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
                    'start_date' => $fromDate ?: $now,
                    'status' => 'ACTIVE'
                ];

                if ($toDate) {
                    $promotionData['end_date'] = $toDate;
                }

                $couponCode = $rule->getCouponCode();
                if ($couponCode) {
                    $promotionData['coupon_code'] = $couponCode;
                }

                $promotions[] = $promotionData;
            }

            if (!empty($promotions)) {
                $endpoint = $commerceAccountId . '/promotions';
                foreach (array_chunk($promotions, 50) as $index => $batch) {
                    $result = $this->fbeHelper->apiPost($endpoint, [
                        'data' => json_encode(
                            $batch,
                            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                        )
                    ]);

                    if (isset($result['error'])) {
                        $this->logger->warning('[Meta Cron] Promotions batch API error', [
                            'store_id' => $storeId,
                            'batch_index' => $index + 1,
                            'batch_size' => count($batch),
                            'http_status' => $result['http_status'] ?? null,
                            'error' => $result['error']
                        ]);
                    }
                }
            }

            $this->logger->info('[Meta Cron] Promotions sync completed', [
                'store_id' => $storeId,
                'count' => count($promotions)
            ]);
        } catch (JsonException $e) {
            $this->logger->error('[Meta Cron] Promotions payload encode failed', [
                'error' => $e->getMessage()
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[Meta Cron] Promotions sync failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function resolveCronStoreId(): ?int
    {
        if ($this->storeManager === null) {
            return null;
        }

        try {
            foreach ($this->storeManager->getStores(true) as $store) {
                $storeId = (int) $store->getId();
                if ($storeId > 0) {
                    return $storeId;
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
