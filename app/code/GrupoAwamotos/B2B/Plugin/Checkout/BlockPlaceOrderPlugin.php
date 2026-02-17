<?php
/**
 * Block order placement for non-approved logged-in customers and enforce minimum order amount.
 * Intercepts the REST/GraphQL payment+placeOrder endpoint.
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Plugin\Checkout;

use GrupoAwamotos\B2B\Api\PriceVisibilityInterface;
use GrupoAwamotos\B2B\Helper\Config;
use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;

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

    public function __construct(
        PriceVisibilityInterface $priceVisibility,
        Config $config,
        CartRepositoryInterface $cartRepository
    ) {
        $this->priceVisibility = $priceVisibility;
        $this->config = $config;
        $this->cartRepository = $cartRepository;
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
                }
            }
        }

        return [$cartId, $paymentMethod, $billingAddress];
    }
}
