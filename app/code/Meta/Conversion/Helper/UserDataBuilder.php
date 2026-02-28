<?php

declare(strict_types=1);

namespace Meta\Conversion\Helper;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Stdlib\CookieManagerInterface;

/**
 * Builds Meta Conversions API user_data payload with frontend context.
 */
class UserDataBuilder
{
    private const COOKIE_FBP = '_fbp';
    private const COOKIE_FBC = '_fbc';
    private const MAX_UA_LENGTH = 1024;

    public function __construct(
        private readonly CookieManagerInterface $cookieManager,
        private readonly RequestInterface $request,
        private readonly RemoteAddress $remoteAddress
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function build(?string $email = null, ?string $phone = null, ?string $externalId = null): array
    {
        $userData = [];

        $hashedEmail = $this->hashEmail($email);
        if ($hashedEmail !== null) {
            $userData['em'] = $hashedEmail;
        }

        $hashedPhone = $this->hashPhone($phone);
        if ($hashedPhone !== null) {
            $userData['ph'] = $hashedPhone;
        }

        $hashedExternalId = $this->hashExternalId($externalId);
        if ($hashedExternalId !== null) {
            $userData['external_id'] = $hashedExternalId;
        }

        $fbp = $this->sanitizeCookieValue($this->cookieManager->getCookie(self::COOKIE_FBP));
        if ($fbp !== null) {
            $userData['fbp'] = $fbp;
        }

        $fbc = $this->sanitizeCookieValue($this->cookieManager->getCookie(self::COOKIE_FBC));
        if ($fbc !== null) {
            $userData['fbc'] = $fbc;
        }

        $remoteIp = $this->sanitizeIp($this->remoteAddress->getRemoteAddress());
        if ($remoteIp !== null) {
            $userData['client_ip_address'] = $remoteIp;
        }

        $userAgent = $this->sanitizeUserAgent((string) $this->request->getServer('HTTP_USER_AGENT', ''));
        if ($userAgent !== null) {
            $userData['client_user_agent'] = $userAgent;
        }

        return $userData;
    }

    public function getEventSourceUrl(): ?string
    {
        $host = trim((string) $this->request->getServer('HTTP_HOST', ''));
        $requestUri = trim((string) $this->request->getServer('REQUEST_URI', ''));

        if ($host === '' || $requestUri === '') {
            return null;
        }

        $scheme = 'https';
        $forwardedProto = trim((string) $this->request->getServer('HTTP_X_FORWARDED_PROTO', ''));
        if ($forwardedProto !== '') {
            $proto = strtolower(strtok($forwardedProto, ',') ?: $forwardedProto);
            if (in_array($proto, ['http', 'https'], true)) {
                $scheme = $proto;
            }
        } else {
            $https = strtolower((string) $this->request->getServer('HTTPS', ''));
            if ($https === '' || $https === 'off' || $https === '0') {
                $scheme = 'http';
            }
        }

        if ($requestUri[0] !== '/') {
            $requestUri = '/' . $requestUri;
        }

        $url = $scheme . '://' . $host . $requestUri;

        return mb_substr($url, 0, 2048);
    }

    private function hashEmail(?string $email): ?string
    {
        $email = trim((string) $email);
        if ($email === '') {
            return null;
        }

        return hash('sha256', strtolower($email));
    }

    private function hashPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (!is_string($digits) || $digits === '') {
            return null;
        }

        return hash('sha256', $digits);
    }

    private function hashExternalId(?string $externalId): ?string
    {
        $externalId = trim((string) $externalId);
        if ($externalId === '') {
            return null;
        }

        return hash('sha256', $externalId);
    }

    private function sanitizeCookieValue(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, 255);
    }

    private function sanitizeIp(?string $ip): ?string
    {
        $ip = trim((string) $ip);
        if ($ip === '') {
            return null;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        return $ip;
    }

    private function sanitizeUserAgent(string $userAgent): ?string
    {
        $userAgent = trim($userAgent);
        if ($userAgent === '') {
            return null;
        }

        return mb_substr($userAgent, 0, self::MAX_UA_LENGTH);
    }
}
