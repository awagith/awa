<?php
namespace GrupoAwamotos\RexisML\Helper;

use GrupoAwamotos\ERPIntegration\Model\WhatsApp\ZApiClient;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;

class WhatsAppNotifier extends AbstractHelper
{
    private ZApiClient $zApiClient;
    private CustomerRepositoryInterface $customerRepository;
    private ProductRepositoryInterface $productRepository;
    private LoggerInterface $logger;

    public function __construct(
        Context $context,
        ZApiClient $zApiClient,
        CustomerRepositoryInterface $customerRepository,
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->zApiClient = $zApiClient;
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    /**
     * Send cross-sell alert to managers/sellers via WhatsApp (Z-API)
     */
    public function sendCrosssellAlert($collection): bool
    {
        try {
            $message = "*REXIS ML - Oportunidades de Cross-sell*\n\n";
            $message .= "*" . $collection->getSize() . " oportunidades detectadas*\n\n";

            $count = 0;
            foreach ($collection as $item) {
                if ($count >= 5) break;
                try {
                    $customer = $this->customerRepository->getById($item->getIdentificadorCliente());
                    $product = $this->productRepository->get($item->getIdentificadorProduto());
                    $message .= sprintf(
                        "*%s*\n%s\nR$ %s | Score: %.1f%%\n\n",
                        $customer->getFirstname() . ' ' . $customer->getLastname(),
                        $product->getName(),
                        number_format($item->getPrevisaoGastoRoundUp(), 2, ',', '.'),
                        $item->getPred() * 100
                    );
                    $count++;
                } catch (\Exception $e) {
                    continue;
                }
            }

            $recipients = $this->scopeConfig->getValue('rexisml/whatsapp/recipients');
            if (!$recipients) {
                $this->logger->warning('[RexisML WhatsApp] No recipients configured');
                return false;
            }

            foreach (explode(',', $recipients) as $number) {
                $this->zApiClient->sendTextMessage(trim($number), $message);
            }
            return true;
        } catch (\Exception $e) {
            $this->logger->error('[RexisML WhatsApp] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send churn recovery message to a specific customer
     */
    public function sendChurnRecovery(int $customerId, string $productSku, float $discount = 10): bool
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            $product = $this->productRepository->get($productSku);

            $phone = $this->getCustomerPhone($customer);
            if (!$phone) {
                return false;
            }

            $message = sprintf(
                "Ola *%s*!\n\n" .
                "Notamos que voce gostava de comprar *%s* e temos uma oferta especial!\n\n" .
                "*%.0f%% de desconto* exclusivo\n" .
                "Valido por 7 dias\n\n" .
                "Acesse nosso site e use o cupom: *RETORNO%d*",
                $customer->getFirstname(),
                $product->getName(),
                $discount,
                $customerId
            );

            return $this->zApiClient->sendTextMessage($phone, $message) !== null;
        } catch (\Exception $e) {
            $this->logger->error('[RexisML WhatsApp] ChurnRecovery: ' . $e->getMessage());
            return false;
        }
    }

    private function getCustomerPhone($customer): ?string
    {
        $phoneAttr = $customer->getCustomAttribute('telephone');
        return $phoneAttr ? $phoneAttr->getValue() : null;
    }
}
