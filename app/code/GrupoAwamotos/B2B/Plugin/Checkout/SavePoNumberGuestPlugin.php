<?php
/**
 * Plugin para salvar PO Number no Quote via guest checkout
 * P0-1: Purchase Order Number
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Plugin\Checkout;

use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Psr\Log\LoggerInterface;

class SavePoNumberGuestPlugin
{
    private CartRepositoryInterface $cartRepository;
    private QuoteIdMaskFactory $quoteIdMaskFactory;
    private LoggerInterface $logger;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        LoggerInterface $logger
    ) {
        $this->cartRepository = $cartRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->logger = $logger;
    }

    /**
     * Before save payment info and place order — guest
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        GuestPaymentInformationManagementInterface $subject,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        ?AddressInterface $billingAddress = null
    ): array {
        $this->extractAndSavePoNumber($cartId, $paymentMethod);
        return [$cartId, $email, $paymentMethod, $billingAddress];
    }

    /**
     * Before save payment info (without place order) — guest
     */
    public function beforeSavePaymentInformation(
        GuestPaymentInformationManagementInterface $subject,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        ?AddressInterface $billingAddress = null
    ): array {
        $this->extractAndSavePoNumber($cartId, $paymentMethod);
        return [$cartId, $email, $paymentMethod, $billingAddress];
    }

    /**
     * Extract PO Number from payment extension attributes and save to quote
     */
    private function extractAndSavePoNumber(string $cartId, PaymentInterface $paymentMethod): void
    {
        try {
            $extensionAttributes = $paymentMethod->getExtensionAttributes();
            if ($extensionAttributes === null) {
                return;
            }

            $poNumber = $extensionAttributes->getB2bPoNumber();
            if (empty($poNumber)) {
                return;
            }

            // Decode masked quote ID
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
            $quoteId = (int) $quoteIdMask->getQuoteId();

            if ($quoteId === 0) {
                return;
            }

            $quote = $this->cartRepository->getActive($quoteId);
            $quote->setData('b2b_po_number', $poNumber);
            $this->cartRepository->save($quote);

            $this->logger->info('[B2B] PO Number salvo no quote (guest)', [
                'quote_id' => $quoteId,
                'po_number' => $poNumber
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[B2B] Erro ao salvar PO Number (guest): ' . $e->getMessage());
        }
    }
}
