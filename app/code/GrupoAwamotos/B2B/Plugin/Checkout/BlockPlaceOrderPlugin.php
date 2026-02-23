<?php
/**
 * Block order placement for non-approved logged-in customers and enforce minimum order amount.
 * Intercepts the REST/GraphQL payment+placeOrder endpoint.
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Plugin\Checkout;

use GrupoAwamotos\B2B\Api\PriceVisibilityInterface;
use GrupoAwamotos\B2B\Helper\Config;
use GrupoAwamotos\ERPIntegration\Model\ResourceModel\SyncLog as SyncLogResource;
use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Psr\Log\LoggerInterface;

class BlockPlaceOrderPlugin
{
    /**
     * @var PriceVisibilityInterface
     */
    private $priceVisibility;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var SyncLogResource
     */
    private $syncLogResource;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        PriceVisibilityInterface $priceVisibility,
        Config $config,
        CartRepositoryInterface $cartRepository,
        CustomerSession $customerSession,
        CustomerRepositoryInterface $customerRepository,
        SyncLogResource $syncLogResource,
        ?LoggerInterface $logger = null
    ) {
        $this->priceVisibility = $priceVisibility;
        $this->config = $config;
        $this->cartRepository = $cartRepository;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->syncLogResource = $syncLogResource;
        $this->logger = $logger;
    }

    /**
     * Before savePaymentInformationAndPlaceOrder - block if user is not approved or below minimum
     *
     * @param PaymentInformationManagementInterface $subject
     * @param int $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return array
     * @throws CouldNotSaveException
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        PaymentInformationManagementInterface $subject,
        $cartId,
        PaymentInterface $paymentMethod,
        ?AddressInterface $billingAddress = null
    ): array {
        if (!$this->config->isEnabled()) {
            return [$cartId, $paymentMethod, $billingAddress];
        }

        if (!$this->priceVisibility->canAddToCart()) {
            throw new CouldNotSaveException(
                __('Sua conta precisa ser aprovada antes de realizar compras. Por favor, aguarde a aprovação.')
            );
        }

        // Validate ERP customer code exists (required for ERP order sync)
        if ($this->customerSession->isLoggedIn()) {
            $customerId = (int) $this->customerSession->getCustomerId();
            $erpCode = $this->getCustomerErpCode($customerId);
            if (!$erpCode) {
                throw new CouldNotSaveException(
                    __('Seu cadastro ainda não está vinculado ao sistema ERP. Entre em contato com o departamento comercial para liberar seus pedidos.')
                );
            }
        }

        // Enforce minimum order amount
        if ($this->config->isMinQtyEnabled()) {
            $minAmount = $this->config->getMinOrderAmount();
            if ($minAmount > 0) {
                try {
                    $quote = $this->cartRepository->getActive($cartId);
                    $subtotal = (float) $quote->getBaseSubtotal();

                    if ($subtotal < $minAmount) {
                        throw new CouldNotSaveException(
                            __($this->config->getMinOrderMessage())
                        );
                    }
                } catch (CouldNotSaveException $e) {
                    throw $e;
                } catch (\Exception $e) {
                    // If we can't load the quote, allow the order to proceed
                    if ($this->logger) {
                        $this->logger->debug('[B2B] Exception: ' . $e->getMessage(), ['exception' => $e]);
                    }
                }
            }
        }

        return [$cartId, $paymentMethod, $billingAddress];
    }

    /**
     * Get customer ERP code from attribute or entity_map fallback
     */
    private function getCustomerErpCode(int $customerId): ?int
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            $attr = $customer->getCustomAttribute('erp_code');
            $erpCode = ($attr && $attr->getValue()) ? $attr->getValue() : null;

            if ($erpCode === null) {
                $erpCode = $this->syncLogResource->getErpCodeByMagentoId('customer', $customerId);
            }

            return ($erpCode !== null && is_numeric($erpCode)) ? (int) $erpCode : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
