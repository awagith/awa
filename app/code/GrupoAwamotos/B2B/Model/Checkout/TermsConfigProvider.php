<?php
/**
 * B2B Checkout Terms Config Provider
 *
 * Provides B2B terms and conditions configuration to checkout JS context.
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Model\Checkout;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class TermsConfigProvider implements ConfigProviderInterface
{
    private const XML_PATH_PREFIX = 'grupoawamotos_b2b/checkout/';

    private ScopeConfigInterface $scopeConfig;
    private CustomerSession $customerSession;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CustomerSession $customerSession
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
    }

    /**
     * Provide B2B checkout terms config
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        // Only provide config for logged-in customers
        if (!$this->customerSession->isLoggedIn()) {
            return [];
        }

        if (!$this->isTermsEnabled()) {
            return [];
        }

        return [
            'b2bCheckout' => [
                'terms' => [
                    'enabled' => true,
                    'checkboxText' => $this->getConfigValue('terms_checkbox_text')
                        ?: 'Li e aceito os Termos de Venda B2B',
                    'content' => $this->getConfigValue('terms_content') ?: '',
                    'warningTitle' => $this->getConfigValue('terms_warning_title')
                        ?: 'Atenção',
                    'warningContent' => $this->getConfigValue('terms_warning_content')
                        ?: 'Você deve aceitar os termos e condições para continuar.',
                ],
                'poNumber' => [
                    'enabled' => $this->isConfigEnabled('po_number_enabled'),
                    'required' => $this->isConfigEnabled('po_number_required'),
                ],
                'deliveryDate' => [
                    'enabled' => $this->isConfigEnabled('delivery_date_enabled'),
                ],
                'orderNotes' => [
                    'enabled' => $this->isConfigEnabled('order_notes_enabled'),
                ],
            ],
        ];
    }

    /**
     * Check if terms are enabled
     *
     * @return bool
     */
    private function isTermsEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_PREFIX . 'terms_enabled',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if a config field is enabled
     *
     * @param string $field
     * @return bool
     */
    private function isConfigEnabled(string $field): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_PREFIX . $field,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get config value
     *
     * @param string $field
     * @return string|null
     */
    private function getConfigValue(string $field): ?string
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_PREFIX . $field,
            ScopeInterface::SCOPE_STORE
        );

        return $value !== null ? (string) $value : null;
    }
}
