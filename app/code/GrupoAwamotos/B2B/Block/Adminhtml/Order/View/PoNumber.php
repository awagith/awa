<?php

declare(strict_types=1);

namespace GrupoAwamotos\B2B\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Block to display PO Number in admin order view
 * P0-1: Purchase Order Number
 */
class PoNumber extends Template
{
    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Get current order
     *
     * @return OrderInterface|null
     */
    public function getOrder(): ?OrderInterface
    {
        return $this->registry->registry('current_order');
    }

    /**
     * Get PO Number from order
     *
     * @return string|null
     */
    public function getPoNumber(): ?string
    {
        $order = $this->getOrder();
        if ($order === null) {
            return null;
        }

        return $order->getData('b2b_po_number') ?: null;
    }

    /**
     * Check if PO Number exists
     *
     * @return bool
     */
    public function hasPoNumber(): bool
    {
        $poNumber = $this->getPoNumber();
        return $poNumber !== null && trim($poNumber) !== '';
    }

    /**
     * Get block title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return (string)__('Pedido de Compra (PO)');
    }
}
