<?php
declare(strict_types=1);

namespace GrupoAwamotos\Theme\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;

class ContactInfo implements ArgumentInterface
{
    private const XML_PATH_WHATSAPP_NUMBER = 'grupoawamotos_theme/contact/whatsapp_number';
    private const XML_PATH_WHATSAPP_MESSAGE = 'grupoawamotos_theme/contact/whatsapp_message';
    private const XML_PATH_PHONE = 'grupoawamotos_theme/contact/phone';
    private const XML_PATH_EMAIL = 'grupoawamotos_theme/contact/email';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function getWhatsAppNumber(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_WHATSAPP_NUMBER,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function hasWhatsApp(): bool
    {
        return $this->getWhatsAppDigits() !== '';
    }

    public function getWhatsAppDigits(): string
    {
        return $this->normalizePhone($this->getWhatsAppNumber());
    }

    public function getWhatsAppUrl(): string
    {
        $digits = $this->getWhatsAppDigits();
        if ($digits === '') {
            return '';
        }

        $query = trim($this->getWhatsAppMessage()) !== ''
            ? '?text=' . rawurlencode($this->getWhatsAppMessage())
            : '';

        return 'https://wa.me/' . $digits . $query;
    }

    public function getWhatsAppMessage(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_WHATSAPP_MESSAGE,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getPhone(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_PHONE,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function hasPhone(): bool
    {
        return $this->getPhoneDigits() !== '';
    }

    public function getPhoneDigits(): string
    {
        return $this->normalizePhone($this->getPhone());
    }

    public function getPhoneUrl(): string
    {
        $digits = $this->getPhoneDigits();

        return $digits !== '' ? 'tel:+' . $digits : '';
    }

    public function getEmail(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function hasEmail(): bool
    {
        return trim($this->getEmail()) !== '';
    }

    public function getEmailUrl(): string
    {
        $email = trim($this->getEmail());

        return $email !== '' ? 'mailto:' . $email : '';
    }

    public function hasAnyContact(): bool
    {
        return $this->hasWhatsApp() || $this->hasPhone() || $this->hasEmail();
    }

    private function normalizePhone(string $value): string
    {
        return (string) preg_replace('/\D+/', '', $value);
    }
}
