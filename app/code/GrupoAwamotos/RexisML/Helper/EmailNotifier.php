<?php
namespace GrupoAwamotos\RexisML\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class EmailNotifier extends AbstractHelper
{
    protected $transportBuilder;
    protected $inlineTranslation;
    protected $storeManager;
    protected $customerRepository;
    protected $productRepository;
    protected $resource;
    protected $logger;

    public function __construct(
        Context $context,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        StoreManagerInterface $storeManager,
        CustomerRepositoryInterface $customerRepository,
        ProductRepositoryInterface $productRepository,
        ResourceConnection $resource,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
        $this->resource = $resource;
        $this->logger = $logger;
    }

    /**
     * Enviar alerta de oportunidades de Churn
     *
     * @param array $rows Raw recommendation rows from DB
     * @return bool
     */
    public function sendChurnAlert(array $rows): bool
    {
        return $this->sendAlert($rows, 'rexisml_churn_alert', 'churn');
    }

    /**
     * Enviar alerta de oportunidades de Cross-sell
     *
     * @param array $rows Raw recommendation rows from DB
     * @return bool
     */
    public function sendCrosssellAlert(array $rows): bool
    {
        return $this->sendAlert($rows, 'rexisml_crosssell_alert', 'crosssell');
    }

    /**
     * Send alert email with opportunity data
     */
    private function sendAlert(array $rows, string $templateId, string $type): bool
    {
        if (empty($rows)) {
            return false;
        }

        try {
            $this->inlineTranslation->suspend();

            $opportunities = [];
            foreach ($rows as $row) {
                $erpCode = $row['identificador_cliente'] ?? '';
                $productCode = $row['identificador_produto'] ?? '';

                // Resolve customer name via ERP mapping
                $customerInfo = $this->resolveCustomerInfo($erpCode);
                $productInfo = $this->resolveProductInfo($productCode);

                $score = round(((float)($row['pred'] ?? 0)) * 100, 1);
                $predictedValue = number_format((float)($row['previsao_gasto_round_up'] ?? 0), 2, ',', '.');

                $opportunities[] = [
                    'customer_name' => $customerInfo['name'],
                    'customer_email' => $customerInfo['email'],
                    'product_name' => $productInfo['name'],
                    'product_sku' => $productCode,
                    'score' => $score,
                    'predicted_value' => $predictedValue,
                    'recency_days' => (int)($row['recencia'] ?? 0),
                    'lift' => round((float)($row['lift'] ?? 0), 2),
                    'tipo' => $type,
                ];
            }

            if (empty($opportunities)) {
                return false;
            }

            $configPath = $type === 'churn'
                ? 'rexisml/alerts/churn_email_recipients'
                : 'rexisml/alerts/crosssell_email_recipients';

            $emailTo = $this->scopeConfig->getValue(
                $configPath,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            if (!$emailTo) {
                $emailTo = 'comercial@grupoawamotos.com.br';
            }

            $totalValue = 0;
            foreach ($opportunities as $opp) {
                $totalValue += (float)str_replace(['.', ','], ['', '.'], $opp['predicted_value']);
            }

            $templateVars = [
                'opportunities' => $opportunities,
                'total_value' => number_format($totalValue, 2, ',', '.'),
                'alert_date' => date('d/m/Y H:i'),
                'type_label' => $type === 'churn' ? 'Churn (Reativacao)' : 'Cross-sell',
                'count' => count($opportunities),
            ];

            $transport = $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $this->storeManager->getStore()->getId()
                ])
                ->setTemplateVars($templateVars)
                ->setFromByScope([
                    'email' => 'noreply@grupoawamotos.com.br',
                    'name' => 'REXIS ML - Sistema de Recomendacoes'
                ])
                ->addTo(explode(',', $emailTo))
                ->getTransport();

            $transport->sendMessage();
            $this->inlineTranslation->resume();

            $this->logger->info(sprintf(
                '[RexisML Email] %s alert sent with %d opportunities to %s',
                ucfirst($type), count($opportunities), $emailTo
            ));

            return true;

        } catch (\Exception $e) {
            $this->logger->error('[RexisML Email] Error sending ' . $type . ' alert: ' . $e->getMessage());
            $this->inlineTranslation->resume();
            return false;
        }
    }

    /**
     * Resolve ERP customer code to name/email
     * Uses erp_entity_map → Magento customer, with ERP code as fallback
     */
    private function resolveCustomerInfo(string $erpCode): array
    {
        $default = ['name' => 'Cliente ' . $erpCode, 'email' => ''];

        try {
            $connection = $this->resource->getConnection();

            // Try entity map first
            $mapTable = $this->resource->getTableName('grupoawamotos_erp_entity_map');
            $magentoId = $connection->fetchOne(
                $connection->select()
                    ->from($mapTable, 'magento_entity_id')
                    ->where('entity_type = ?', 'customer')
                    ->where('erp_code = ?', $erpCode)
                    ->limit(1)
            );

            if ($magentoId) {
                $customer = $this->customerRepository->getById((int)$magentoId);
                return [
                    'name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
                    'email' => $customer->getEmail(),
                ];
            }

            // Try loading directly if erpCode is numeric (might be Magento ID)
            if (is_numeric($erpCode)) {
                try {
                    $customer = $this->customerRepository->getById((int)$erpCode);
                    return [
                        'name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
                        'email' => $customer->getEmail(),
                    ];
                } catch (\Exception $e) {
                    // Not a Magento ID
                }
            }
        } catch (\Exception $e) {
            $this->logger->debug('[RexisML Email] Could not resolve customer ' . $erpCode . ': ' . $e->getMessage());
        }

        return $default;
    }

    /**
     * Resolve product code to name
     */
    private function resolveProductInfo(string $productCode): array
    {
        try {
            $product = $this->productRepository->get($productCode);
            return ['name' => $product->getName()];
        } catch (\Exception $e) {
            return ['name' => $productCode];
        }
    }
}
