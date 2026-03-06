<?php

declare(strict_types=1);

namespace GrupoAwamotos\MarketingIntelligence\Model\Service;

use GrupoAwamotos\MarketingIntelligence\Api\AudienceRepositoryInterface;
use GrupoAwamotos\MarketingIntelligence\Api\Data\AudienceInterface;
use GrupoAwamotos\MarketingIntelligence\Model\AudienceFactory;
use GrupoAwamotos\MarketingIntelligence\Model\ResourceModel\Audience\CollectionFactory as AudienceCollectionFactory;
use Meta\BusinessExtension\Helper\FBEHelper;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * Syncs Custom and Lookalike Audiences with Meta Marketing API.
 */
class AudienceSyncer
{
    private const XML_PATH_ENABLED = 'marketing_intelligence/meta_audiences/enabled';
    private const XML_PATH_AD_ACCOUNT_ID = 'marketing_intelligence/meta_audiences/ad_account_id';
    private const XML_PATH_SYSTEM_USER_TOKEN = 'marketing_intelligence/meta_audiences/system_user_token';
    private const XML_PATH_AUTO_LOOKALIKE = 'marketing_intelligence/meta_audiences/auto_lookalike';
    private const XML_PATH_LOOKALIKE_RATIO = 'marketing_intelligence/meta_audiences/lookalike_ratio';
    private const LOOKALIKE_COUNTRY = 'BR';
    private const BATCH_SIZE = 500;

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly FBEHelper $fbeHelper,
        private readonly AudienceRepositoryInterface $audienceRepository,
        private readonly AudienceFactory $audienceFactory,
        private readonly AudienceCollectionFactory $audienceCollectionFactory,
        private readonly CustomerCollectionFactory $customerCollectionFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Refresh all active audiences: re-upload hashes and optionally create lookalikes.
     *
     * @return int Number of audiences processed
     */
    public function refreshAll(): int
    {
        if (!$this->isEnabled()) {
            $this->logger->info('AudienceSyncer: disabled via config.');
            return 0;
        }

        $collection = $this->audienceCollectionFactory->create();
        $collection->addFieldToFilter('status', 'active');
        $collection->addFieldToFilter('auto_refresh', 1);
        $processed = 0;

        /** @var AudienceInterface $audience */
        foreach ($collection as $audience) {
            try {
                $this->syncAudience($audience);
                $processed++;
            } catch (\Exception $e) {
                $this->logger->error(sprintf(
                    'AudienceSyncer: error syncing audience %d — %s',
                    $audience->getAudienceId(),
                    $e->getMessage()
                ));
            }
        }

        $this->logger->info(sprintf('AudienceSyncer: %d audiences refreshed.', $processed));
        return $processed;
    }

    /**
     * Create a new Custom Audience from a segment rule and upload hashed customer data.
     *
     * @param string $name Audience name
     * @param string $audienceType custom|lookalike
     * @param array<string, mixed> $segmentRule Filter criteria (JSON-serializable)
     * @return AudienceInterface
     */
    public function createCustomAudience(string $name, array $segmentRule): AudienceInterface
    {
        $adAccountId = $this->getAdAccountId();

        $response = $this->fbeHelper->apiPost(
            sprintf('/%s/customaudiences', $adAccountId),
            [
                'name' => $name,
                'subtype' => 'CUSTOM',
                'description' => 'AWA Motos Marketing Intelligence — ' . $name,
                'customer_file_source' => 'USER_PROVIDED_ONLY',
            ]
        );

        $metaAudienceId = $response['id'] ?? null;
        if (empty($metaAudienceId)) {
            throw new \RuntimeException('Meta API did not return an audience ID.');
        }

        $audience = $this->audienceFactory->create();
        $audience->setMetaAudienceId($metaAudienceId);
        $audience->setName($name);
        $audience->setAudienceType('custom');
        $audience->setSegmentRule(json_encode($segmentRule, JSON_THROW_ON_ERROR));
        $audience->setStatus('active');
        $audience->setAutoRefresh(true);
        $audience->setRefreshFrequency('daily');

        $customerCount = $this->uploadCustomerHashes($metaAudienceId, $segmentRule);
        $audience->setCustomerCount($customerCount);
        $audience->setLastSyncedAt(date('Y-m-d H:i:s'));

        $this->audienceRepository->save($audience);

        $this->logger->info(sprintf(
            'AudienceSyncer: created audience "%s" (Meta ID: %s) with %d customers.',
            $name,
            $metaAudienceId,
            $customerCount
        ));

        if ($this->isAutoLookalikeEnabled()) {
            $this->createLookalikeAudience($audience);
        }

        return $audience;
    }

