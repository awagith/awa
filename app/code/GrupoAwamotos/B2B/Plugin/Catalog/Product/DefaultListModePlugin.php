<?php
/**
 * Force list view as default mode for B2B customers on PLP.
 *
 * B2B buyers use list mode to scan SKUs and quantities efficiently.
 * This only changes the DEFAULT — if the customer explicitly chose grid
 * (cookie set), the toolbar respects that choice over this default.
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Plugin\Catalog\Product;

use GrupoAwamotos\B2B\Helper\Data as B2BHelper;
use Magento\Catalog\Helper\Product\ProductList;
use Psr\Log\LoggerInterface;

class DefaultListModePlugin
{
    public function __construct(
        private readonly B2BHelper $b2bHelper,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Return 'list' as default view mode for B2B customers.
     *
     * @param ProductList $subject
     * @param string $result
     * @return string
     */
    public function afterGetDefaultViewMode(ProductList $subject, string $result): string
    {
        if ($result === 'list') {
            return $result;
        }

        try {
            if ($this->b2bHelper->isB2BCustomer()) {
                return 'list';
            }
        } catch (\Exception $e) {
            $this->logger->error('[B2B DefaultListMode] ' . $e->getMessage());
        }

        return $result;
    }
}
