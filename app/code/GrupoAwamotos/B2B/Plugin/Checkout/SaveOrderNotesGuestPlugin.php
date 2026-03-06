<?php
/**
 * Plugin para salvar Order Notes no Quote via guest checkout
 * P2-4.2: Order Notes
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Plugin\Checkout;

use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Psr\Log\LoggerInterface;

class SaveOrderNotesGuestPlugin
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
     *
     * @param GuestPaymentInformationManagementInterface $subject
     * @param string $cartId
     * @param string $email
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return array
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        GuestPaymentInformationManagementInterface $subject,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        ?AddressInterface $billingAddress = null
    ): array {
        $this->extractAndSaveOrderNotes($cartId, $paymentMethod);

        return [$cartId, $email, $paymentMethod, $billingAddress];
    }

    /**
     * Before save payment info (without place order) — guest
     *
     * @param GuestPaymentInformationManagementInterface $subject
     * @param string $cartId
     * @param string $email
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return array
     */
    public function beforeSavePaymentInformation(
        GuestPaymentInformationManagementInterface $subject,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        ?AddressInterface $billingAddress = null
    ): array {
        $this->extractAndSaveOrderNotes($cartId, $paymentMethod);

        return [$cartId, $email, $paymentMethod, $billingAddress];
    }

    /**
     * Extract Order Notes from payment extension attributes and save to quote
     */
    private function extractAndSaveOrderNotes(string $cartId, PaymentInterface $paymentMethod): void
    {
        try {
            $extensionAttributes = $paymentMethod->getExtensionAttributes();
            if ($extensionAttributes === null) {
                return;
            }

            $orderNotes = $extensionAttributes->getB2bOrderNotes();
            if (empty($orderNotes)) {
                return;
            }

            // Strip HTML tags and trim
            $orderNotes = trim(strip_tags((string) $orderNotes));
            if ($orderNotes === '') {
                return;
            }

            // Limit to 500 chars
            if (mb_strlen($orderNotes) > 500) {
                $orderNotes = mb_substr($orderNotes, 0, 500);
            }

            // Decode masked quote ID
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
            $quoteId = (int) $quoteIdMask->getQuoteId();

            if ($quoteId === 0) {
                return;
            }

            $quote = $this->cartRepository->getActive($quoteId);
            $quote->setData('b2b_order_notes', $orderNotes);
            $this->cartRepository->save($quote);

            $this->logger->info('[B2B] Order Notes salvo no quote (guest)', [
                'quote_id' => $quoteId,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[B2B] Erro ao salvar Order Notes (guest): ' . $e->getMessage());
        }
    }
}
