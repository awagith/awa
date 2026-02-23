<?php

declare(strict_types=1);

namespace GrupoAwamotos\B2B\Block\Order;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Block for CSV Export link on order history page
 * P1-2: CSV Export for B2B customers
 */
class ExportLink extends Template
{
    private CustomerSession $customerSession;

    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        parent::__construct($context, $data);
    }

    /**
     * Check if customer can export (logged in B2B customer)
     *
     * @return bool
     */
    public function canExport(): bool
    {
        return $this->customerSession->isLoggedIn();
    }

    /**
     * Get CSV export URL
     *
     * @return string
     */
    public function getExportUrl(): string
    {
        return $this->getUrl('b2b/order/exportCsv');
    }

    /**
     * Get link label
     *
     * @return string
     */
    public function getLinkLabel(): string
    {
        return (string) __('Exportar Pedidos (CSV)');
    }

    /**
     * Get link tooltip
     *
     * @return string
     */
    public function getTooltip(): string
    {
        return (string) __('Baixar histórico de pedidos em formato CSV para importar em planilhas');
    }
}