    /**
     * Create a Lookalike Audience from an existing Custom Audience.
     */
    public function createLookalikeAudience(AudienceInterface $sourceAudience): AudienceInterface
    {
        $adAccountId = $this->getAdAccountId();
        $ratio = $this->getLookalikeRatio();

        $response = $this->fbeHelper->apiPost(
            sprintf('/%s/customaudiences', $adAccountId),
            [
                'name' => $sourceAudience->getName() . ' — Lookalike ' . $ratio . '%',
                'subtype' => 'LOOKALIKE',
                'origin_audience_id' => $sourceAudience->getMetaAudienceId(),
                'lookalike_spec' => json_encode([
                    'type' => 'similarity',
                    'ratio' => $ratio / 100,
                    'country' => self::LOOKALIKE_COUNTRY,
                ], JSON_THROW_ON_ERROR),
            ]
        );

        $metaAudienceId = $response['id'] ?? null;
        if (empty($metaAudienceId)) {
            throw new \RuntimeException('Meta API did not return a lookalike audience ID.');
        }

        $lookalike = $this->audienceFactory->create();
        $lookalike->setMetaAudienceId($metaAudienceId);
        $lookalike->setName($sourceAudience->getName() . ' — Lookalike ' . $ratio . '%');
        $lookalike->setAudienceType('lookalike');
        $lookalike->setSourceAudienceId($sourceAudience->getAudienceId());
        $lookalike->setLookalikeRatio($ratio);
        $lookalike->setLookalikeCountry(self::LOOKALIKE_COUNTRY);
        $lookalike->setStatus('active');
        $lookalike->setAutoRefresh(false);
        $lookalike->setLastSyncedAt(date('Y-m-d H:i:s'));

        $this->audienceRepository->save($lookalike);

        $this->logger->info(sprintf(
            'AudienceSyncer: created lookalike "%s" (Meta ID: %s) from source %d.',
            $lookalike->getName(),
            $metaAudienceId,
            $sourceAudience->getAudienceId()
        ));

        return $lookalike;
    }

    private function syncAudience(AudienceInterface $audience): void
    {
        $metaAudienceId = $audience->getMetaAudienceId();
        if (empty($metaAudienceId)) {
            return;
        }

        $segmentRule = json_decode($audience->getSegmentRule() ?: '{}', true);
        $customerCount = $this->uploadCustomerHashes($metaAudienceId, $segmentRule);

        $audience->setCustomerCount($customerCount);
        $audience->setLastSyncedAt(date('Y-m-d H:i:s'));
        $this->audienceRepository->save($audience);
    }

    /**
     * Upload SHA256-hashed customer emails/phones to a Custom Audience.
     *
     * @param array<string, mixed> $segmentRule
     * @return int Customer count uploaded
     */
    private function uploadCustomerHashes(string $metaAudienceId, array $segmentRule): int
    {
        $customers = $this->getCustomersBySegment($segmentRule);
        $totalUploaded = 0;
        $batch = [];

        foreach ($customers as $customer) {
            $email = $customer->getEmail();
            if (empty($email)) {
                continue;
            }

            $row = [hash('sha256', mb_strtolower(trim($email)))];

            $phone = $customer->getData('telefone') ?: $customer->getData('telephone');
            if (!empty($phone)) {
                $digits = preg_replace('/\D/', '', $phone);
                if (!empty($digits)) {
                    $row[] = hash('sha256', $digits);
                }
            }

            $batch[] = $row;
            $totalUploaded++;

            if (count($batch) >= self::BATCH_SIZE) {
                $this->sendBatch($metaAudienceId, $batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            $this->sendBatch($metaAudienceId, $batch);
        }

        return $totalUploaded;
    }

    /**
     * @param array<int, array<int, string>> $batch
     */
    private function sendBatch(string $metaAudienceId, array $batch): void
    {
        $payload = [
            'payload' => json_encode([
                'schema' => ['EMAIL', 'PHONE'],
                'data' => $batch,
            ], JSON_THROW_ON_ERROR),
        ];

        $this->fbeHelper->apiPost(
            sprintf('/%s/users', $metaAudienceId),
            $payload
        );
    }

    /**
     * @param array<string, mixed> $segmentRule
     * @return \Magento\Customer\Model\ResourceModel\Customer\Collection
     */
    private function getCustomersBySegment(array $segmentRule): \Magento\Customer\Model\ResourceModel\Customer\Collection
    {
        $collection = $this->customerCollectionFactory->create();
        $collection->addAttributeToSelect(['email', 'firstname', 'lastname']);

        if (!empty($segmentRule['group_id'])) {
            $collection->addFieldToFilter('group_id', ['in' => (array)$segmentRule['group_id']]);
        }

        if (!empty($segmentRule['cnae_profile'])) {
            $collection->addAttributeToFilter('cnae_profile', ['in' => (array)$segmentRule['cnae_profile']]);
        }

        if (!empty($segmentRule['uf'])) {
            $collection->addAttributeToFilter('uf', ['in' => (array)$segmentRule['uf']]);
        }

        if (!empty($segmentRule['min_orders'])) {
            $collection->getSelect()->joinLeft(
                ['orders' => $collection->getTable('sales_order')],
                'orders.customer_id = e.entity_id',
                ['order_count' => 'COUNT(orders.entity_id)']
            );
            $collection->getSelect()->group('e.entity_id');
            $collection->getSelect()->having('order_count >= ?', (int)$segmentRule['min_orders']);
        }

        return $collection;
    }

    private function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED);
    }

    private function getAdAccountId(): string
    {
        $id = (string)$this->scopeConfig->getValue(self::XML_PATH_AD_ACCOUNT_ID);
        if (empty($id)) {
            throw new \RuntimeException('Meta Ad Account ID not configured.');
        }

        if (!str_starts_with($id, 'act_')) {
            $id = 'act_' . $id;
        }

        return $id;
    }

    private function isAutoLookalikeEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_AUTO_LOOKALIKE);
    }

    private function getLookalikeRatio(): int
    {
        $ratio = (int)$this->scopeConfig->getValue(self::XML_PATH_LOOKALIKE_RATIO);
        return max(1, min(10, $ratio ?: 3));
    }
}
